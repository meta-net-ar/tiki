<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Test\Core\Services\User;

use JitFilter;
use PHPUnit\Framework\TestCase;
use Services_User_Controller as ServicesUserController;
use TikiLib;

class ServicesUserControllerTest extends TestCase
{
    protected static $originalTimezone;
    protected $originalUserSyncPref;

    protected function setUp(): void
    {
        global $prefs, $user;

        // Ensure session exists (required for temporary timezone)
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Simulate a logged-in user (required by get_display_timezone)
        $user = 'testuser';

        // Fake referer used by controller redirects
        $_SERVER['HTTP_REFERER'] = 'http://example.com/some/page';

        // redirectAndReturn() calls TikiAccessLib::redirect(), which ends the
        // request with exit(); preventRedirect() makes it a no-op so the
        // controller's return value can still be asserted (same pattern used
        // by lib/core/Services/Tracker/Controller.php and searchlib-unified.php).
        TikiLib::lib('access')->preventRedirect(true);

        self::$originalTimezone = $prefs['display_timezone'];
        $this->originalUserSyncPref = $prefs['user_localtimezonesync'];
    }

    protected function tearDown(): void
    {
        global $prefs;

        $prefs['display_timezone'] = self::$originalTimezone;
        $prefs['user_localtimezonesync'] = $this->originalUserSyncPref;

        TikiLib::lib('access')->preventRedirect(false);

        unset($_SESSION['temp_timezone']);
        unset($_SERVER['HTTP_REFERER']);
        unset($GLOBALS['user']);
    }

    public function testTimezoneSwitchAction(): void
    {
        global $prefs;

        $input = new JitFilter([
            'client_timezone' => 'Africa/Lubumbashi',
            'timezone_action' => 'switch',
        ]);

        $result = (new ServicesUserController())->actionLocalTimezoneSync($input);

        // Actual behavior: preference must be updated
        $this->assertEquals('Africa/Lubumbashi', $prefs['display_timezone']);

        // Response consistency
        $this->assertFalse($result['different']);
        $this->assertEquals('Africa/Lubumbashi', $result['effectiveTimezone']);
    }

    public function testTimezoneTemporaryAction(): void
    {
        global $prefs;

        $originalTz = $prefs['display_timezone'];

        $input = new JitFilter([
            'client_timezone' => 'Africa/Lubumbashi',
            'timezone_action' => 'temporary',
        ]);

        $result = (new ServicesUserController())->actionLocalTimezoneSync($input);

        // Actual behavior: session timezone only
        $this->assertEquals('Africa/Lubumbashi', $_SESSION['temp_timezone']);

        // Preference must remain unchanged
        $this->assertEquals($originalTz, $prefs['display_timezone']);

        // Response consistency
        $this->assertEquals('Africa/Lubumbashi', $result['effectiveTimezone']);
    }

    public function testTimezoneNeverActionDisablesSyncForUser(): void
    {
        global $prefs, $tikilib;

        $tikilib->set_user_preference('testuser', 'user_localtimezonesync', 'y');

        $input = new JitFilter([
            'timezone_action' => 'never',
        ]);

        $result = (new ServicesUserController())->actionLocalTimezoneSync($input);

        $this->assertSame([], $result);
        $this->assertSame(
            'n',
            $tikilib->get_user_preference('testuser', 'user_localtimezonesync'),
            'The timezone synchronization notification should be disabled for the user'
        );
        $this->assertSame('n', $prefs['user_localtimezonesync']);
    }

    public function testFunctionallyEquivalentTimezonesAreNotDifferent(): void
    {
        global $tikilib, $prefs;

        // Deterministic initial state
        $prefs['display_timezone'] = 'Etc/UTC';
        date_default_timezone_set('Etc/UTC');

        // Explicit user preference (not detect mode)
        $tikilib->set_user_preference('testuser', 'display_timezone', 'Etc/UTC');

        $input = new JitFilter([
            'client_timezone' => 'UTC',
            'timezone_action' => 'check',
        ]);

        $result = (new ServicesUserController())->actionLocalTimezoneSync($input);

        // Functional equivalence must not trigger sync
        $this->assertFalse(
            $result['different'],
            'Equivalent timezones should not trigger sync notification'
        );

        // Effective timezone may be normalized
        $this->assertContains(
            $result['effectiveTimezone'],
            ['UTC', 'Etc/UTC']
        );
    }
}
