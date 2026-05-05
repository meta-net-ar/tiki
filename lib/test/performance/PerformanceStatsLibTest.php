<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once 'lib/performance/performancestatslib.php';

class PerformanceStatsLibTest extends PHPUnit\Framework\TestCase
{
    public function testAddRecordStoresBackendAndFrontendTimesWhenSchemaSupportsIt(): void
    {
        $table = $this->getMockBuilder(stdClass::class)
            ->addMethods(['insert'])
            ->getMock();

        $table->expects($this->once())
            ->method('insert')
            ->with([
                'url' => 'https://example.org/wiki/HomePage',
                'time_taken' => 1200,
                'backend_time' => 450,
                'frontend_time' => 750,
            ])
            ->willReturn(true);

        $lib = $this->getMockBuilder('PerformanceStatsLib')
            ->disableOriginalConstructor()
            ->onlyMethods(['table'])
            ->getMock();

        $lib->expects($this->once())
            ->method('table')
            ->with('tiki_performance')
            ->willReturn($table);

        $this->assertTrue(
            $lib->addRecord('https://example.org/wiki/HomePage', 1200, 450, 750)
        );
    }

    public function testAddRecordFallsBackToLegacyInsertIfExtraColumnsAreUnavailable(): void
    {
        $modernTable = $this->getMockBuilder(stdClass::class)
            ->addMethods(['insert'])
            ->getMock();

        $modernTable->expects($this->once())
            ->method('insert')
            ->with([
                'url' => 'https://example.org/wiki/HomePage',
                'time_taken' => 1200,
                'backend_time' => 450,
                'frontend_time' => 750,
            ])
            ->willThrowException(new Exception('Unknown column backend_time'));

        $legacyTable = $this->getMockBuilder(stdClass::class)
            ->addMethods(['insert'])
            ->getMock();

        $legacyTable->expects($this->once())
            ->method('insert')
            ->with([
                'url' => 'https://example.org/wiki/HomePage',
                'time_taken' => 1200,
            ])
            ->willReturn(true);

        $lib = $this->getMockBuilder('PerformanceStatsLib')
            ->disableOriginalConstructor()
            ->onlyMethods(['table'])
            ->getMock();

        $lib->expects($this->exactly(2))
            ->method('table')
            ->with('tiki_performance')
            ->willReturnOnConsecutiveCalls($modernTable, $legacyTable);

        $this->assertTrue(
            $lib->addRecord('https://example.org/wiki/HomePage', 1200, 450, 750)
        );
    }

    public function testGetRequestDetailsByUrlReturnsFetchedAggregateRow(): void
    {
        $result = $this->getMockBuilder(stdClass::class)
            ->addMethods(['fetchRow'])
            ->getMock();

        $expected = [
            'url' => 'https://example.org/wiki/HomePage',
            'number_of_requests' => 3,
            'average_time_taken' => 900,
            'minimum_time_taken' => 700,
            'maximum_time_taken' => 1200,
            'average_backend_time' => 400,
            'average_frontend_time' => 500,
            'breakdown_samples' => 2,
        ];

        $result->expects($this->once())
            ->method('fetchRow')
            ->willReturn($expected);

        $lib = $this->getMockBuilder('PerformanceStatsLib')
            ->disableOriginalConstructor()
            ->onlyMethods(['query'])
            ->getMock();

        $lib->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('SELECT'),
                ['https://example.org/wiki/HomePage']
            )
            ->willReturn($result);

        $this->assertSame($expected, $lib->getRequestDetailsByUrl('https://example.org/wiki/HomePage'));
    }

    public function testGetRequestDetailsByUrlReturnsFalseWhenQueryFails(): void
    {
        $lib = $this->getMockBuilder('PerformanceStatsLib')
            ->disableOriginalConstructor()
            ->onlyMethods(['query'])
            ->getMock();

        $lib->expects($this->once())
            ->method('query')
            ->willReturn(false);

        $this->assertFalse($lib->getRequestDetailsByUrl('https://example.org/wiki/HomePage'));
    }

    public function testGetSlowestSamplesByUrlPassesUrlAndLimitToQuery(): void
    {
        $pdoResult = new stdClass();

        $lib = $this->getMockBuilder('PerformanceStatsLib')
            ->disableOriginalConstructor()
            ->onlyMethods(['query'])
            ->getMock();

        $lib->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('LIMIT 10'),
                ['https://example.org/wiki/HomePage']
            )
            ->willReturn($pdoResult);

        $this->assertSame(
            $pdoResult,
            $lib->getSlowestSamplesByUrl('https://example.org/wiki/HomePage', 10)
        );
    }
}
