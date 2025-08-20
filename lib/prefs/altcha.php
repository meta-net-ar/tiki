<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_altcha_list()
{
    $altchaPrefs = [];

    // Altcha requires PHP 8.2+ (see https://github.com/altcha/altcha-php)
    // Tiki's general minimum is 8.1, but this specific integration depends on 8.2 features.
    if (version_compare(PHP_VERSION, '8.2.0', '>=')) {
        $altchaPrefs['altcha_enabled'] = [
            'name' => tra('Use Altcha'),
            'description' => tra('Enable Altcha, a privacy-friendly CAPTCHA alternative to protect forms.'),
            'hint' => tra('You can self-host Altcha or use an existing instance like altcha.org.'),
            'keywords' => 'captcha altcha',
            'help' => 'Spam-protection',
            'type' => 'flag',
            'default' => 'n',
        ];
        $altchaPrefs['altcha_hmac_key'] = [
            'name' => tra('Altcha Hmac Key'),
            'type' => 'text',
            'description' => tra('Your Altcha Developper Secret Key'),
            'size' => 60,
            'default' => '',
            'keywords' => 'captcha altcha',
        ];
    } else {
        $altchaPrefs['altcha_incompatible'] = [
            'name' => tra('Altcha Not Available'),
            'description' => tra('Altcha requires PHP 8.2 or later. Your current version is ') . PHP_VERSION,
            'hint' => tra('Altcha cannot be enabled because your PHP version is too low. Upgrade PHP to use it.'),
            'type' => 'flag',
            'keywords' => 'captcha altcha',
        ];
    }

    return $altchaPrefs;
}
