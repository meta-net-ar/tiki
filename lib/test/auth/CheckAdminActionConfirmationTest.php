<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Auth;

use TikiTestCase;
use TikiLib;
use UsersLib;

/**
 * @group integration
 */
class CheckAdminActionConfirmationTest extends TikiTestCase
{
    private const LOGIN = 'checkadminconfirm_admin';
    private const PASSWORD = 'Correct-Horse-Battery-42';

    /** @var UsersLib */
    protected $userlib;
    private $originalPref;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userlib = new UsersLib();
        $this->userlib->remove_user(self::LOGIN);
        $this->userlib->add_user(self::LOGIN, self::PASSWORD, 'checkadminconfirm@example.test');

        global $prefs;
        $this->originalPref = $prefs['users_admin_actions_require_validation'] ?? 'n';

        unset($_SESSION['autologin_base_url']);
    }

    protected function tearDown(): void
    {
        global $prefs;
        $prefs['users_admin_actions_require_validation'] = $this->originalPref;
        unset($_SESSION['autologin_base_url']);

        $this->userlib->remove_user(self::LOGIN);
        parent::tearDown();
    }

    public function testAllowedWhenPreferenceDisabled(): void
    {
        global $prefs;
        $prefs['users_admin_actions_require_validation'] = 'n';

        $this->assertSame('', $this->userlib->checkAdminActionConfirmation(self::LOGIN, 'totally-wrong'));
    }

    public function testAllowedWhenPreferenceEnabledAndPasswordCorrect(): void
    {
        global $prefs;
        $prefs['users_admin_actions_require_validation'] = 'y';

        $this->assertSame('', $this->userlib->checkAdminActionConfirmation(self::LOGIN, self::PASSWORD));
    }

    public function testRejectedWhenPasswordIncorrect(): void
    {
        global $prefs;
        $prefs['users_admin_actions_require_validation'] = 'y';

        $this->assertNotSame('', $this->userlib->checkAdminActionConfirmation(self::LOGIN, 'wrong-password'));
    }

    public function testRejectedWhenPasswordMissing(): void
    {
        global $prefs;
        $prefs['users_admin_actions_require_validation'] = 'y';

        $this->assertNotSame('', $this->userlib->checkAdminActionConfirmation(self::LOGIN, ''));
    }

    public function testRejectedWithAutologinSessionEvenIfPasswordCorrect(): void
    {
        global $prefs;
        $prefs['users_admin_actions_require_validation'] = 'y';
        $_SESSION['autologin_base_url'] = 'https://example.test/';

        $error = $this->userlib->checkAdminActionConfirmation(self::LOGIN, self::PASSWORD);

        $this->assertSame($this->userlib->getAutologinAdminActionError(), $error);
    }

    public function testHelperItselfIsValidAgainstARenamedLoginWhenCalledWithTheNewLogin(): void
    {
        global $prefs;
        $prefs['users_admin_actions_require_validation'] = 'y';

        $this->assertSame('', $this->userlib->checkAdminActionConfirmation(self::LOGIN, self::PASSWORD));

        $renamedLogin = self::LOGIN . '_renamed';
        $this->userlib->remove_user($renamedLogin);
        $this->userlib->change_login(self::LOGIN, $renamedLogin);

        try {
            $this->assertSame('', $this->userlib->checkAdminActionConfirmation($renamedLogin, self::PASSWORD));
            $this->assertNotSame('', $this->userlib->checkAdminActionConfirmation(self::LOGIN, self::PASSWORD));
        } finally {
            $this->userlib->remove_user($renamedLogin);
        }
    }

    public function testDoesNotTriggerLoginMultipleForbiddenSideEffects(): void
    {
        global $prefs;
        $prefs['users_admin_actions_require_validation'] = 'y';
        $originalMultiple = $prefs['login_multiple_forbidden'] ?? 'n';
        $originalGrabSession = $prefs['login_grab_session'] ?? 'n';
        $prefs['login_multiple_forbidden'] = 'y';
        $prefs['login_grab_session'] = 'n';

        $tikilib = TikiLib::lib('tiki');
        $otherSessionId = 'other-session-' . uniqid();
        $tikilib->table('tiki_sessions')->insert([
            'sessionId' => $otherSessionId,
            'timestamp' => $tikilib->now,
            'user' => self::LOGIN,
            'tikihost' => 'test',
        ]);
        $tikilib->online_users_cache = null;

        try {
            $loginAttempt = $this->userlib->validate_user(self::LOGIN, self::PASSWORD);
            $this->assertFalse($loginAttempt[0], 'test setup did not reproduce an already-online account');
            $tikilib->online_users_cache = null;

            $this->assertSame('', $this->userlib->checkAdminActionConfirmation(self::LOGIN, self::PASSWORD));
        } finally {
            $tikilib->table('tiki_sessions')->delete(['sessionId' => $otherSessionId]);
            $tikilib->online_users_cache = null;
            $prefs['login_multiple_forbidden'] = $originalMultiple;
            $prefs['login_grab_session'] = $originalGrabSession;
        }
    }
}
