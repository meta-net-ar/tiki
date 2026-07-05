<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Test\Performance;

require_once 'lib/performance/performancestatslib.php';

class PerformanceStatsLibTest extends \TikiTestCase
{
    private $lib;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lib = new \PerformanceStatsLib();
        $db = \TikiDb::get();

        $db->query('DELETE FROM tiki_performance');
        $db->query(
            'INSERT INTO tiki_performance (id, url, time_taken, backend_time, frontend_time) VALUES
            (1, ?, 1200, 500, 700),
            (2, ?, 800, 400, 400),
            (3, ?, 600, NULL, NULL)',
            [
                'https://example.org/wiki/HomePage',
                'https://example.org/wiki/HomePage',
                'https://example.org/wiki/AnotherPage',
            ]
        );
    }

    public function testAddRecordStoresBackendAndFrontendTimes(): void
    {
        $this->lib->addRecord('https://example.org/wiki/NewPage', 1500, 600, 900);

        $row = \TikiDb::get()->table('tiki_performance')->fetchRow(
            ['url', 'time_taken', 'backend_time', 'frontend_time'],
            ['url' => 'https://example.org/wiki/NewPage']
        );

        $this->assertSame('https://example.org/wiki/NewPage', $row['url']);
        $this->assertSame(1500, (int) $row['time_taken']);
        $this->assertSame(600, (int) $row['backend_time']);
        $this->assertSame(900, (int) $row['frontend_time']);
    }

    public function testGetRequestDetailsByUrlReturnsAggregateValues(): void
    {
        $stats = $this->lib->getRequestDetailsByUrl('https://example.org/wiki/HomePage');

        $this->assertSame('https://example.org/wiki/HomePage', $stats['url']);
        $this->assertSame(2, (int) $stats['number_of_requests']);
        $this->assertSame(1000, (int) $stats['average_time_taken']);
        $this->assertSame(800, (int) $stats['minimum_time_taken']);
        $this->assertSame(1200, (int) $stats['maximum_time_taken']);
        $this->assertSame(450, (int) $stats['average_backend_time']);
        $this->assertSame(550, (int) $stats['average_frontend_time']);
        $this->assertSame(2, (int) $stats['breakdown_samples']);
    }

    public function testGetSlowestSamplesByUrlReturnsRowsSortedByTimeTakenDesc(): void
    {
        $rows = $this->lib->getSlowestSamplesByUrl('https://example.org/wiki/HomePage', 2);

        $this->assertCount(2, $rows);
        $this->assertSame(1200, (int) $rows[0]['time_taken']);
        $this->assertSame(800, (int) $rows[1]['time_taken']);
    }
}
