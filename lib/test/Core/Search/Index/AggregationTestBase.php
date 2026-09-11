<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Index;

use Search\Query\Aggregation\AggregationInterface;
use Search\Query\Aggregation\Join\Join;
use Search\Query\Aggregation\Join\JoinResolver;
use Search\Query\Aggregation\Sql\Executor as AggregationSqlExecutor;
use Search\Query\Aggregation\Tier\TierSelector;
use Search\Query\Aggregation\WikiBuilder as AggregationWikiBuilder;
use Search\ResultSet\AggregationResult;

/**
 * Abstract integration test for the unified aggregation engine.
 *
 * Seeds a fixed dataset into the index, then verifies aggregation results
 * match expected values. Concrete subclasses provide engine-specific setup.
 */
abstract class AggregationTestBase extends \PHPUnit\Framework\TestCase
{
    protected $index;

    protected function tearDown(): void
    {
        if ($this->index) {
            $this->index->destroy();
        }
    }

    // ------------------------------------------------------------------
    // Data seeding
    // ------------------------------------------------------------------

    protected function populate($index): void
    {
        $tf = $index->getTypeFactory();

        $orders = [
            ['id' => '1', 'vendor_id' => 'v1', 'region' => 'EMEA', 'amount' => 100, 'quantity' => 10, 'status' => 'open',    'created' => '2026-01-01', 'closed' => '2026-01-11'],
            ['id' => '2', 'vendor_id' => 'v1', 'region' => 'EMEA', 'amount' => 200, 'quantity' => 5,  'status' => 'closed',  'created' => '2026-01-05', 'closed' => '2026-01-25'],
            ['id' => '3', 'vendor_id' => 'v2', 'region' => 'EMEA', 'amount' => 150, 'quantity' => 8,  'status' => 'open',    'created' => '2026-02-01', 'closed' => '2026-02-08'],
            ['id' => '4', 'vendor_id' => 'v2', 'region' => 'AMER', 'amount' => 300, 'quantity' => 12, 'status' => 'closed',  'created' => '2026-02-10', 'closed' => '2026-03-02'],
            ['id' => '5', 'vendor_id' => 'v3', 'region' => 'AMER', 'amount' => 50,  'quantity' => 3,  'status' => 'pending', 'created' => '2026-03-01', 'closed' => '2026-03-04'],
            ['id' => '6', 'vendor_id' => 'v3', 'region' => 'AMER', 'amount' => 75,  'quantity' => 7,  'status' => 'open',    'created' => '2026-03-10', 'closed' => '2026-03-24'],
            ['id' => '7', 'vendor_id' => 'v4', 'region' => 'APAC', 'amount' => 500, 'quantity' => 20, 'status' => 'closed',  'created' => '2026-04-01', 'closed' => '2026-04-16'],
            ['id' => '8', 'vendor_id' => 'v4', 'region' => 'APAC', 'amount' => 250, 'quantity' => 15, 'status' => 'open',    'created' => '2026-04-10', 'closed' => '2026-05-01'],
        ];

        foreach ($orders as $row) {
            $index->addDocument([
                'object_type' => $tf->identifier('trackeritem'),
                'object_id' => $tf->identifier($row['id']),
                'tracker_id' => $tf->identifier('10'),
                'title' => $tf->sortable('order ' . $row['id']),
                'vendor_id' => $tf->identifier($row['vendor_id']),
                'region' => $tf->identifier($row['region']),
                'amount' => $tf->numeric($row['amount']),
                'quantity' => $tf->numeric($row['quantity']),
                'status' => $tf->identifier($row['status']),
                'created_date' => $tf->timestamp(strtotime($row['created']), true),
                'closed_date' => $tf->timestamp(strtotime($row['closed']), true),
            ]);
        }
    }

    // ------------------------------------------------------------------
    // Wiki markup parsing helper
    // ------------------------------------------------------------------

    /**
     * Parse wiki aggregation markup into an aggregation tree, exactly as
     * wikiplugin_list does in production.
     *
     * @return array<string, AggregationInterface>
     */
    protected function parseWikiAggregations(string $markup): array
    {
        $matches = \WikiParser_PluginMatcher::match($markup);
        $builder = new AggregationWikiBuilder();
        $builder->apply($matches);
        return $builder->getAggregations();
    }

    // ------------------------------------------------------------------
    // Aggregation execution helper
    // ------------------------------------------------------------------

    /**
     * Run a search with aggregations using the same tier-selection logic
     * as wikiplugin_list.php:
     *
     *  1. AggregationTierSelector::select() picks engine vs temp-table
     *  2. If engine tier but index is MySQL, force temp-table
     *  3. Engine tier: attach aggregations to query, search
     *  4. Temp-table tier: search without aggs, then SQL executor
     *
     * @param array<string, AggregationInterface> $aggregations
     */
    protected function searchWithAggregations(array $aggregations, ?\Search_Query $query = null): \Search_ResultSet
    {
        if ($query === null) {
            $query = new \Search_Query();
            $query->filterType('trackeritem');
            $query->filterIdentifier('10', 'tracker_id');
        }

        $tier = (new TierSelector())->select(
            $aggregations,
            null,
            $this->index
        );

        if ($tier === TierSelector::TIER_ENGINE) {
            foreach ($aggregations as $agg) {
                $query->addAggregation($agg);
            }
            $result = $query->search($this->index);
        } else {
            $result = $query->search($this->index);
            $aggQuery = clone $query;
            $aggQuery->clearAggregations();
            $executor = new AggregationSqlExecutor(\TikiDb::get());
            foreach ($executor->execute($aggregations, $aggQuery, $this->index) as $aggResult) {
                $result->setAggregationResult($aggResult);
            }
        }

        return $result;
    }

    // ------------------------------------------------------------------
    // Assertion helpers
    // ------------------------------------------------------------------

    protected function assertBucketAggregation(
        AggregationResult $result,
        array $expectedBuckets,
        string $message = ''
    ): void {
        $this->assertTrue($result->isBucket(), $message ?: 'Expected a bucket aggregation');

        $actual = $result->getBuckets();

        $sortByKey = function (array &$buckets): void {
            usort($buckets, fn ($a, $b) => strcmp((string) $a['key'], (string) $b['key']));
        };

        $sortByKey($actual);
        $sortByKey($expectedBuckets);

        $this->assertCount(count($expectedBuckets), $actual, ($message ? "$message: " : '') . 'Bucket count mismatch');

        foreach ($expectedBuckets as $i => $expected) {
            $prefix = ($message ? "$message: " : '') . "Bucket #{$i} (key={$expected['key']})";
            $this->assertSame((string) $expected['key'], (string) $actual[$i]['key'], "$prefix key");

            if (isset($expected['doc_count'])) {
                $this->assertSame($expected['doc_count'], $actual[$i]['doc_count'], "$prefix doc_count");
            }
            if (isset($expected['metrics'])) {
                foreach ($expected['metrics'] as $metricName => $metricValue) {
                    $this->assertArrayHasKey($metricName, $actual[$i]['metrics'], "$prefix missing metric '$metricName'");
                    $this->assertEqualsWithDelta(
                        $metricValue,
                        $actual[$i]['metrics'][$metricName],
                        0.01,
                        "$prefix metric '$metricName'"
                    );
                }
            }
        }
    }

    protected function assertMetricResult(
        AggregationResult $result,
        $expectedValue,
        float $delta = 0.01,
        string $message = ''
    ): void {
        $this->assertFalse($result->isBucket(), $message ?: 'Expected a metric aggregation');
        if (is_array($expectedValue)) {
            foreach ($expectedValue as $k => $v) {
                $this->assertEqualsWithDelta($v, $result->getValue()[$k], $delta, "$message [$k]");
            }
        } else {
            $this->assertEqualsWithDelta($expectedValue, $result->getValue(), $delta, $message);
        }
    }

    /**
     * Assert bucket aggregation ignoring order and only checking listed
     * buckets (for Others/Total rows that have special kind tags).
     */
    protected function assertBucketAggregationWithSyntheticRows(
        AggregationResult $result,
        array $normalBuckets,
        ?array $othersExpected = null,
        ?array $totalExpected = null
    ): void {
        $this->assertTrue($result->isBucket());
        $buckets = $result->getBuckets();

        $normal = [];
        $others = null;
        $total = null;
        foreach ($buckets as $bucket) {
            $kind = $bucket['metrics']['__bucket_kind__'] ?? 'bucket-normal';
            if ($kind === 'bucket-others') {
                $others = $bucket;
            } elseif ($kind === 'bucket-total') {
                $total = $bucket;
            } else {
                $normal[] = $bucket;
            }
        }

        usort($normal, fn ($a, $b) => strcmp((string) $a['key'], (string) $b['key']));
        usort($normalBuckets, fn ($a, $b) => strcmp((string) $a['key'], (string) $b['key']));

        $this->assertCount(count($normalBuckets), $normal, 'Normal bucket count');
        foreach ($normalBuckets as $i => $expected) {
            $this->assertSame((string) $expected['key'], (string) $normal[$i]['key']);
            if (isset($expected['metrics'])) {
                foreach ($expected['metrics'] as $name => $val) {
                    $this->assertEqualsWithDelta($val, $normal[$i]['metrics'][$name], 0.01);
                }
            }
        }

        if ($othersExpected !== null) {
            $this->assertNotNull($others, 'Expected an Others bucket');
            if (isset($othersExpected['metrics'])) {
                foreach ($othersExpected['metrics'] as $name => $val) {
                    $this->assertEqualsWithDelta($val, $others['metrics'][$name], 0.01);
                }
            }
        }
        if ($totalExpected !== null) {
            $this->assertNotNull($total, 'Expected a Total bucket');
            if (isset($totalExpected['metrics'])) {
                foreach ($totalExpected['metrics'] as $name => $val) {
                    $this->assertEqualsWithDelta($val, $total['metrics'][$name], 0.01);
                }
            }
        }
    }

    // ------------------------------------------------------------------
    // Test cases
    // ------------------------------------------------------------------

    public function testTermsWithSumMetric(): void
    {
        $aggregations = $this->parseWikiAggregations(
            '{group field="vendor_id" name="vendor" size="10"}
             {metric name="rev" op="sum" field="amount"}'
        );

        $result = $this->searchWithAggregations($aggregations);
        $agg = $result->getAggregation('vendor');
        $this->assertNotNull($agg);

        $this->assertBucketAggregation($agg, [
            ['key' => 'v1', 'doc_count' => 2, 'metrics' => ['rev' => 300.0]],
            ['key' => 'v2', 'doc_count' => 2, 'metrics' => ['rev' => 450.0]],
            ['key' => 'v3', 'doc_count' => 2, 'metrics' => ['rev' => 125.0]],
            ['key' => 'v4', 'doc_count' => 2, 'metrics' => ['rev' => 750.0]],
        ]);
    }

    public function testTermsWithMultipleMetrics(): void
    {
        $aggregations = $this->parseWikiAggregations(
            '{group field="vendor_id" name="vendor" size="10"}
             {metric name="rev" op="sum" field="amount"}
             {metric name="orders" op="count"}
             {metric name="avg_qty" op="avg" field="quantity"}'
        );

        $result = $this->searchWithAggregations($aggregations);
        $agg = $result->getAggregation('vendor');
        $this->assertNotNull($agg);

        $this->assertBucketAggregation($agg, [
            ['key' => 'v1', 'doc_count' => 2, 'metrics' => ['rev' => 300.0, 'orders' => 2, 'avg_qty' => 7.5]],
            ['key' => 'v2', 'doc_count' => 2, 'metrics' => ['rev' => 450.0, 'orders' => 2, 'avg_qty' => 10.0]],
            ['key' => 'v3', 'doc_count' => 2, 'metrics' => ['rev' => 125.0, 'orders' => 2, 'avg_qty' => 5.0]],
            ['key' => 'v4', 'doc_count' => 2, 'metrics' => ['rev' => 750.0, 'orders' => 2, 'avg_qty' => 17.5]],
        ]);
    }

    public function testTopNOrderedByMetric(): void
    {
        $aggregations = $this->parseWikiAggregations(
            '{group field="vendor_id" name="vendor" size="2" order="rev desc"}
             {metric name="rev" op="sum" field="amount"}'
        );

        $result = $this->searchWithAggregations($aggregations);
        $agg = $result->getAggregation('vendor');
        $this->assertNotNull($agg);

        $buckets = $agg->getBuckets();
        $this->assertCount(2, $buckets);
        $this->assertSame('v4', (string) $buckets[0]['key']);
        $this->assertEqualsWithDelta(750.0, $buckets[0]['metrics']['rev'], 0.01);
        $this->assertSame('v2', (string) $buckets[1]['key']);
        $this->assertEqualsWithDelta(450.0, $buckets[1]['metrics']['rev'], 0.01);
    }

    public function testTermsWithOthersAndTotal(): void
    {
        $aggregations = $this->parseWikiAggregations(
            '{group field="vendor_id" name="vendor" size="2" order="rev desc" others="y" others_label="Others" total="y" total_label="Total"}
             {metric name="rev" op="sum" field="amount"}'
        );

        $result = $this->searchWithAggregations($aggregations);
        $agg = $result->getAggregation('vendor');
        $this->assertNotNull($agg);

        $this->assertBucketAggregationWithSyntheticRows(
            $agg,
            [
                ['key' => 'v4', 'metrics' => ['rev' => 750.0]],
                ['key' => 'v2', 'metrics' => ['rev' => 450.0]],
            ],
            ['metrics' => ['rev' => 425.0]],   // Others: v1(300) + v3(125)
            ['metrics' => ['rev' => 1625.0]]   // Total: all
        );
    }

    public function testRootMetrics(): void
    {
        $aggregations = $this->parseWikiAggregations(
            '{metric name="total_rev" op="sum" field="amount"}
             {metric name="order_count" op="count"}'
        );

        $result = $this->searchWithAggregations($aggregations);

        $rev = $result->getAggregation('total_rev');
        $this->assertNotNull($rev);
        $this->assertMetricResult($rev, 1625.0);

        $cnt = $result->getAggregation('order_count');
        $this->assertNotNull($cnt);
        $this->assertMetricResult($cnt, 8);
    }

    public function testNestedGroupBy(): void
    {
        $aggregations = $this->parseWikiAggregations(
            '{group field="region" name="region" size="10"}
             {group field="vendor_id" name="vendor" size="10"}
             {metric name="rev" op="sum" field="amount"}'
        );

        $result = $this->searchWithAggregations($aggregations);
        $agg = $result->getAggregation('region');
        $this->assertNotNull($agg);
        $this->assertTrue($agg->isBucket());

        $regionBuckets = $agg->getBuckets();
        usort($regionBuckets, fn ($a, $b) => strcmp((string) $a['key'], (string) $b['key']));

        $this->assertCount(3, $regionBuckets);
        $this->assertSame('AMER', (string) $regionBuckets[0]['key']);
        $this->assertSame('APAC', (string) $regionBuckets[1]['key']);
        $this->assertSame('EMEA', (string) $regionBuckets[2]['key']);

        // AMER: v2(300) + v3(50+75=125) = 3 docs
        $this->assertSame(3, $regionBuckets[0]['doc_count']);
        $amerVendors = $regionBuckets[0]['children']['vendor'];
        $this->assertInstanceOf(AggregationResult::class, $amerVendors);
        $amerBuckets = $amerVendors->getBuckets();
        usort($amerBuckets, fn ($a, $b) => strcmp((string) $a['key'], (string) $b['key']));
        $this->assertCount(2, $amerBuckets);
        $this->assertSame('v2', (string) $amerBuckets[0]['key']);
        $this->assertEqualsWithDelta(300.0, $amerBuckets[0]['metrics']['rev'], 0.01);
        $this->assertSame('v3', (string) $amerBuckets[1]['key']);
        $this->assertEqualsWithDelta(125.0, $amerBuckets[1]['metrics']['rev'], 0.01);

        // APAC: v4(750) = 2 docs
        $this->assertSame(2, $regionBuckets[1]['doc_count']);

        // EMEA: v1(300), v2(150) = 3 docs
        $this->assertSame(3, $regionBuckets[2]['doc_count']);
    }

    public function testFormulaAggregateExpression(): void
    {
        $aggregations = $this->parseWikiAggregations(
            '{group field="vendor_id" name="vendor" size="10"}
             {metric name="rev" op="sum" field="amount"}
             {metric name="qty" op="sum" field="quantity"}
             {metric name="margin" op="formula" expr="SUM(amount) - SUM(quantity)"}'
        );

        $result = $this->searchWithAggregations($aggregations);
        $agg = $result->getAggregation('vendor');
        $this->assertNotNull($agg);

        $buckets = $agg->getBuckets();
        usort($buckets, fn ($a, $b) => strcmp((string) $a['key'], (string) $b['key']));

        // v1: 300 - 15 = 285
        $this->assertEqualsWithDelta(285.0, $buckets[0]['metrics']['margin'], 0.01);
        // v2: 450 - 20 = 430
        $this->assertEqualsWithDelta(430.0, $buckets[1]['metrics']['margin'], 0.01);
        // v3: 125 - 10 = 115
        $this->assertEqualsWithDelta(115.0, $buckets[2]['metrics']['margin'], 0.01);
        // v4: 750 - 35 = 715
        $this->assertEqualsWithDelta(715.0, $buckets[3]['metrics']['margin'], 0.01);
    }

    public function testPostAggregateFormula(): void
    {
        $aggregations = $this->parseWikiAggregations(
            '{group field="vendor_id" name="vendor" size="10"}
             {metric name="rev" op="sum" field="amount"}
             {metric name="orders" op="count"}
             {metric name="aov" op="formula" expr="rev / NULLIF(orders, 0)"}'
        );

        $result = $this->searchWithAggregations($aggregations);
        $agg = $result->getAggregation('vendor');
        $this->assertNotNull($agg);

        $buckets = $agg->getBuckets();
        usort($buckets, fn ($a, $b) => strcmp((string) $a['key'], (string) $b['key']));

        // v1: 300/2 = 150
        $this->assertEqualsWithDelta(150.0, $buckets[0]['metrics']['aov'], 0.01);
        // v2: 450/2 = 225
        $this->assertEqualsWithDelta(225.0, $buckets[1]['metrics']['aov'], 0.01);
        // v3: 125/2 = 62.5
        $this->assertEqualsWithDelta(62.5, $buckets[2]['metrics']['aov'], 0.01);
        // v4: 750/2 = 375
        $this->assertEqualsWithDelta(375.0, $buckets[3]['metrics']['aov'], 0.01);
    }

    public function testHavingFilter(): void
    {
        $aggregations = $this->parseWikiAggregations(
            '{group field="vendor_id" name="vendor" size="10"}
             {metric name="rev" op="sum" field="amount"}
             {having expr="rev > 200"}'
        );

        $result = $this->searchWithAggregations($aggregations);
        $agg = $result->getAggregation('vendor');
        $this->assertNotNull($agg);

        $buckets = $agg->getBuckets();
        $keys = array_map(fn ($b) => (string) $b['key'], $buckets);
        sort($keys);

        // v1(300), v2(450), v4(750) pass; v3(125) does not
        $this->assertSame(['v1', 'v2', 'v4'], $keys);
    }

    public function testStatsMetric(): void
    {
        $aggregations = $this->parseWikiAggregations(
            '{metric name="amount_stats" op="stats" field="amount"}'
        );

        $result = $this->searchWithAggregations($aggregations);
        $stats = $result->getAggregation('amount_stats');
        $this->assertNotNull($stats);
        $this->assertFalse($stats->isBucket());

        $val = $stats->getValue();
        $this->assertEqualsWithDelta(8, $val['count'], 0.01);
        $this->assertEqualsWithDelta(50.0, $val['min'], 0.01);
        $this->assertEqualsWithDelta(500.0, $val['max'], 0.01);
        $this->assertEqualsWithDelta(1625.0, $val['sum'], 0.01);
        $this->assertEqualsWithDelta(203.125, $val['avg'], 0.01);
    }

    public function testCardinalityMetric(): void
    {
        $aggregations = $this->parseWikiAggregations(
            '{metric name="unique_vendors" op="cardinality" field="vendor_id"}'
        );

        $result = $this->searchWithAggregations($aggregations);
        $card = $result->getAggregation('unique_vendors');
        $this->assertNotNull($card);
        $this->assertMetricResult($card, 4);
    }

    public function testSimpleTermsNoMetrics(): void
    {
        $aggregations = $this->parseWikiAggregations(
            '{group field="status" name="by_status" size="10"}'
        );

        $result = $this->searchWithAggregations($aggregations);
        $agg = $result->getAggregation('by_status');
        $this->assertNotNull($agg);
        $this->assertTrue($agg->isBucket());

        $buckets = $agg->getBuckets();
        $map = [];
        foreach ($buckets as $b) {
            $map[(string) $b['key']] = $b['doc_count'];
        }
        ksort($map);

        $this->assertSame(['closed' => 3, 'open' => 4, 'pending' => 1], $map);
    }

    /**
     * Validates that date arithmetic in formulas produces correct day
     * differences across all engines, including the MySQL executeDirect path
     * where DATE columns must be wrapped with UNIX_TIMESTAMP().
     */
    public function testDateFormulaAvgDaysToClose(): void
    {
        $aggregations = $this->parseWikiAggregations(
            '{group field="vendor_id" name="vendor" size="10"}
             {metric name="avg_days" op="formula" expr="AVG((closed_date - created_date) / 86400)"}'
        );

        $result = $this->searchWithAggregations($aggregations);
        $agg = $result->getAggregation('vendor');
        $this->assertNotNull($agg);
        $this->assertTrue($agg->isBucket());

        $buckets = $agg->getBuckets();
        $map = [];
        foreach ($buckets as $b) {
            $map[(string) $b['key']] = $b['metrics']['avg_days'];
        }
        ksort($map);

        $this->assertEqualsWithDelta(15.0, $map['v1'], 0.1, 'v1: avg of 10 and 20 days');
        $this->assertEqualsWithDelta(13.5, $map['v2'], 0.1, 'v2: avg of 7 and 20 days');
        $this->assertEqualsWithDelta(8.5, $map['v3'], 0.1, 'v3: avg of 3 and 14 days');
        $this->assertEqualsWithDelta(18.0, $map['v4'], 0.1, 'v4: avg of 15 and 21 days');
    }

    // ------------------------------------------------------------------
    // Join tests
    // ------------------------------------------------------------------

    /**
     * Seed vendor documents that the join resolver can look up.
     * These have a 'vendor_code' field matching the bucket keys (v1..v4),
     * a 'relations' multivalue for relation-based join tests, and
     * a 'tags' multivalue field for multivalue join tests.
     */
    protected function populateVendors($index): void
    {
        $tf = $index->getTypeFactory();

        $vendors = [
            ['id' => 'v1', 'code' => 'v1', 'name' => 'Acme Corp', 'country' => 'US', 'tags' => ['premium', 'domestic']],
            ['id' => 'v2', 'code' => 'v2', 'name' => 'Beta Ltd', 'country' => 'UK', 'tags' => ['premium', 'international']],
            ['id' => 'v3', 'code' => 'v3', 'name' => 'Gamma Inc', 'country' => 'DE', 'tags' => ['standard', 'international']],
            ['id' => 'v4', 'code' => 'v4', 'name' => 'Delta SA', 'country' => 'FR', 'tags' => ['standard', 'domestic']],
        ];

        foreach ($vendors as $v) {
            $relations = [];
            $index->addDocument([
                'object_type' => $tf->identifier('trackeritem'),
                'object_id' => $tf->identifier($v['id']),
                'tracker_id' => $tf->identifier('17'),
                'title' => $tf->sortable($v['name']),
                'vendor_code' => $tf->identifier($v['code']),
                'country' => $tf->identifier($v['country']),
                'tags' => $tf->multivalue($v['tags']),
            ]);
        }
    }


    /**
     * Parse wiki markup that includes {join} blocks and return both
     * aggregations and joins.
     *
     * @return array{aggregations: array, joins: Join[]}
     */
    protected function parseWikiWithJoins(string $markup): array
    {
        $matches = \WikiParser_PluginMatcher::match($markup);
        $builder = new AggregationWikiBuilder();
        $builder->apply($matches);
        return [
            'aggregations' => $builder->getAggregations(),
            'joins' => $builder->getJoins(),
        ];
    }

    /**
     * Run aggregation + join resolution end-to-end.
     */
    protected function searchWithAggregationsAndJoins(array $aggregations, array $joins, ?\Search_Query $query = null): \Search_ResultSet
    {
        $result = $this->searchWithAggregations($aggregations, $query);

        if (! empty($joins)) {
            $resolver = new JoinResolver($joins);
            $resolver->resolve($result, $this->index);
        }

        return $result;
    }

    public function testJoinByObjectId(): void
    {
        $this->populateVendors($this->index);

        $parsed = $this->parseWikiWithJoins(
            '{group field="vendor_id" name="vendor" size="10"}
             {metric name="rev" op="sum" field="amount"}
             {join name="vi" on="vendor" tracker="17" select="title,country"}'
        );

        $result = $this->searchWithAggregationsAndJoins(
            $parsed['aggregations'],
            $parsed['joins']
        );

        $agg = $result->getAggregation('vendor');
        $this->assertNotNull($agg);

        $buckets = $agg->getBuckets();
        $map = [];
        foreach ($buckets as $b) {
            $map[(string) $b['key']] = $b['metrics'];
        }
        ksort($map);

        $this->assertSame('Acme Corp', $map['v1']['vi.title']);
        $this->assertSame('US', $map['v1']['vi.country']);
        $this->assertSame('Beta Ltd', $map['v2']['vi.title']);
        $this->assertSame('UK', $map['v2']['vi.country']);
        $this->assertSame('Gamma Inc', $map['v3']['vi.title']);
        $this->assertSame('DE', $map['v3']['vi.country']);
        $this->assertSame('Delta SA', $map['v4']['vi.title']);
        $this->assertSame('FR', $map['v4']['vi.country']);
    }

    public function testJoinByField(): void
    {
        $this->populateVendors($this->index);

        $parsed = $this->parseWikiWithJoins(
            '{group field="vendor_id" name="vendor" size="10"}
             {metric name="rev" op="sum" field="amount"}
             {join name="vi" on="vendor" field="vendor_code" tracker="17" select="title,country"}'
        );

        $result = $this->searchWithAggregationsAndJoins(
            $parsed['aggregations'],
            $parsed['joins']
        );

        $agg = $result->getAggregation('vendor');
        $this->assertNotNull($agg);

        $buckets = $agg->getBuckets();
        $map = [];
        foreach ($buckets as $b) {
            $map[(string) $b['key']] = $b['metrics'];
        }
        ksort($map);

        $this->assertSame('Acme Corp', $map['v1']['vi.title']);
        $this->assertSame('US', $map['v1']['vi.country']);
        $this->assertSame('Delta SA', $map['v4']['vi.title']);
        $this->assertSame('FR', $map['v4']['vi.country']);
    }

    public function testJoinByTypeIdKeys(): void
    {
        $this->populateVendors($this->index);

        $parsed = $this->parseWikiWithJoins(
            '{group field="vendor_id" name="vendor" size="10"}
             {metric name="rev" op="sum" field="amount"}
             {join name="vi" on="vendor" select="title,country"}'
        );

        $result = $this->searchWithAggregationsAndJoins(
            $parsed['aggregations'],
            $parsed['joins']
        );

        $agg = $result->getAggregation('vendor');
        $this->assertNotNull($agg);

        $buckets = $agg->getBuckets();
        $map = [];
        foreach ($buckets as $b) {
            $map[(string) $b['key']] = $b['metrics'];
        }

        // Bucket keys are bare IDs (v1..v4) which match vendor object_ids
        // since populateVendors indexes them with object_id = v1..v4
        $this->assertSame('Acme Corp', $map['v1']['vi.title'] ?? null);
        $this->assertSame('US', $map['v1']['vi.country'] ?? null);
        $this->assertSame('Delta SA', $map['v4']['vi.title'] ?? null);
        $this->assertSame('FR', $map['v4']['vi.country'] ?? null);
    }

    public function testJoinByFieldMultivalue(): void
    {
        $this->populateVendors($this->index);

        $parsed = $this->parseWikiWithJoins(
            '{group field="vendor_id" name="vendor" size="10"}
             {metric name="rev" op="sum" field="amount"}
             {join name="vi" on="vendor" field="tags" multivalue="y" tracker="17" select="title"}'
        );

        // Bucket keys are v1..v4 which are NOT tag values, so this should
        // produce null joins (no vendor has tags="v1"). This validates that
        // the multivalue filter path executes without error.
        $result = $this->searchWithAggregationsAndJoins(
            $parsed['aggregations'],
            $parsed['joins']
        );

        $agg = $result->getAggregation('vendor');
        $this->assertNotNull($agg);

        $buckets = $agg->getBuckets();
        foreach ($buckets as $b) {
            $this->assertNull($b['metrics']['vi.title']);
        }
    }


    public function testJoinNoMatchReturnsNullMetrics(): void
    {
        // Join targets tracker 99 which has no documents, so all joined fields are null.
        // We only select fields that exist in the index (from populate()) to avoid
        // "unknown column" errors on MySQL.
        $parsed = $this->parseWikiWithJoins(
            '{group field="vendor_id" name="vendor" size="10"}
             {metric name="rev" op="sum" field="amount"}
             {join name="vi" on="vendor" tracker="99" select="title,region"}'
        );

        $result = $this->searchWithAggregationsAndJoins(
            $parsed['aggregations'],
            $parsed['joins']
        );

        $agg = $result->getAggregation('vendor');
        $this->assertNotNull($agg);

        $buckets = $agg->getBuckets();
        foreach ($buckets as $b) {
            $this->assertNull($b['metrics']['vi.title']);
            $this->assertNull($b['metrics']['vi.region']);
        }
    }

    // ------------------------------------------------------------------
    // Multivalue group-key expansion tests
    // ------------------------------------------------------------------

    /**
     * Verifies that grouping by a multivalue field produces individual
     * buckets per value rather than one bucket per document's full array.
     *
     * Skipped on MySQL where multivalue fields are stored as hashed tokens
     * and executeDirect does not pass through TempTablePopulator.
     */
    public function testGroupByMultivalueFieldExpandsValues(): void
    {
        if ($this->index instanceof \Search_MySql_Index) {
            $this->markTestSkipped('MySQL stores multivalue as hashed tokens; per-value expansion not applicable.');
        }

        $this->populateMultivalueGroupData($this->index);

        $aggregations = $this->parseWikiAggregations(
            '{group field="tags" name="by_tag" size="10"}'
        );

        $query = new \Search_Query();
        $query->filterType('trackeritem');
        $query->filterIdentifier('30', 'tracker_id');

        $result = $this->searchWithAggregations($aggregations, $query);
        $agg = $result->getAggregation('by_tag');
        $this->assertNotNull($agg, 'Expected aggregation "by_tag"');

        $buckets = $agg->getBuckets();
        $map = [];
        foreach ($buckets as $b) {
            $map[(string) $b['key']] = $b['doc_count'];
        }
        ksort($map);

        // doc1 has [alpha, beta], doc2 has [beta, gamma], doc3 has [alpha]
        // => alpha:2, beta:2, gamma:1
        $this->assertSame(2, $map['alpha'] ?? null, 'alpha should appear in 2 docs');
        $this->assertSame(2, $map['beta'] ?? null, 'beta should appear in 2 docs');
        $this->assertSame(1, $map['gamma'] ?? null, 'gamma should appear in 1 doc');
        $this->assertCount(3, $map, 'Should have exactly 3 buckets');
    }

    /**
     * Verifies that grouping by a multivalue field with a metric still
     * correctly sums per individual value.
     *
     * Skipped on MySQL where multivalue fields are stored as hashed tokens.
     */
    public function testGroupByMultivalueWithMetric(): void
    {
        if ($this->index instanceof \Search_MySql_Index) {
            $this->markTestSkipped('MySQL stores multivalue as hashed tokens; per-value expansion not applicable.');
        }

        $this->populateMultivalueGroupData($this->index);

        $aggregations = $this->parseWikiAggregations(
            '{group field="tags" name="by_tag" size="10"}
             {metric name="total" op="sum" field="score"}'
        );

        $query = new \Search_Query();
        $query->filterType('trackeritem');
        $query->filterIdentifier('30', 'tracker_id');

        $result = $this->searchWithAggregations($aggregations, $query);
        $agg = $result->getAggregation('by_tag');
        $this->assertNotNull($agg);

        $buckets = $agg->getBuckets();
        $map = [];
        foreach ($buckets as $b) {
            $map[(string) $b['key']] = $b['metrics'];
        }
        ksort($map);

        // doc1(score=10): [alpha, beta], doc2(score=20): [beta, gamma], doc3(score=30): [alpha]
        // alpha: 10 + 30 = 40, beta: 10 + 20 = 30, gamma: 20
        $this->assertEqualsWithDelta(40, $map['alpha']['total'], 0.01, 'alpha total');
        $this->assertEqualsWithDelta(30, $map['beta']['total'], 0.01, 'beta total');
        $this->assertEqualsWithDelta(20, $map['gamma']['total'], 0.01, 'gamma total');
    }

    protected function populateMultivalueGroupData($index): void
    {
        $tf = $index->getTypeFactory();

        $docs = [
            ['id' => '101', 'tags' => ['alpha', 'beta'], 'score' => 10],
            ['id' => '102', 'tags' => ['beta', 'gamma'], 'score' => 20],
            ['id' => '103', 'tags' => ['alpha'], 'score' => 30],
        ];

        foreach ($docs as $doc) {
            $index->addDocument([
                'object_type' => $tf->identifier('trackeritem'),
                'object_id' => $tf->identifier($doc['id']),
                'tracker_id' => $tf->identifier('30'),
                'title' => $tf->sortable('item ' . $doc['id']),
                'tags' => $tf->multivalue($doc['tags']),
                'score' => $tf->numeric($doc['score']),
            ]);
        }
    }
}
