<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

use Search\Query\Aggregation\AggregationInterface;
use Search\Query\Aggregation\Metric;
use Search\Query\Aggregation\Terms;

/**
 * Translates a {@see AggregationInterface} tree into SphinxQL.
 *
 * Two output modes:
 *
 *  1. **FACET** ({@see build()}) — for aggregations without explicit
 *     metrics at the first child level (pure doc-count facets as well as
 *     deeper-nested metrics that `aggregationHasMetrics()` does not
 *     detect). The FACET clauses are appended to the main SELECT and
 *     parsed from the extra rowsets returned by `nextRowset()`.
 *
 *  2. **SELECT … GROUP BY** ({@see buildGroupByQueries()}) — for
 *     aggregations that carry explicit metrics (`SUM`, `AVG`, …) which
 *     SphinxQL FACET syntax cannot express. Each aggregation becomes a
 *     standalone query executed via {@see PdoClient::fetchAll()}.
 *
 * {@see Index::find()} picks the right mode per-aggregation so simple
 * doc-count facets stay on the cheap FACET path while metric-bearing
 * aggregations get a full GROUP BY without falling back to the MySQL
 * temp-table tier.
 *
 * Nested groups (e.g. region -> vendor) are emitted as a multi-column GROUP
 * BY in a single FACET; the matching {@see AggregationReader} folds the flat
 * rows back into a nested bucket tree on the way out.
 *
 * Arbitrary nesting depth is supported: nested Terms children are
 * flattened into a single multi-column GROUP BY / FACET.  Per-bucket
 * top-N on inner Terms levels is not supported — only the outermost
 * size/LIMIT applies.  If a field cannot be resolved (missing from the
 * index), that aggregation branch is silently skipped and the reader
 * produces no result for it.
 */
class AggregationBuilder
{
    private const ROOT_METRICS_FACET = '__metrics_root__';

    public function __construct(private readonly Index $index)
    {
    }

    /**
     * @param AggregationInterface[] $aggregations
     * @return array{
     *     clauses: string,
     *     specs: array<string, array{kind: string, agg: AggregationInterface, group_fields?: array<int, array{expr: string, alias: string, agg: Terms}>}>
     * }
     */
    public function build(array $aggregations): array
    {
        $clauses = [];
        $specs = [];
        $rootMetrics = [];

        foreach ($aggregations as $agg) {
            if ($agg instanceof Metric) {
                $rootMetrics[] = $agg;
                continue;
            }
            if ($agg instanceof Terms) {
                $built = $this->buildTerms($agg);
                if ($built !== null) {
                    $clauses[] = $built['clause'];
                    $specs[$agg->getName()] = $built['spec'];
                }
                continue;
            }
            // unknown aggregation kind: skip silently (graceful degradation)
        }

        if (! empty($rootMetrics)) {
            $built = $this->buildRootMetrics($rootMetrics);
            $clauses[] = $built['clause'];
            $specs[self::ROOT_METRICS_FACET] = $built['spec'];
        }

        return [
            'clauses' => implode(' ', $clauses),
            'specs' => $specs,
        ];
    }

    /**
     * Build standalone `SELECT … GROUP BY` queries for aggregations that
     * carry explicit metrics (SUM, AVG, COUNT-with-field, etc.) which
     * SphinxQL FACET syntax cannot express.
     *
     * @param AggregationInterface[] $aggregations
     * @return array{queries: list<array{sql: string, spec: array}>, specs: array<string, array>}
     */
    public function buildGroupByQueries(array $aggregations, string $table, string $condition, array $selectExpressions = []): array
    {
        $queries = [];
        $specs = [];
        $rootMetrics = [];

        foreach ($aggregations as $agg) {
            if ($agg instanceof Metric) {
                $rootMetrics[] = $agg;
                continue;
            }
            if ($agg instanceof Terms) {
                $built = $this->buildTermsGroupBy($agg, $table, $condition, $selectExpressions);
                if ($built !== null) {
                    $queries[] = $built;
                    $specs[$agg->getName()] = $built['spec'];
                }
                continue;
            }
        }

        if (! empty($rootMetrics)) {
            $built = $this->buildRootMetricsGroupBy($rootMetrics, $table, $condition, $selectExpressions);
            $queries[] = $built;
            $specs[self::ROOT_METRICS_FACET] = $built['spec'];
        }

        return ['queries' => $queries, 'specs' => $specs];
    }

    /**
     * Check whether a single aggregation needs the GROUP BY path
     * (i.e. it has metric children or is itself a root metric).
     */
    public static function aggregationHasMetrics(AggregationInterface $agg): bool
    {
        if ($agg instanceof Metric) {
            return true;
        }
        if ($agg instanceof Terms) {
            foreach ($agg->getChildren() as $child) {
                if ($child instanceof Metric) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Build a single FACET line for a Terms aggregation, walking any
     * depth of nested Terms children (collapsed into a multi-column
     * GROUP BY).
     *
     * @return array{clause: string, spec: array}|null  null if the agg cannot be expressed
     */
    private function buildTerms(Terms $agg): ?array
    {
        $groupFields = [];
        $g = $this->buildGroupColumn($agg);
        if ($g === null) {
            return null;
        }
        $groupFields[] = $g;

        // Walk all nested Terms levels, flattening them into a single FACET
        // with a composite key. Only one Terms child per level is followed;
        // additional sibling Terms at the same depth are ignored.
        $childMetrics = [];
        $current = $agg;
        while (true) {
            $nextTerms = null;
            foreach ($current->getChildren() as $child) {
                if ($child instanceof Metric) {
                    $childMetrics[$child->getName()] = $child;
                } elseif ($child instanceof Terms) {
                    if ($nextTerms !== null) {
                        // multiple sibling Terms aggs at the same level are
                        // not expressible as one Manticore FACET; skip the rest
                        break;
                    }
                    $nextTerms = $child;
                }
            }
            if ($nextTerms === null) {
                break;
            }
            $g = $this->buildGroupColumn($nextTerms);
            if ($g === null) {
                break;
            }
            $groupFields[] = $g;
            $current = $nextTerms;
        }

        $select = [];
        $aliasNames = [];
        foreach ($groupFields as $gf) {
            $select[] = $gf['expr'] . ' AS ' . $gf['alias'];
            $aliasNames[] = $gf['alias'];
        }
        // doc_count is always available as count(*)
        foreach ($childMetrics as $metric) {
            $select[] = $this->buildMetricExpr($metric) . ' AS ' . $this->quoteIdent($metric->getName());
        }

        $orderClause = $this->buildOrderClause($agg, $childMetrics, $aliasNames[0]);
        $size = $agg->getSize() > 0 ? $agg->getSize() : 10;

        $clause = 'FACET ' . implode(', ', $select)
            . ' BY ' . implode(', ', $aliasNames)
            . ' ' . $orderClause
            . ' LIMIT ' . (int) $size;

        return [
            'clause' => $clause,
            'spec' => [
                'kind' => 'terms',
                'agg' => $agg,
                'group_fields' => $groupFields,
                'metrics' => $childMetrics,
            ],
        ];
    }

    /**
     * Build a FACET that folds a list of root-level metric aggregations into
     * a single one-row rowset. Manticore SphinxQL does not allow plain
     * scalar aggregates without GROUP BY in the same SELECT as a regular
     * paginated fetch, so we fake a constant grouping key.
     *
     * @param Metric[] $metrics
     */
    private function buildRootMetrics(array $metrics): array
    {
        $select = ['0 AS ' . self::ROOT_METRICS_FACET];
        foreach ($metrics as $metric) {
            $select[] = $this->buildMetricExpr($metric) . ' AS ' . $this->quoteIdent($metric->getName());
        }
        return [
            'clause' => 'FACET ' . implode(', ', $select) . ' ORDER BY 1 ASC LIMIT 1',
            'spec' => [
                'kind' => 'metrics',
                'metrics' => $metrics,
            ],
        ];
    }

    /**
     * Build a SELECT … GROUP BY query for a Terms aggregation with metrics.
     *
     * @return array{sql: string, spec: array}|null
     */
    private function buildTermsGroupBy(Terms $agg, string $table, string $condition, array $selectExpressions = []): ?array
    {
        $groupFields = [];
        $g = $this->buildGroupColumn($agg);
        if ($g === null) {
            return null;
        }
        $groupFields[] = $g;

        $childMetrics = [];
        $current = $agg;
        while (true) {
            $nextTerms = null;
            foreach ($current->getChildren() as $child) {
                if ($child instanceof Metric) {
                    $childMetrics[$child->getName()] = $child;
                } elseif ($child instanceof Terms) {
                    if ($nextTerms !== null) {
                        break;
                    }
                    $nextTerms = $child;
                }
            }
            if ($nextTerms === null) {
                break;
            }
            $g = $this->buildGroupColumn($nextTerms);
            if ($g === null) {
                break;
            }
            $groupFields[] = $g;
            $current = $nextTerms;
        }

        $select = [];
        $aliasNames = [];
        foreach ($groupFields as $gf) {
            $select[] = $gf['expr'] . ' AS ' . $gf['alias'];
            $aliasNames[] = $gf['alias'];
        }
        foreach ($childMetrics as $metric) {
            $select[] = $this->buildMetricExpr($metric) . ' AS ' . $this->quoteIdent($metric->getName());
        }
        $select[] = 'COUNT(*)';
        foreach ($selectExpressions as $key => $expr) {
            $select[] = "$expr AS $key";
        }

        $orderClause = $this->buildOrderClause($agg, $childMetrics, $aliasNames[0]);
        $size = $agg->getSize() > 0 ? $agg->getSize() : 10;

        $sql = 'SELECT ' . implode(', ', $select)
            . ' FROM ' . $table
            . ' WHERE ' . $condition
            . ' GROUP BY ' . implode(', ', $aliasNames)
            . ' ' . $orderClause
            . ' LIMIT ' . (int) $size
            . ' option not_terms_only_allowed=1,cutoff=0,expand_keywords=1';

        $spec = [
            'kind' => 'terms',
            'agg' => $agg,
            'group_fields' => $groupFields,
            'metrics' => $childMetrics,
        ];

        return ['sql' => $sql, 'spec' => $spec];
    }

    /**
     * Build a SELECT query for root-level metrics (no grouping).
     *
     * @param Metric[] $metrics
     * @return array{sql: string, spec: array}
     */
    private function buildRootMetricsGroupBy(array $metrics, string $table, string $condition, array $selectExpressions = []): array
    {
        $select = ['0 AS ' . self::ROOT_METRICS_FACET];
        foreach ($metrics as $metric) {
            $select[] = $this->buildMetricExpr($metric) . ' AS ' . $this->quoteIdent($metric->getName());
        }
        foreach ($selectExpressions as $key => $expr) {
            $select[] = "$expr AS $key";
        }

        $sql = 'SELECT ' . implode(', ', $select)
            . ' FROM ' . $table
            . ' WHERE ' . $condition
            . ' LIMIT 1'
            . ' option not_terms_only_allowed=1,cutoff=0,expand_keywords=1';

        return [
            'sql' => $sql,
            'spec' => [
                'kind' => 'metrics',
                'metrics' => $metrics,
            ],
        ];
    }

    /**
     * Resolve a Terms aggregation field into the SphinxQL expression and
     * alias used both in the SELECT projection and as the bucket key in the
     * returned rowset.
     *
     * @return array{expr: string, alias: string, agg: Terms}|null
     */
    private function buildGroupColumn(Terms $agg): ?array
    {
        $field = $agg->getField();
        if (! $field) {
            return null;
        }
        if ($this->index->isFieldInJson($field)) {
            $expr = $this->index->getJsonPathForField($field);
        } else {
            $expr = strtolower($field);
            try {
                $this->index->ensureHasField($expr);
            } catch (\Exception $e) {
                return null;
            }
        }
        return [
            'expr' => $expr,
            'alias' => $this->quoteIdent($agg->getName()),
            'agg' => $agg,
        ];
    }

    /**
     * Resolve a metric field into the appropriate SphinxQL aggregate
     * expression, picking the numeric companion (`*_nsort`) when the source
     * column is a string attribute, and casting JSON-stored values.
     */
    private function buildMetricExpr(Metric $metric): string
    {
        $kind = $metric->getKind();
        $field = $metric->getField();

        if ($kind === Metric::KIND_COUNT && ($field === null || $field === '')) {
            return 'COUNT(*)';
        }
        $column = $this->resolveNumericColumn($field, $kind);

        switch ($kind) {
            case Metric::KIND_SUM:
                return 'SUM(' . $column . ')';
            case Metric::KIND_AVG:
                return 'AVG(' . $column . ')';
            case Metric::KIND_MIN:
                return 'MIN(' . $column . ')';
            case Metric::KIND_MAX:
                return 'MAX(' . $column . ')';
            case Metric::KIND_COUNT:
                return 'COUNT(*)';
        }
        // stats and cardinality are routed to the temp-table tier by
        // TierSelector and never reach this builder.
        return 'COUNT(*)';
    }

    /**
     * Pick the right SphinxQL numeric expression for $field. Mirrors the
     * conventions in OrderBuilder so behaviour stays consistent with sorts.
     */
    private function resolveNumericColumn(?string $field, string $kind): string
    {
        if ($field === null || $field === '') {
            return '1';
        }
        if ($this->index->isFieldInJson($field)) {
            return 'DOUBLE(' . $this->index->getJsonPathForField($field) . ')';
        }
        $lower = strtolower($field);
        $mapping = $this->index->getFieldMapping($lower);
        if (! empty($mapping['types']) && (in_array('float', $mapping['types'], true) || in_array('int', $mapping['types'], true))) {
            return $lower;
        }
        // string/text attribute -> use the float companion the indexer adds
        // for numeric fields. If absent, the column likely isn't numeric and
        // the aggregation will silently degrade.
        $nsort = $lower . '_nsort';
        $nmap = $this->index->getFieldMapping($nsort);
        if (! empty($nmap)) {
            return $nsort;
        }
        return $lower;
    }

    /**
     * Build the ORDER BY clause for a Terms FACET. Defaults to bucket count
     * desc; supports `_count`, `_key` and any of the local metric aliases.
     *
     * @param Metric[] $metrics
     */
    private function buildOrderClause(
        Terms $agg,
        array $metrics,
        string $keyAlias
    ): string {
        $orderBy = $agg->getOrderBy();
        if (empty($orderBy)) {
            return 'ORDER BY COUNT(*) DESC';
        }
        $field = (string) array_key_first($orderBy);
        $dir = strtoupper($orderBy[$field]) === 'ASC' ? 'ASC' : 'DESC';
        if ($field === Terms::ORDER_COUNT) {
            return 'ORDER BY COUNT(*) ' . $dir;
        }
        if ($field === Terms::ORDER_KEY) {
            return 'ORDER BY ' . $keyAlias . ' ' . $dir;
        }
        if (isset($metrics[$field])) {
            return 'ORDER BY ' . $this->quoteIdent($field) . ' ' . $dir;
        }
        return 'ORDER BY COUNT(*) DESC';
    }

    /**
     * SphinxQL doesn't support quoted identifiers; aliases must be valid
     * unquoted names. We sanitise to a safe character set, since the alias
     * comes from the user-provided agg/metric `name`.
     */
    private function quoteIdent(string $name): string
    {
        $sanitised = preg_replace('/[^A-Za-z0-9_]/', '_', $name);
        if ($sanitised === '' || preg_match('/^[0-9]/', $sanitised)) {
            $sanitised = '_' . $sanitised;
        }
        return $sanitised;
    }
}
