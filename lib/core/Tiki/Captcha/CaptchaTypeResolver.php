<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Captcha;

class CaptchaTypeResolver
{
    public static function getConfiguredType(array $prefs): string
    {
        return self::resolveProviderType($prefs['captcha_type'] ?? 'default', $prefs);
    }

    public static function requiresManualInput(array $prefs): bool
    {
        return ! in_array(self::getConfiguredType($prefs), ['recaptcha', 'recaptcha20', 'recaptcha30', 'altcha']);
    }

    private static function resolveProviderType(string $provider, array $prefs): string
    {
        if ($provider === 'recaptcha' && ! empty($prefs['recaptcha_privkey']) && ! empty($prefs['recaptcha_pubkey'])) {
            if (($prefs['recaptcha_version'] ?? '') == '2') {
                return 'recaptcha20';
            }

            if (($prefs['recaptcha_version'] ?? '') == '3') {
                return 'recaptcha30';
            }

            return 'recaptcha';
        }

        if ($provider === 'altcha' && ! empty($prefs['altcha_hmac_key'])) {
            return 'altcha';
        }

        if ($provider === 'questions' && ! empty($prefs['captcha_questions'])) {
            return 'questions';
        }

        if ($provider === 'default' && extension_loaded('gd') && function_exists('imagepng') && function_exists('imageftbbox')) {
            return 'default';
        }

        return 'dumb';
    }
}
