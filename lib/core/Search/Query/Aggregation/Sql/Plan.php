<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Query\Aggregation\Sql;

use InvalidArgumentException;

/**
 * One executable SQL step in the temp-table tier's aggregation plan.
 *
 * The compiler produces one or more Plan steps per top-level aggregation,
 * each tagged with a {@see self::KIND_*} so the executor knows how to fold the
 * resulting rows back into a {@see \Search\ResultSet\AggregationResult}.
 *
 * Steps fall into two families today:
 *
 *  - composite bucket step (KIND_GROUP_BUCKETS)
 *      one CTE-based SELECT that returns the entire bucket list for a
 *      top-level Terms node, including any synthetic Others / Total rows
 *      and any derived metric columns.
 *
 *  - root metric step (KIND_METRIC_ROOT)
 *      one scalar SELECT that returns the value of a root-level Metric.
 *
 * Nested-group plans use KIND_NESTED_GROUP_BUCKETS: a flat GROUP BY
 * over both key columns, with the executor pivoting into a tree.
 *
 * The plan is purely declarative: it captures SQL string + bind params + the
 * aggregation-tree path the rows belong to. No execution happens here.
 *
 * Plans are immutable once compiled, which keeps the compiler easy to test
 * (assert against the plan) and the executor easy to reason about (no
 * compilation surprises mid-flight).
 */
final class Plan
{
    /**
     * One composite SELECT that returns the entire bucket list for a
     * top-level {group} - normal Top-N rows, the synthetic Others row
     * (when withOthers=y) and the synthetic Total row (when withTotal=y).
     * Implemented with MySQL 8 CTEs + window functions in {@see Compiler}.
     */
    public const KIND_GROUP_BUCKETS = 'group_buckets';

    /**
     * One scalar SELECT that returns a single root-level metric value.
     */
    public const KIND_METRIC_ROOT = 'metric_root';

    /**
     * A flat SELECT that groups by two columns (outer key + inner key)
     * and returns one row per (outer, inner) combination. The executor
     * pivots this into a nested bucket tree: outer buckets with child
     * AggregationResults containing inner buckets.
     */
    public const KIND_NESTED_GROUP_BUCKETS = 'nested_group_buckets';

    public const VALID_KINDS = [
        self::KIND_GROUP_BUCKETS,
        self::KIND_METRIC_ROOT,
        self::KIND_NESTED_GROUP_BUCKETS,
    ];

    /**
     * @param string $kind one of self::KIND_*
     * @param string[] $aggregationPath ordered names of aggregation nodes
     *        from root to the target node, e.g. ['vendor','quarter'] for a
     *        nested group's TopN step.
     * @param string $sql parameterised SQL using ? placeholders
     * @param array<int, mixed> $params positional bind params for the SQL
     * @param array<string, string> $columnAliases mapping of result column
     *        alias -> the metric / key / system role it represents. System
     *        columns the executor recognises:
     *           '__key__'           bucket key
     *           '__bucket_kind__'   'normal' | 'others' | 'total'
     *           '__doc_count__'     COUNT(*) of source rows for the bucket
     *        Anything else is treated as a metric value using the alias
     *        as the metric name.
     */
    public function __construct(
        public readonly string $kind,
        public readonly array $aggregationPath,
        public readonly string $sql,
        public readonly array $params,
        public readonly array $columnAliases,
        public readonly array $meta = [],
    ) {
        if (! in_array($kind, self::VALID_KINDS, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown SQL plan kind "%s". Valid: %s',
                $kind,
                implode(', ', self::VALID_KINDS)
            ));
        }
        if ($aggregationPath === []) {
            throw new InvalidArgumentException('SQL plan must target at least one aggregation node');
        }
    }

    /**
     * Innermost target aggregation name (the node whose result this plan
     * contributes to).
     */
    public function targetName(): string
    {
        return $this->aggregationPath[count($this->aggregationPath) - 1];
    }

    /**
     * Root aggregation name — the top-level key in the aggregation map
     * that this plan belongs to. For flat plans this equals targetName();
     * for nested plans it is the outermost group.
     */
    public function rootName(): string
    {
        return $this->aggregationPath[0];
    }
}
