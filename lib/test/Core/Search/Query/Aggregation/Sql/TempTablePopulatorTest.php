<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Test\Core\Search\Query\Aggregation\Sql;

use PHPUnit\Framework\TestCase;
use Search\Query\Aggregation\Sql\TempTablePopulator;
use TikiDb;

class TempTablePopulatorTest extends TestCase
{
    private function createPopulator(array $schema): TempTablePopulator
    {
        return new TempTablePopulator(TikiDb::get(), $schema);
    }

    private function invokeExpandRow(TempTablePopulator $pop, $row): array
    {
        $ref = new \ReflectionMethod($pop, 'expandRow');
        $ref->setAccessible(true);
        return $ref->invoke($pop, $row);
    }

    public function testScalarGroupKeyProducesSingleRow(): void
    {
        $schema = [
            'columns' => [
                ['name' => 'vendor_id', 'type' => 'text', 'sql_type' => 'VARCHAR(255)', 'source_field' => 'vendor_id', 'is_group_key' => true],
                ['name' => 'amount', 'type' => 'numeric', 'sql_type' => 'DECIMAL(20,4)', 'source_field' => 'amount', 'is_group_key' => false],
            ],
        ];
        $pop = $this->createPopulator($schema);

        $rows = $this->invokeExpandRow($pop, ['vendor_id' => 'v1', 'amount' => 100]);

        $this->assertCount(1, $rows);
        $this->assertSame('v1', $rows[0][0]);
        $this->assertSame(100, $rows[0][1]);
    }

    public function testArrayGroupKeyExpandsToMultipleRows(): void
    {
        $schema = [
            'columns' => [
                ['name' => 'tags', 'type' => 'text', 'sql_type' => 'VARCHAR(255)', 'source_field' => 'tags', 'is_group_key' => true],
                ['name' => 'score', 'type' => 'numeric', 'sql_type' => 'DECIMAL(20,4)', 'source_field' => 'score', 'is_group_key' => false],
            ],
        ];
        $pop = $this->createPopulator($schema);

        $rows = $this->invokeExpandRow($pop, ['tags' => ['alpha', 'beta', 'gamma'], 'score' => 50]);

        $this->assertCount(3, $rows);
        $this->assertSame('alpha', $rows[0][0]);
        $this->assertSame(50, $rows[0][1]);
        $this->assertSame('beta', $rows[1][0]);
        $this->assertSame(50, $rows[1][1]);
        $this->assertSame('gamma', $rows[2][0]);
        $this->assertSame(50, $rows[2][1]);
    }

    public function testNonGroupKeyArrayIsCoercedToString(): void
    {
        $schema = [
            'columns' => [
                ['name' => 'category', 'type' => 'text', 'sql_type' => 'VARCHAR(255)', 'source_field' => 'category', 'is_group_key' => true],
                ['name' => 'labels', 'type' => 'text', 'sql_type' => 'VARCHAR(255)', 'source_field' => 'labels', 'is_group_key' => false],
            ],
        ];
        $pop = $this->createPopulator($schema);

        $rows = $this->invokeExpandRow($pop, ['category' => 'news', 'labels' => ['urgent', 'important']]);

        $this->assertCount(1, $rows);
        $this->assertSame('news', $rows[0][0]);
        $this->assertSame('urgent, important', $rows[0][1]);
    }

    public function testCartesianProductOfMultipleGroupKeyArrays(): void
    {
        $schema = [
            'columns' => [
                ['name' => 'tags', 'type' => 'text', 'sql_type' => 'VARCHAR(255)', 'source_field' => 'tags', 'is_group_key' => true],
                ['name' => 'region', 'type' => 'text', 'sql_type' => 'VARCHAR(255)', 'source_field' => 'region', 'is_group_key' => true],
                ['name' => 'amount', 'type' => 'numeric', 'sql_type' => 'DECIMAL(20,4)', 'source_field' => 'amount', 'is_group_key' => false],
            ],
        ];
        $pop = $this->createPopulator($schema);

        $rows = $this->invokeExpandRow($pop, [
            'tags' => ['alpha', 'beta'],
            'region' => ['US', 'EU'],
            'amount' => 100,
        ]);

        // 2 tags x 2 regions = 4 rows
        $this->assertCount(4, $rows);

        $expanded = array_map(fn($r) => $r[0] . '|' . $r[1], $rows);
        sort($expanded);
        $this->assertSame(['alpha|EU', 'alpha|US', 'beta|EU', 'beta|US'], $expanded);

        foreach ($rows as $r) {
            $this->assertSame(100, $r[2]);
        }
    }

    public function testEmptyArrayGroupKeyProducesNoRows(): void
    {
        $schema = [
            'columns' => [
                ['name' => 'tags', 'type' => 'text', 'sql_type' => 'VARCHAR(255)', 'source_field' => 'tags', 'is_group_key' => true],
                ['name' => 'score', 'type' => 'numeric', 'sql_type' => 'DECIMAL(20,4)', 'source_field' => 'score', 'is_group_key' => false],
            ],
        ];
        $pop = $this->createPopulator($schema);

        $rows = $this->invokeExpandRow($pop, ['tags' => [], 'score' => 10]);

        // Document with empty multivalue group key does not contribute to any bucket
        $this->assertCount(0, $rows);
    }

    public function testTypeIdValuesExpandCorrectly(): void
    {
        $schema = [
            'columns' => [
                ['name' => 'related', 'type' => 'text', 'sql_type' => 'VARCHAR(255)', 'source_field' => 'tracker_field_related_multi', 'is_group_key' => true],
            ],
        ];
        $pop = $this->createPopulator($schema);

        $rows = $this->invokeExpandRow($pop, [
            'tracker_field_related_multi' => ['trackeritem:3039', 'trackeritem:1', 'trackeritem:42'],
        ]);

        $this->assertCount(3, $rows);
        $this->assertSame('trackeritem:3039', $rows[0][0]);
        $this->assertSame('trackeritem:1', $rows[1][0]);
        $this->assertSame('trackeritem:42', $rows[2][0]);
    }
}
