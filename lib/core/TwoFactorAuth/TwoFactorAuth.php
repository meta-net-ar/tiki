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

        if ($prefs['twoFactorAuth'] === 'y') {
            $userInfo = $userlib->get_user_info($user);
            $lastMfaDateDb = intval($userInfo['last_mfa_date']);
            if ($mfaIntervalDaysPrefs > 0) {
                if (empty($lastMfaDateDb) || (time() - $lastMfaDateDb) > ($mfaIntervalDaysPrefs * 86400)) {
                    $requireMfa = true;
                }
            } else {
                $requireMfa = true;
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

    public static function forceTwoFactorAuth($user)
    {
        global $prefs;

        $userlib = TikiLib::lib('user');

        //
        // Process first exceptions ( that should not force )
        //

        //Do not force user (admin) to enable 2FA
        if ($user === 'admin') {
            return false;
        }

        //Do not force specific users to enable 2FA
        if (! empty($prefs['twoFactorAuthExcludedUsers'])) {
            //get list of usernames
            $users = $prefs['twoFactorAuthExcludedUsers'];
            if (in_array($user, $users)) {
                return false;
            }
        }

        //Do not force users in the specific groups to enable 2FA
        if (! empty($prefs['twoFactorAuthExcludedGroup'])) {
            //get list of group names
            $groups = $prefs['twoFactorAuthExcludedGroup'];
            foreach ($groups as $groupName) {
                $userIsInGroup = $userlib->user_is_in_group($user, $groupName);
                if ($userIsInGroup) {
                    return false;
                }
            }
        }

        //
        // Then process enforcing
        //

        //Force all users to enable 2FA
        if ($prefs['twoFactorAuthAllUsers'] === 'y') {
            return true;
        }

        //Force users in the specific groups to enable 2FA
        if (! empty($prefs['twoFactorAuthIncludedGroup'])) {
            //get list of group names
            $groups = $prefs['twoFactorAuthIncludedGroup'];
            foreach ($groups as $groupName) {
                $userIsInGroup = $userlib->user_is_in_group($user, $groupName);
                if ($userIsInGroup) {
                    return true;
                }
            }
        }

        //Force specific users to enable 2FA
        if (! empty($prefs['twoFactorAuthIncludedUsers'])) {
            //get list of usernames
            $users = $prefs['twoFactorAuthIncludedUsers'];
            if (in_array($user, $users)) {
                return true;
            }
        }

        // default not to force
        return false;
    }
}
