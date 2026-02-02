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
    /** @var string string representation for totp 2FA */
    public const TOTP_2FA = 'totp2FA';

    /** @var string string representation for email 2FA */
    public const EMAIL_2FA = 'email2FA';

    /** @var string The default 2FA type */
    public const DEFAULT_2FA = self::TOTP_2FA;

    /**
     * Map internal 2FA identifiers to their implementing classes.
     *
     * IMPORTANT:
     * - The string identifiers (keys) are the values stored in prefs/DB for backward compatibility.
     * - Class names follow PSR naming (Google2FA, Email2FA). Do NOT try to compute class names
     *   from the identifier (e.g., with ucfirst). Always use this map to avoid case issues
     *   and to keep the TOTP_2FA → Google2FA linkage explicit.
     */
    private const CLASS_BY_TYPE = [
        self::TOTP_2FA  => \Tiki\TwoFactorAuth\Google2FA::class,
        self::EMAIL_2FA => \Tiki\TwoFactorAuth\Email2FA::class,
    ];

    /** @var string[] list of available 2FA type identifiers */
    public const AVAILABLE_2FA_TYPES = [self::TOTP_2FA, self::EMAIL_2FA];

    public static function getTwoFactorAuthTypeEnabled(): string
    {
        global $prefs;

        // If not set or empty, always default to the default type
        return $prefs['twoFactorAuthType'] ?: self::DEFAULT_2FA;
    }

    public static function getTwoFactorAuth()
    {
        return self::getTwoFactorAuthByType(self::getTwoFactorAuthTypeEnabled());
    }

    public static function getTwoFactorAuthByType($type)
    {
        $class = self::CLASS_BY_TYPE[$type] ?? null;

        if (! $class || ! in_array($type, self::AVAILABLE_2FA_TYPES, true) || ! class_exists($class)) {
            $errMsg = tr(
                'Two factor auth type not found: %0. Supported types are: %1',
                $type,
                implode(', ', self::AVAILABLE_2FA_TYPES)
            );
            throw new TwoFactorAuthException($errMsg);
        }

        if (! in_array(TwoFactorAuthInterface::class, class_implements($class), true)) {
            $errMsg = tr('The class %0 does not implement the required TwoFactorAuthInterface.', $class);
            throw new TwoFactorAuthException($errMsg);
        }

        return new $class();
    }

    public static function isMFARequired($user)
    {
        global $prefs, $userlib;

        $mfaIntervalDaysPrefs = (int) $prefs['twoFactorAuthIntervalDays'];
        $requireMfa = false;

        if ($prefs['twoFactorAuth'] === 'y') {
            $userInfo = $userlib->get_user_info($user);
            if (! empty($userInfo['twoFactorSecret'])) {
                $lastMfaDateDb = (int) $userInfo['last_mfa_date'];
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

        if ($twoFAType === self::TOTP_2FA) {
            return $userlib->get_2_factor_secret($user);
        } elseif ($twoFAType === self::EMAIL_2FA) {
            return true;
        }

        throw new TwoFactorAuthException(tr('Unsupported 2FA type: ' . $twoFAType));
    }
}
