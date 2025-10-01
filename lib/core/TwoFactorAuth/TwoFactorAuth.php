<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\TwoFactorAuth;

use Tiki\TwoFactorAuth\Exception\TwoFactorAuthException;
use TikiLib;

class TwoFactorAuth
{
    /** @var string string representation for google 2FA */
    public const GOOGLE_2FA = 'google2FA';

    /** @var string string representation for email 2FA */
    public const EMAIL_2FA = 'email2FA';

    /** @var string The default 2FA type */
    public const DEFAULT_2FA = self::GOOGLE_2FA;

    /** @var string[] The list of available 2FA types */
    public const AVAILABLE_2FA_TYPES = [self::GOOGLE_2FA, self::EMAIL_2FA];

    public static function getTwoFactorAuthTypeEnabled(): string
    {
        global $prefs;

        // If not set, always default to the default type
        return (string) $prefs['twoFactorAuthType'] ?? self::DEFAULT_2FA;
    }

    public static function getTwoFactorAuth()
    {
        return self::getTwoFactorAuthByType(self::getTwoFactorAuthTypeEnabled());
    }

    public static function getTwoFactorAuthByType($type)
    {
        $authType = ucfirst($type);
        $class = "\\Tiki\\TwoFactorAuth\\$authType";

        if (! in_array($type, self::AVAILABLE_2FA_TYPES, true) || ! class_exists($class)) {
            $errMsg = tr('Two factor auth type not found: ' . $type . ', Supported types are: ' . implode(', ', self::AVAILABLE_2FA_TYPES));
            throw new TwoFactorAuthException($errMsg);
        }

        if (! in_array(TwoFactorAuthInterface::class, class_implements($class), true)) {
            $errMsg = tr('The class ' . $class . ' does not implement the required TwoFactorAuthInterface.');
            throw new TwoFactorAuthException($errMsg);
        }

        $twoFactorAuth = new $class();

        return $twoFactorAuth;
    }

    public static function isMFARequired($user)
    {
        global $prefs, $userlib;

        $mfaIntervalDaysPrefs = intval($prefs['twoFactorAuthIntervalDays']);
        $requireMfa = false;

        if ($prefs['twoFactorAuth'] == 'y') {
            $userInfo = $userlib->get_user_info($user);
            if (! empty($userInfo['twoFactorSecret'])) {
                $lastMfaDateDb = intval($userInfo['last_mfa_date']);
                if ($mfaIntervalDaysPrefs > 0) {
                    if (empty($lastMfaDateDb) || (time() - $lastMfaDateDb) > ($mfaIntervalDaysPrefs * 86400)) {
                        $requireMfa = true;
                    }
                } else {
                    $requireMfa = true;
                }
            }
        }

        return $requireMfa;
    }

    public static function get2FactorSecret($user)
    {
        $userlib = TikiLib::lib('user');
        $twoFAType = self::getTwoFactorAuthTypeEnabled();

        if ($twoFAType === self::GOOGLE_2FA) {
            return $userlib->get_2_factor_secret($user);
        } elseif ($twoFAType === self::EMAIL_2FA) {
            return true;
        } else {
            throw new TwoFactorAuthException(tr('Unsupported 2FA type: ' . $twoFAType));
        }
    }
}
