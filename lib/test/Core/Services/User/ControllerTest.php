<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Test\Core\Services\User;

use JitFilter;
use PHPUnit\Framework\TestCase;
use Services_User_Controller as ServicesUserController;

class ServicesUserControllerTest extends TestCase
{
    protected $controller;
    protected static $originalTimezone;
    protected $originalUserSyncPref;

    protected function setUp(): void
    {
        global $prefs,$user;

        $_SERVER['HTTP_REFERER'] = 'http://example.com/some/page';

        self::$originalTimezone = $prefs['display_timezone'];
        $this->originalUserSyncPref = $prefs['user_localtimezonesync'];
    }

    protected function tearDown(): void
    {
        global $prefs;

        $prefs['display_timezone'] = self::$originalTimezone;
        $prefs['user_localtimezonesync'] = $this->originalUserSyncPref;

        unset($_SESSION['temp_timezone']);
        unset($_SERVER['HTTP_REFERER']);
    }

    public function testTimezoneSwitchAction(): void
    {

        $input = new JitFilter([
            'client_timezone' => 'Africa/Lubumbashi',
            'timezone_action' => 'switch',
        ]);

        $result = (new ServicesUserController())->actionLocalTimezoneSync($input);

        $this->assertEquals([
            'different' => false,
            'preferedTimezone' => 'Africa/Lubumbashi',
            'clientTimezone' => 'Africa/Lubumbashi'
        ], $result);
    }

    public function testTimezoneTemporaryAction(): void
    {
        $input = new JitFilter([
            'client_timezone' => 'Africa/Lubumbashi',
            'timezone_action' => 'temporary',
        ]);

        $_SESSION['temp_timezone'] = $input->client_timezone;

        $result = (new ServicesUserController())->actionLocalTimezoneSync($input);

        $this->assertEquals([
            'different' => false,
            'preferedTimezone' => 'Africa/Lubumbashi',
            'clientTimezone' => 'Africa/Lubumbashi'
        ], $result);
    }
}
