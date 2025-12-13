<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Tests\Maintenance;

use DateTime;
use PHPUnit\Framework\TestCase;
use Tiki\Maintenance\Maintenance;

class MaintenanceTest extends TestCase
{
    protected function setUp(): void
    {
        global $prefs;
        $prefs['maintenanceRecurrentEnable'] = 'y';
        $prefs['maintenanceRecurrentPreMessage'] = 'Recurrent maintenance in TIME minutes.';
        $prefs['maintenanceRecurrentDuringMessage'] = 'Recurrent maintenance ongoing, will be back in DOWN minutes.';
        $prefs['maintenanceRecurrentStartTime'] = '14:00';
        $prefs['maintenanceRecurrentDuration'] = '60';
        $prefs['maintenanceEnableWeekdays'] = [date('w')];
        $prefs['maintenanceOnceOffEnable'] = 'y';
        $prefs['maintenanceOnceOffPreMessage'] = 'Once-off maintenance starting in TIME minutes.';
        $prefs['maintenanceOnceOffDuringMessage'] = 'Once-off maintenance in progress, will be back in DOWN minutes.';
        $prefs['maintenanceOnceOffStartDate'] = '2024-10-25';
        $prefs['maintenanceOnceOffStartTime'] = '12:00';
        $prefs['maintenanceOnceOffDuration'] = '30';
    }

    public function testIsRecurringTimeTrue()
    {
        global $prefs;
        $prefs['maintenanceRecurrentEnable'] = 'y';
        $prefs['maintenanceRecurrentStartTime'] = '14:00';
        $prefs['maintenanceRecurrentDuration'] = '60';
        $prefs['maintenanceEnableWeekdays'] = [date('w')];

        $maintenance = new Maintenance(new DateTime('14:30'));
        $this->assertTrue($maintenance->isRecurringTime());
    }

    public function testIsRecurringTimeFalse()
    {
        global $prefs;
        $prefs['maintenanceRecurrentEnable'] = 'y';
        $prefs['maintenanceRecurrentStartTime'] = '14:00';
        $prefs['maintenanceRecurrentDuration'] = '60';
        $prefs['maintenanceEnableWeekdays'] = [date('w')];

        $maintenance = new Maintenance(new DateTime('16:00'));
        $this->assertFalse($maintenance->isRecurringTime());
    }

    public function testIsOnceOffTimeTrue()
    {
        global $prefs;
        $prefs['maintenanceOnceOffEnable'] = 'y';
        $prefs['maintenanceOnceOffStartDate'] = '2024-10-25';
        $prefs['maintenanceOnceOffStartTime'] = '12:00';
        $prefs['maintenanceOnceOffDuration'] = '30';

        $maintenance = new Maintenance(new DateTime('2024-10-25 12:15'));
        $this->assertTrue($maintenance->isOnceOffTime());
    }

    public function testIsOnceOffTimeFalse()
    {
        global $prefs;
        $prefs['maintenanceOnceOffEnable'] = 'y';
        $prefs['maintenanceOnceOffStartDate'] = '2024-10-25';
        $prefs['maintenanceOnceOffStartTime'] = '12:00';
        $prefs['maintenanceOnceOffDuration'] = '30';

        $maintenance = new Maintenance(new DateTime('2024-10-25 13:00'));
        $this->assertFalse($maintenance->isOnceOffTime());
    }

    public function testIsTimeTrueWhenRecurring()
    {
        global $prefs;
        $prefs['maintenanceRecurrentEnable'] = 'y';
        $prefs['maintenanceRecurrentStartTime'] = '14:00';
        $prefs['maintenanceRecurrentDuration'] = '60';
        $prefs['maintenanceEnableWeekdays'] = [date('w')]; // Set the current weekday

        $maintenance = new Maintenance(new DateTime('14:30'));
        $this->assertTrue($maintenance->isTime());
    }

    public function testIsTimeTrueWhenOnceOff()
    {
        global $prefs;
        $prefs['maintenanceOnceOffEnable'] = 'y';
        $prefs['maintenanceOnceOffStartDate'] = '2024-10-25';
        $prefs['maintenanceOnceOffStartTime'] = '12:00';
        $prefs['maintenanceOnceOffDuration'] = '30';

        $maintenance = new Maintenance(new DateTime('2024-10-25 12:15'));
        $this->assertTrue($maintenance->isTime());
    }

    public function testGetMessageForOnceOff()
    {
        global $prefs;
        $prefs['maintenanceOnceOffEnable'] = 'y';
        $prefs['maintenanceOnceOffMessage'] = 'Once-off maintenance in progress, will be back in DOWN minutes';
        $prefs['maintenanceOnceOffStartDate'] = '2024-10-25';
        $prefs['maintenanceOnceOffStartTime'] = '12:00';
        $prefs['maintenanceOnceOffDuration'] = '30';

        $maintenance = new Maintenance(new DateTime('2024-10-25 12:15'));
        $this->assertEquals('Once-off maintenance in progress, will be back in 15 minutes.', $maintenance->getMessage());
    }

    public function testGetPreMaintenanceMessage()
    {
        global $prefs;
        $prefs['maintenanceRecurrentEnable'] = 'y';
        $prefs['maintenanceRecurrentMessage'] = 'Recurrent maintenance in %d minutes.';
        $prefs['maintenanceRecurrentStartTime'] = '14:00';
        $prefs['maintenanceTimeBeforeDisplayMessage'] = '30';

        $maintenance = new Maintenance(new DateTime('13:45'));
        $this->assertStringContainsString('Recurrent maintenance', $maintenance->getPreMaintenanceMessage());
    }
}
