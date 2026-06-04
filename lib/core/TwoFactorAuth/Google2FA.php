<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\TwoFactorAuth;

use TikiLib;
use PragmaRX\Google2FA\Google2FA as PragmaGoogle2FA;
use Tiki\TwoFactorAuth\Exception\TwoFactorAuthException;

class Google2FA implements TwoFactorAuthInterface
{
    private $google2fa;
    private $userlib;

    public function __construct()
    {
        $this->google2fa = new PragmaGoogle2FA();
        $this->userlib = TikiLib::lib('user');
    }

    public function generateCode($user, $isEmail = true)
    {
        // For Google2FA, code generation is typically done on the client-side
        return true;
    }

    public function validateCode($user, $code = null)
    {
        if (empty($code)) {
            $errMsg = tr('You have enabled 2FA and 2FA code is required. So, you should login with 2FA.');
            throw new TwoFactorAuthException($errMsg);
        }

        $twoFactorSecret = $this->userlib->get_2_factor_secret($user);
        $result = $this->google2fa->verifyKey($twoFactorSecret, $code, 2);

        if (! $result) {
            $this->userlib->handleUnsuccessfulLogin($user);
        }

        return $result;
    }
}
