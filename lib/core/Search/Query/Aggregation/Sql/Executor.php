<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Query\Aggregation\Sql;

use LogicException;
use Search\Query\Aggregation\AggregationInterface;
use Search\Query\Aggregation\Metric;
use Search\Query\Aggregation\Sql\Schema\Inferrer;
use Search\Query\Aggregation\Terms;
use Search_MySql_Index;
use Search_Query;
use Search_ResultSet;
use Search\ResultSet\AggregationResult;
use TikiDb;

/**
 * Top-level executor for the temp-table aggregation tier.
 *
 * Given an aggregation tree, a Search_Query and an index backend, the
 * executor:
 *
 *   1. infers the temp-table schema (via {@see Inferrer})
 *   2. CREATE TABLE / scrolls the index hits into it (via
 *      {@see TempTablePopulator})
 *   3. compiles the aggregation tree to SQL plans (via {@see Compiler})
 *   4. runs each plan, reads rows back into a {@see AggregationResult} tree
 *   5. drops the temp table in a `finally` block, regardless of outcome
 */
class Executor
{
    private const KIND_TAG_PREFIX = 'bucket-';

    private const SYSTEM_COLUMNS = ['__key__', '__doc_count__', '__bucket_kind__'];

    private TikiDb $db;
    /** @var ?callable */
    private $fieldTypeResolver;

    /** @var string[] */
    private array $lastWarnings = [];

    public function __construct(TikiDb $db, ?callable $fieldTypeResolver = null)
    {
        $this->db = $db;
        $this->fieldTypeResolver = $fieldTypeResolver;
    }

    /**
     * @param array<string, AggregationInterface> $aggregations
     * @param Search_Query $query
     * @param mixed $index
     * @return array<string, AggregationResult>
     */
    public function execute(array $aggregations, Search_Query $query, $index): array
    {
        $this->lastWarnings = [];
        if ($aggregations === []) {
            return [];
        }

        $schema = (new Inferrer($this->fieldTypeResolver))->infer($aggregations);
        if (! empty($schema['warnings'])) {
            $this->lastWarnings = array_merge($this->lastWarnings, $schema['warnings']);
        }
        if (($schema['columns'] ?? []) === []) {
            return [];
        }

        if ($index instanceof Search_MySql_Index) {
            return $this->executeDirect($aggregations, $query, $index, $schema);
        }

        $populator = new TempTablePopulator($this->db, $schema);
        $tableName = $populator->create();
        try {
            $populator->populate($tableName, $query, $index);
            $compiler = new Compiler($tableName, $schema);
            $plans = $compiler->compile($aggregations);

            $results = [];
            foreach ($plans as $plan) {
                $aggName = $plan->rootName();
                $aggDef = $aggregations[$aggName] ?? null;
                if ($aggDef === null) {
                    continue;
                }
                $rows = $this->db->fetchAll($plan->sql, $plan->params);
                if ($rows === false) {
                    $rows = [];
                }
                $results[$aggName] = $this->buildResult($plan, $rows, $aggDef);
            }
            return $results;
        } finally {
            $populator->drop($tableName);
            $popWarnings = $populator->getWarnings();
            if ($popWarnings !== []) {
                $this->lastWarnings = array_merge($this->lastWarnings, $popWarnings);
            }
        }
    }

    private function executeDirect(
        array $aggregations,
        Search_Query $query,
        Search_MySql_Index $index,
        array $schema
    ): array {
        $table = $index->getTable();
        $builder = $index->getQueryBuilder();

        $baseTable = '`' . $table->getTableName() . '`';
        $joins = $table->getIndexTablesSqlJoins();

        $where = $builder->build($query->getExpr());
        $whereClause = $where !== '' ? ' WHERE ' . $where : '';

        $tfTranslator = new \Search_MySql_TrackerFieldTranslator();
        $selectCols = [];
        foreach ($schema['columns'] as $col) {
            $rawField = $tfTranslator->shortenize($col['source_field']);
            $indexCol = '`' . $rawField . '`';
            $schemaCol = '`' . $col['name'] . '`';

            $fieldType = $table->getFieldType($rawField);
            if ($fieldType !== null && stripos($fieldType, 'DATE') === 0) {
                $indexCol = 'UNIX_TIMESTAMP(' . $indexCol . ')';
            }

            if ($indexCol === $schemaCol) {
                $selectCols[] = $indexCol;
            } else {
                $selectCols[] = $indexCol . ' AS ' . $schemaCol;
            }
        }

        $fromExpr = sprintf(
            '(SELECT %s FROM %s%s%s) AS `_src`',
            implode(', ', $selectCols),
            $baseTable,
            $joins,
            $whereClause
        );

        $compiler = new Compiler('_src', $schema, $fromExpr);
        $plans = $compiler->compile($aggregations);

        $results = [];
        foreach ($plans as $plan) {
            $aggName = $plan->rootName();
            $aggDef = $aggregations[$aggName] ?? null;
            if ($aggDef === null) {
                continue;
            }
            $rows = $this->db->fetchAll($plan->sql, $plan->params);
            if ($rows === false) {
                $rows = [];
            }
            $results[$aggName] = $this->buildResult($plan, $rows, $aggDef);
        }
        return $results;
    }

    /**
     * @return string[]
     */
    public function getLastWarnings(): array
    {
        return $this->lastWarnings;
    }

    private function buildResult(
        Plan $plan,
        array $rows,
        AggregationInterface $aggDef
    ): AggregationResult {
        if ($plan->kind === Plan::KIND_METRIC_ROOT) {
            $result = new AggregationResult($aggDef->getName(), $aggDef->getKind(), false);
            $result->setField($aggDef->getField());
            $value = $rows[0][$aggDef->getName()] ?? null;
            $result->setValue($this->normaliseScalar($aggDef, $value));
            return $result;
        }

        if ($plan->kind === Plan::KIND_NESTED_GROUP_BUCKETS) {
            return $this->buildNestedResult($plan, $rows, $aggDef);
        }

        if (! ($aggDef instanceof Terms)) {
            throw new LogicException(sprintf(
                'AggregationSqlExecutor: plan of kind "%s" mapped to non-terms aggregation "%s".',
                $plan->kind,
                $aggDef->getName()
            ));
        }

        $result = new AggregationResult($aggDef->getName(), $aggDef->getKind(), true);
        $result->setField($aggDef->getField());
        $result->setLabel($aggDef->getLabel());

        $palette = $aggDef->getPalette();
        $othersColor = $aggDef->getOthersColor();
        $totalColor = $aggDef->getTotalColor();
        $normalIdx = 0;

        foreach ($rows as $row) {
            $kindRaw = (string) ($row['__bucket_kind__'] ?? 'normal');
            $kindTag = self::KIND_TAG_PREFIX . $kindRaw;

            $metrics = ['__bucket_kind__' => $kindTag];
            foreach ($row as $col => $val) {
                if (in_array($col, self::SYSTEM_COLUMNS, true)) {
                    continue;
                }
                $metrics[$col] = $this->coerceMetricValue($val);
            }

            $color = $this->resolveColor($kindRaw, $palette, $othersColor, $totalColor, $normalIdx);
            if ($color !== null) {
                $metrics['__bucket_color__'] = $color;
            }
            if ($kindRaw === 'normal') {
                $normalIdx++;
            }

            $result->addBucket([
                'key' => $row['__key__'] ?? null,
                'doc_count' => (int) ($row['__doc_count__'] ?? 0),
                'metrics' => $metrics,
            ]);
        }

        $labelMap = [];
        foreach ($aggDef->getChildren() as $child) {
            if ($child->getLabel() !== null) {
                $labelMap[$child->getName()] = $child->getLabel();
            }
        }
        if ($labelMap !== []) {
            $result->setMetricLabels($labelMap);
        }

        return $result;
    }

    private function buildNestedResult(
        Plan $plan,
        array $rows,
        AggregationInterface $aggDef
    ): AggregationResult {
        if (! ($aggDef instanceof Terms)) {
            throw new LogicException('Nested group plan mapped to non-terms aggregation.');
        }

        $innerAggName = $plan->meta['innerAggName'] ?? 'inner';
        $innerAggField = $plan->meta['innerAggField'] ?? null;
        $systemCols = ['__outer_key__', '__inner_key__', '__doc_count__'];

        $outerGroups = [];
        foreach ($rows as $row) {
            $outerKey = $row['__outer_key__'] ?? null;
            $outerGroups[$outerKey][] = $row;
        }

        $result = new AggregationResult($aggDef->getName(), $aggDef->getKind(), true);
        $result->setField($aggDef->getField());
        $result->setLabel($aggDef->getLabel());

        foreach ($outerGroups as $outerKey => $innerRows) {
            $innerResult = new AggregationResult($innerAggName, 'terms', true);
            if ($innerAggField !== null) {
                $innerResult->setField($innerAggField);
            }

            $outerDocCount = 0;
            foreach ($innerRows as $row) {
                $docCount = (int) ($row['__doc_count__'] ?? 0);
                $outerDocCount += $docCount;

                $metrics = [];
                foreach ($row as $col => $val) {
                    if (in_array($col, $systemCols, true)) {
                        continue;
                    }
                    $metrics[$col] = $this->coerceMetricValue($val);
                }

                $innerResult->addBucket([
                    'key' => $row['__inner_key__'] ?? null,
                    'doc_count' => $docCount,
                    'metrics' => $metrics,
                ]);
            }

            $result->addBucket([
                'key' => $outerKey,
                'doc_count' => $outerDocCount,
                'metrics' => [],
                'children' => [$innerAggName => $innerResult],
            ]);
        }

        return $result;
    }

    /**
     * @param string[] $palette
     */
    private function resolveColor(
        string $kindRaw,
        array $palette,
        ?string $othersColor,
        ?string $totalColor,
        int $normalIdx
    ): ?string {
        if ($kindRaw === 'others') {
            return $othersColor ?? ($palette === [] ? null : '#BAB0AC');
        }
        if ($kindRaw === 'total') {
            return $totalColor ?? ($palette === [] ? null : '#222222');
        }
        if ($palette === []) {
            return null;
        }
        return $palette[$normalIdx % count($palette)];
    }

    private function coerceMetricValue($value)
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value) && is_numeric($value)) {
            return $value + 0;
        }
        return $value;
    }

    private function normaliseScalar(AggregationInterface $aggDef, $value)
    {
        if ($value === null) {
            return null;
        }
        if (
            $aggDef instanceof Metric
            && $aggDef->getKind() === Metric::KIND_STATS
            && is_string($value)
        ) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return $this->coerceMetricValue($value);
    }
}
