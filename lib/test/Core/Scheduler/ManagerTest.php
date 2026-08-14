<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Tests\Scheduler;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Scheduler_Item;
use Scheduler_Manager;
use Tiki_Log;
use TikiLib;
use UsersLib;

/**
 * Class ItemTest
 */
class ManagerTest extends TestCase
{
    private const USER = 'membershiptest_a';
    protected static $items = [];

    public static function tearDownAfterClass(): void
    {
        $schedlib = TikiLib::lib('scheduler');

        foreach (self::$items as $itemId) {
            $schedlib->remove_scheduler($itemId);
        }
        $userlib = new UsersLib();
        $userlib->remove_user(self::USER);
    }

    /**
     * Test if two active schedulers scheduled to run at same same, run.
     */
    public function testSchedulersRunAtSameRunTime()
    {

        $logger = new Tiki_Log('UnitTests', LogLevel::ERROR);
        $scheduler1 = Scheduler_Item::fromArray([
            'id' => null,
            'name' => 'Test Scheduler',
            'description' => 'Test Scheduler',
            'task' => 'ConsoleCommandTask',
            'params' => '{"console_command":"list"}',
            'run_time' => '* * * * *',
            'status' => 'active',
            're_run' => 0,
            'run_only_once' => 0,
            'creation_date' => time() - 60,
            'user_run_now' => null,
        ], $logger);

        $scheduler2 = Scheduler_Item::fromArray([
            'id' => null,
            'name' => 'Test Scheduler',
            'description' => 'Test Scheduler',
            'task' => 'ConsoleCommandTask',
            'params' => '{"console_command":"list"}',
            'run_time' => '*/1 * * * *',
            'status' => 'active',
            're_run' => 0,
            'run_only_once' => 0,
            'creation_date' => time() - 60,
            'user_run_now' => null,
        ], $logger);

        $scheduler1->save();
        $scheduler2->save();

        self::$items[] = $scheduler1->id;
        self::$items[] = $scheduler2->id;

        $manager = new Scheduler_Manager($logger);
        $manager->run();

        $this->assertNotEmpty($scheduler1->getLastRun());
        $this->assertNotEmpty($scheduler2->getLastRun());
    }


    /**
     * Test if two active schedulers scheduled to run at same same, run.
     */
    public function testSchedulersRunNow()
    {
        $userlib = new UsersLib();
        $userlib->add_user(self::USER, 'abc', 'a@example.com');

        $logger = new Tiki_Log('UnitTests', LogLevel::ERROR);

        $scheduler1Data = [
            'id' => null,
            'name' => 'Test Scheduler',
            'description' => 'Test Scheduler',
            'task' => 'ConsoleCommandTask',
            'params' => '{"console_command":"list"}',
            'run_time' => '* * * * *',
            'status' => Scheduler_Item::STATUS_ACTIVE,
            're_run' => 0,
            'run_only_once' => 0,
            'user_run_now' => self::USER,
        ];

        $scheduler1 = Scheduler_Item::fromArray($scheduler1Data, $logger);

        $scheduler2Data = $scheduler1Data;
        $scheduler2Data['status'] = Scheduler_Item::STATUS_INACTIVE;

        $scheduler2 = Scheduler_Item::fromArray($scheduler2Data, $logger);

        $scheduler3Data = $scheduler2Data;
        $scheduler3Data['user_run_now'] = null;
        $scheduler3Data['creation_date'] = time() - 60;
        $scheduler3 = Scheduler_Item::fromArray($scheduler3Data, $logger);

        $scheduler4Data = $scheduler3Data;
        $scheduler4Data['status'] = Scheduler_Item::STATUS_ACTIVE;
        $scheduler4 = Scheduler_Item::fromArray($scheduler4Data, $logger);

        $scheduler1->save();
        $scheduler2->save();
        $scheduler3->save();
        $scheduler4->save();

        self::$items[] = $scheduler1->id;
        self::$items[] = $scheduler2->id;
        self::$items[] = $scheduler3->id;
        self::$items[] = $scheduler4->id;

        $manager = new Scheduler_Manager($logger);
        $manager->run();
        $lastRun = $scheduler1->getLastRun();
        $this->assertNotEmpty($lastRun);
        $this->assertStringContainsString('Run triggered by ', $lastRun['output']);

        $lastRun2 = $scheduler2->getLastRun();
        $this->assertNotEmpty($lastRun2);
        $this->assertStringContainsString('Run triggered by ' . self::USER, $lastRun['output']);

        $this->assertEmpty($scheduler3->getLastRun());
        $this->assertNotEmpty($scheduler4->getLastRun());

        $manager = new Scheduler_Manager($logger);
        $manager->run();
        $this->assertEquals($lastRun2['id'], $scheduler2->getLastRun()['id']);
    }

    /**
     * @covers Scheduler_Manager::shouldRun
     */
    public function testShouldRun()
    {
        $schedulerStub = $this->createMock(Scheduler_Item::class);
        $schedulerStub->user_run_now = 1;
        $schedulerStub->run_time = '*/5 * * * *'; // Every 5 min
        $schedulerStub
            ->method('getLastRun')
            ->willReturn([
                'end_time' => strtotime('15 min ago')
            ]);

        $schedulerStub
            ->method('getPreviousRunDate')
            ->willReturn(strtotime('10 min ago'));

        $managerStub = $this->createPartialMock(Scheduler_Manager::class, []);
        $shouldRun = $managerStub->shouldRun($schedulerStub);

        $this->assertTrue($shouldRun);

        $schedulerStub->user_run_now = null;
        $shouldRun = $managerStub->shouldRun($schedulerStub);

        $this->assertTrue($shouldRun);
    }

    /**
     * @covers Scheduler_Manager::shouldRun
     */
    public function testShouldNotRun()
    {
        $schedulerStub = $this->createMock(Scheduler_Item::class);
        $schedulerStub->user_run_now = 0;
        $schedulerStub->run_time = '0 * * * *'; // Every hour

        $schedulerStub
            ->method('getLastRun')
            ->willReturn([
                'end_time' => strtotime('15 min ago')
            ]);

        $schedulerStub
            ->method('getPreviousRunDate')
            ->willReturn(time() - (time() % 3600));

        $managerStub = $this->createPartialMock(Scheduler_Manager::class, ['shouldRun']);
        $shouldRun = $managerStub->shouldRun($schedulerStub);

        $this->assertFalse($shouldRun);
    }
    /**
     * Test if the Scheduler does run in the Timezone of the server.
     */
    public function testSchedulerRunsInServerTimezone()
    {
        $defaultTimezone = date_default_timezone_get();
        $testTimezone = $defaultTimezone === 'Pacific/Kiritimati' ? 'Pacific/Fiji' : 'Pacific/Kiritimati';
        $currentTime = date('Y-m-d H:i:s');
        $logger = new Tiki_Log('UnitTests', LogLevel::ERROR);
        $scheduler = Scheduler_Item::fromArray([
            'id' => null,
            'name' => 'Test Scheduler Server Timezone',
            'description' => 'Scheduler to test Server Timezone',
            'task' => 'ConsoleCommandTask',
            'params' => '{"console_command":"list"}',
            'run_time' => '0 2 * * *',
            'status' => Scheduler_Item::STATUS_ACTIVE,
            're_run' => 0,
            'run_only_once' => 0,
            'creation_date' => time() - 60,
            'user_run_now' => self::USER,
        ], $logger);
        $scheduler->save();
        $date_run_default_timezone = new \DateTime('@' . $scheduler->getPreviousRunDate($currentTime));
        $date_default_timezone = $date_run_default_timezone->format('Y-m-d H:i');
        date_default_timezone_set($testTimezone);
        $date_run_test_timezone = new \DateTime('@' . $scheduler->getPreviousRunDate($currentTime));
        $date_test_timezone = $date_run_test_timezone->format('Y-m-d H:i');
        date_default_timezone_set($defaultTimezone);
        $this->assertNotEquals($date_default_timezone, $date_test_timezone);
    }
}
