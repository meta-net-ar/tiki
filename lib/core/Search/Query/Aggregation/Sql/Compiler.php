<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Query\Aggregation\Sql;

use RuntimeException;
use Search\Query\Aggregation\AggregationInterface;
use Search\Query\Aggregation\Formula;
use Search\Query\Aggregation\Metric;
use Search\Query\Aggregation\Terms;

/**
 * Compiles an aggregation tree into a list of {@see Plan} steps that the
 * executor will run against the per-request TEMPORARY table.
 *
 * Compilation is purely declarative: this class never touches the database.
 * It takes the table name + the schema produced by
 * {@see Schema\Inferrer} (so it knows the actual column names) + the
 * aggregation tree, and emits SQL strings + bind params.
 *
 * The output shape is one composite query per top-level group, plus one
 * scalar query per root-level metric:
 *
 *   {metric ...}        -> KIND_METRIC_ROOT  (scalar SELECT)
 *   {group ...}{...}    -> KIND_GROUP_BUCKETS (one big CTE-based SELECT)
 *
 * The composite group query covers in a single round-trip:
 *
 *   - per-key aggregation (per_key CTE)
 *   - Top-N selection
 *   - synthetic Others bucket (when withOthers=y AND size > 0)
 *   - synthetic Total bucket  (when withTotal=y)
 *   - all metric columns
 *
 * Identifier safety:
 *   - table name and column names are produced by us (executor + schema
 *     inferrer) and validated against `[A-Za-z_][A-Za-z0-9_]*` before
 *     being interpolated into SQL. Backticks are added only for known-safe
 *     identifiers.
 *   - user-authored strings (Others / Total labels) are the only literal
 *     values that travel and are passed via bind parameters.
 *
 * User-authored SQL fragments ({metric op="formula"} and {having}) are
 * passed through {@see \Search\Query\Aggregation\ExpressionSanitizer} by
 * the wiki builder before they reach this class. The compiler then
 * splices each fragment directly into the generated query *after* one
 * extra check: every identifier the fragment references must be in the
 * known set (temp-table columns at per_key scope, sibling metric
 * aliases at outer-SELECT / HAVING scope). The sanitizer guarantees
 * the rest (no semicolons, comments, statement starters, dangerous
 * functions, ...), so an identifier-domain check is enough to keep
 * a malicious fragment confined to its slot.
 */
class Compiler
{
    /**
     * @var array{columns: array, indexes?: array, warnings?: array}
     */
    private array $schema;

    /**
     * @var array<string, array{name: string, type: string, sql_type: string, source_field: string}>
     *      indexed by source field name for fast lookup
     */
    private array $columnsByField;

    private string $tableName;

    private ?string $rawFromExpression;

    public function __construct(string $tableName, array $schema, ?string $rawFromExpression = null)
    {
        $this->tableName = $tableName;
        $this->schema = $schema;
        $this->rawFromExpression = $rawFromExpression;
        $this->columnsByField = [];
        foreach ($schema['columns'] ?? [] as $col) {
            if (! empty($col['source_field'])) {
                $this->columnsByField[$col['source_field']] = $col;
            }
        }
    }

    /**
     * @param array<string, AggregationInterface> $aggregations
     * @return Plan[]
     *
     * @throws RuntimeException for aggregation shapes the compiler does not
     *         yet support; the executor turns these into user-visible
     *         errors rather than silently producing wrong results.
     */
    public function compile(array $aggregations): array
    {
        $plans = [];
        foreach ($aggregations as $agg) {
            if ($agg instanceof Terms) {
                $plans[] = $this->compileGroup($agg);
                continue;
            }
            if ($agg instanceof Metric) {
                $plans[] = $this->compileRootMetric($agg);
                continue;
            }
            if ($agg instanceof Formula) {
                $plans[] = $this->compileRootFormula($agg);
                continue;
            }
            throw new RuntimeException(sprintf(
                'AggregationSqlCompiler does not support aggregation kind "%s" (node "%s").',
                $agg->getKind(),
                $agg->getName()
            ));
        }
        return $plans;
    }

    private function compileGroup(Terms $terms): Plan
    {
        $field = $terms->getField();
        if ($field === null || $field === '') {
            throw new RuntimeException(sprintf('Group "%s" must have a field= attribute.', $terms->getName()));
        }

        $innerTerms = null;
        foreach ($terms->getChildren() as $child) {
            if ($child instanceof Terms) {
                if ($innerTerms !== null) {
                    throw new RuntimeException(
                        'Multiple sibling nested {group} aggregations are not supported by the temp-table tier.'
                    );
                }
                $innerTerms = $child;
            }
        }
        if ($innerTerms !== null) {
            return $this->compileNestedGroup($terms, $innerTerms);
        }

        $keyColumn = $this->columnFor($field, $terms->getName());
        $metricChildren = $this->collectMetricChildren($terms);
        [$aggFormulas, $postAggFormulas] = $this->collectFormulaChildren($terms);

        $perKeyAllowed = $this->columnIdentifierSet();
        foreach ($metricChildren as $name => $_m) {
            $perKeyAllowed[strtolower($name)] = true;
        }
        foreach ($aggFormulas as $name => $_f) {
            $perKeyAllowed[strtolower($name)] = true;
        }

        $size = $terms->getSize();
        $orderExpr = $this->buildPerKeyOrder($terms, $metricChildren);

        $perKeySelect = [
            sprintf('%s AS %s', $this->q($keyColumn), $this->q('__key__')),
            sprintf('COUNT(*) AS %s', $this->q('__doc_count__')),
        ];
        foreach ($metricChildren as $name => $metric) {
            $perKeySelect[] = sprintf(
                '%s AS %s',
                $this->metricExpression($metric),
                $this->q($name)
            );
        }
        foreach ($aggFormulas as $name => $formula) {
            $perKeySelect[] = sprintf(
                '(%s) AS %s',
                $this->validateUserExpression(
                    $formula->getExpression(),
                    $formula->getIdentifiers(),
                    $perKeyAllowed,
                    sprintf('formula metric "%s"', $name)
                ),
                $this->q($name)
            );
        }
        $perKeySelect[] = sprintf(
            'ROW_NUMBER() OVER (ORDER BY %s) AS %s',
            $orderExpr,
            $this->q('__rn__')
        );

        $havingClause = '';
        $having = $terms->getHaving();
        if ($having !== null) {
            $havingExpr = $this->validateUserExpression(
                $having,
                $terms->getHavingIdentifiers(),
                $perKeyAllowed,
                sprintf('{having} on group "%s"', $terms->getName())
            );
            $havingClause = "\n  HAVING " . $havingExpr;
        }

        $perKeyCte = sprintf(
            "per_key AS (\n  SELECT %s\n  FROM %s\n  GROUP BY %s%s\n)",
            implode(', ', $perKeySelect),
            $this->fromClause(),
            $this->q($keyColumn),
            $havingClause
        );

        $params = [];
        $labelBranches = [];

        $aggFormulaNames = array_keys($aggFormulas);

        $normalCols = [
            $this->q('__key__'),
            $this->q('__doc_count__'),
        ];
        foreach (array_keys($metricChildren) as $name) {
            $normalCols[] = $this->q($name);
        }
        foreach ($aggFormulaNames as $name) {
            $normalCols[] = $this->q($name);
        }
        $labelBranches[] = sprintf(
            "  SELECT %s,\n         'normal' AS %s, 0 AS %s, %s AS %s\n  FROM per_key%s",
            implode(', ', $normalCols),
            $this->q('__bucket_kind__'),
            $this->q('__kord__'),
            $this->q('__rn__'),
            $this->q('__sortkey__'),
            $size > 0 ? "\n  WHERE " . $this->q('__rn__') . ' <= ' . (int) $size : ''
        );

        if ($terms->withOthers() && $size > 0) {
            $othersCols = [
                $this->qLiteralPlaceholder() . ' AS ' . $this->q('__key__'),
                'SUM(' . $this->q('__doc_count__') . ') AS ' . $this->q('__doc_count__'),
            ];
            foreach (array_keys($metricChildren) as $name) {
                $othersCols[] = sprintf('SUM(%s) AS %s', $this->q($name), $this->q($name));
            }
            foreach ($aggFormulaNames as $name) {
                $othersCols[] = sprintf('NULL AS %s', $this->q($name));
            }
            $params[] = $terms->getOthersLabel();
            $labelBranches[] = sprintf(
                "  SELECT %s,\n         'others' AS %s, 1 AS %s, 1000000 AS %s\n  FROM per_key WHERE %s > %d\n  HAVING COUNT(*) > 0",
                implode(', ', $othersCols),
                $this->q('__bucket_kind__'),
                $this->q('__kord__'),
                $this->q('__sortkey__'),
                $this->q('__rn__'),
                (int) $size
            );
        }

        if ($terms->withTotal()) {
            $totalCols = [
                $this->qLiteralPlaceholder() . ' AS ' . $this->q('__key__'),
                'SUM(' . $this->q('__doc_count__') . ') AS ' . $this->q('__doc_count__'),
            ];
            foreach (array_keys($metricChildren) as $name) {
                $totalCols[] = sprintf('SUM(%s) AS %s', $this->q($name), $this->q($name));
            }
            foreach ($aggFormulaNames as $name) {
                $totalCols[] = sprintf('NULL AS %s', $this->q($name));
            }
            $params[] = $terms->getTotalLabel();
            $labelBranches[] = sprintf(
                "  SELECT %s,\n         'total' AS %s, 2 AS %s, 2000000 AS %s\n  FROM per_key",
                implode(', ', $totalCols),
                $this->q('__bucket_kind__'),
                $this->q('__kord__'),
                $this->q('__sortkey__')
            );
        }

        $labelsCte = sprintf(
            "labels AS (\n%s\n)",
            implode("\n  UNION ALL\n", $labelBranches)
        );

        $outerCols = [
            $this->q('__key__'),
            $this->q('__doc_count__'),
            $this->q('__bucket_kind__'),
        ];
        $aliases = [
            '__key__' => '__key__',
            '__doc_count__' => '__doc_count__',
            '__bucket_kind__' => '__bucket_kind__',
        ];
        foreach (array_keys($metricChildren) as $name) {
            $outerCols[] = $this->q($name);
            $aliases[$name] = $name;
        }
        foreach (array_keys($aggFormulas) as $name) {
            $outerCols[] = $this->q($name);
            $aliases[$name] = $name;
        }
        $outerAllowed = [
            '__key__' => true, '__doc_count__' => true, '__bucket_kind__' => true,
        ];
        foreach (array_keys($metricChildren) as $name) {
            $outerAllowed[strtolower($name)] = true;
        }
        foreach (array_keys($aggFormulas) as $name) {
            $outerAllowed[strtolower($name)] = true;
        }
        foreach ($postAggFormulas as $name => $formula) {
            $outerCols[] = sprintf(
                '(%s) AS %s',
                $this->validateUserExpression(
                    $formula->getExpression(),
                    $formula->getIdentifiers(),
                    $outerAllowed,
                    sprintf('formula metric "%s"', $name)
                ),
                $this->q($name)
            );
            $aliases[$name] = $name;
        }

        $sql = sprintf(
            "WITH %s,\n%s\nSELECT %s\nFROM labels\nORDER BY %s, %s",
            $perKeyCte,
            $labelsCte,
            implode(",\n       ", $outerCols),
            $this->q('__kord__') . ' ASC',
            $this->q('__sortkey__') . ' ASC'
        );

        return new Plan(
            kind: Plan::KIND_GROUP_BUCKETS,
            aggregationPath: [$terms->getName()],
            sql: $sql,
            params: $params,
            columnAliases: $aliases,
        );
    }

    private function compileNestedGroup(
        Terms $outer,
        Terms $inner
    ): Plan {
        $outerField = $outer->getField();
        $innerField = $inner->getField();
        if ($innerField === null || $innerField === '') {
            throw new RuntimeException(sprintf('Nested group "%s" must have a field= attribute.', $inner->getName()));
        }

        $outerCol = $this->columnFor($outerField, $outer->getName());
        $innerCol = $this->columnFor($innerField, $inner->getName());

        $metricChildren = $this->collectMetricChildren($inner);

        $selectCols = [
            sprintf('%s AS %s', $this->q($outerCol), $this->q('__outer_key__')),
            sprintf('%s AS %s', $this->q($innerCol), $this->q('__inner_key__')),
            sprintf('COUNT(*) AS %s', $this->q('__doc_count__')),
        ];
        $aliases = [
            '__outer_key__' => '__outer_key__',
            '__inner_key__' => '__inner_key__',
            '__doc_count__' => '__doc_count__',
        ];
        foreach ($metricChildren as $name => $metric) {
            $selectCols[] = sprintf('%s AS %s', $this->metricExpression($metric), $this->q($name));
            $aliases[$name] = $name;
        }

        $sql = sprintf(
            "SELECT %s\nFROM %s\nGROUP BY %s, %s\nORDER BY %s ASC, %s ASC",
            implode(', ', $selectCols),
            $this->fromClause(),
            $this->q($outerCol),
            $this->q($innerCol),
            $this->q($outerCol),
            $this->q($innerCol)
        );

        return new Plan(
            kind: Plan::KIND_NESTED_GROUP_BUCKETS,
            aggregationPath: [$outer->getName(), $inner->getName()],
            sql: $sql,
            params: [],
            columnAliases: $aliases,
            meta: [
                'innerAggName' => $inner->getName(),
                'innerAggField' => $innerField,
            ],
        );
    }

    private function compileRootMetric(Metric $metric): Plan
    {
        $expr = $this->metricExpression($metric);
        $alias = $metric->getName();

        $sql = sprintf(
            'SELECT %s AS %s FROM %s',
            $expr,
            $this->q($alias),
            $this->fromClause()
        );

        return new Plan(
            kind: Plan::KIND_METRIC_ROOT,
            aggregationPath: [$metric->getName()],
            sql: $sql,
            params: [],
            columnAliases: [$alias => $alias],
        );
    }

    private function compileRootFormula(Formula $formula): Plan
    {
        if (! $formula->isAggregate()) {
            throw new RuntimeException(sprintf(
                'Root-level {metric op="formula"} "%s" must contain an aggregate function (SUM, AVG, COUNT, ...) - non-aggregate formulas only make sense inside a {group} block where they reference sibling metric aliases.',
                $formula->getName()
            ));
        }
        $allowed = $this->columnIdentifierSet();
        $expr = $this->validateUserExpression(
            $formula->getExpression(),
            $formula->getIdentifiers(),
            $allowed,
            sprintf('root formula metric "%s"', $formula->getName())
        );
        $alias = $formula->getName();
        $sql = sprintf(
            'SELECT (%s) AS %s FROM %s',
            $expr,
            $this->q($alias),
            $this->fromClause()
        );
        return new Plan(
            kind: Plan::KIND_METRIC_ROOT,
            aggregationPath: [$formula->getName()],
            sql: $sql,
            params: [],
            columnAliases: [$alias => $alias],
        );
    }

    private function metricExpression(Metric $metric): string
    {
        $kind = $metric->getKind();
        $field = $metric->getField();

        if ($kind === Metric::KIND_COUNT && ($field === null || $field === '')) {
            return 'COUNT(*)';
        }

        if ($field === null || $field === '') {
            throw new RuntimeException(sprintf(
                'Metric "%s" of kind "%s" requires a field.',
                $metric->getName(),
                $kind
            ));
        }
        $col = $this->q($this->columnFor($field, $metric->getName()));

        switch ($kind) {
            case Metric::KIND_SUM:
                return 'SUM(' . $col . ')';
            case Metric::KIND_AVG:
                return 'AVG(' . $col . ')';
            case Metric::KIND_MIN:
                return 'MIN(' . $col . ')';
            case Metric::KIND_MAX:
                return 'MAX(' . $col . ')';
            case Metric::KIND_COUNT:
                return 'COUNT(' . $col . ')';
            case Metric::KIND_CARDINALITY:
                return 'COUNT(DISTINCT ' . $col . ')';
            case Metric::KIND_STATS:
                return sprintf(
                    'JSON_OBJECT(%s, COUNT(%s), %s, MIN(%s), %s, MAX(%s), %s, AVG(%s), %s, SUM(%s))',
                    $this->qStr('count'),
                    $col,
                    $this->qStr('min'),
                    $col,
                    $this->qStr('max'),
                    $col,
                    $this->qStr('avg'),
                    $col,
                    $this->qStr('sum'),
                    $col,
                );
        }
        throw new RuntimeException(sprintf('Unhandled metric kind "%s" in SQL compiler.', $kind));
    }

    /**
     * @param array<string, Metric> $metrics
     */
    private function buildPerKeyOrder(Terms $terms, array $metrics): string
    {
        $orderBy = $terms->getOrderBy();
        if ($orderBy === []) {
            return 'COUNT(*) DESC';
        }
        $parts = [];
        foreach ($orderBy as $field => $dir) {
            $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';
            if ($field === Terms::ORDER_KEY) {
                $parts[] = $this->q($this->columnFor((string) $terms->getField(), $terms->getName())) . ' ' . $dir;
                continue;
            }
            if ($field === Terms::ORDER_COUNT) {
                $parts[] = 'COUNT(*) ' . $dir;
                continue;
            }
            if (! isset($metrics[$field])) {
                throw new RuntimeException(sprintf(
                    'Group "%s" orders by metric "%s" which is not declared in the same group.',
                    $terms->getName(),
                    $field
                ));
            }
            $parts[] = $this->metricExpression($metrics[$field]) . ' ' . $dir;
        }
        return implode(', ', $parts);
    }

    /**
     * @return array<string, Metric>
     */
    private function collectMetricChildren(Terms $terms): array
    {
        $out = [];
        foreach ($terms->getChildren() as $child) {
            if ($child instanceof Metric) {
                $out[$child->getName()] = $child;
            }
        }
        return $out;
    }

    /**
     * @return array{0: array<string, Formula>, 1: array<string, Formula>}
     */
    private function collectFormulaChildren(Terms $terms): array
    {
        $agg = [];
        $post = [];
        foreach ($terms->getChildren() as $child) {
            if ($child instanceof Formula) {
                if ($child->isAggregate()) {
                    $agg[$child->getName()] = $child;
                } else {
                    $post[$child->getName()] = $child;
                }
            }
        }
        return [$agg, $post];
    }

    /**
     * @return array<string, true> case-insensitive set of temp-table
     *         column names available at per_key scope.
     */
    private function columnIdentifierSet(): array
    {
        $set = [];
        foreach ($this->columnsByField as $col) {
            $set[strtolower($col['name'])] = true;
        }
        return $set;
    }

    private function validateUserExpression(
        string $expression,
        array $extracted,
        array $allowed,
        string $context
    ): string {
        foreach ($extracted as $ident) {
            if (! isset($allowed[strtolower($ident)])) {
                throw new RuntimeException(sprintf(
                    'Identifier "%s" referenced in %s is not a known temp-table column or sibling alias. Available: %s',
                    $ident,
                    $context,
                    implode(', ', array_keys($allowed))
                ));
            }
        }
        return $expression;
    }

    private function columnFor(string $sourceField, string $contextName): string
    {
        if (! isset($this->columnsByField[$sourceField])) {
            throw new RuntimeException(sprintf(
                'Field "%s" referenced by aggregation "%s" is not present in the inferred schema. This is a bug in the projection or schema inference.',
                $sourceField,
                $contextName
            ));
        }
        return $this->columnsByField[$sourceField]['name'];
    }

    private function fromClause(): string
    {
        if ($this->rawFromExpression !== null) {
            return $this->rawFromExpression;
        }
        return $this->q($this->tableName);
    }

    private function q(string $ident): string
    {
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $ident)) {
            throw new RuntimeException(sprintf(
                'Refusing to quote unsafe identifier "%s" - schema/compiler invariant violated.',
                $ident
            ));
        }
        return '`' . $ident . '`';
    }

    private function qStr(string $literal): string
    {
        return "'" . str_replace("'", "''", $literal) . "'";
    }

    private function qLiteralPlaceholder(): string
    {
        return '?';
    }
}
