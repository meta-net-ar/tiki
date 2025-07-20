<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_altcha_list()
{
    $prefs = [];

    if (version_compare(PHP_VERSION, '8.2.0', '>=')) {
        $prefs['altcha_enabled'] = [
            'name' => tra('Use Altcha'),
            'description' => tra('Enable Altcha, a privacy-friendly CAPTCHA alternative to protect forms.'),
            'hint' => tra('You can self-host Altcha or use an existing instance like altcha.org.'),
            'keywords' => 'captcha altcha',
            'help' => 'Spam-protection',
            'type' => 'flag',
            'default' => 'n',
        ];
        $prefs['altcha_hmac_key'] = [
            'name' => tra('Altcha Hmac Key'),
            'type' => 'text',
            'description' => tra('Your Altcha Developper Secret Key'),
            'size' => 60,
            'default' => '',
            'keywords' => 'captcha altcha',
        ];
    } else {
        $prefs['altcha_incompatible'] = [
            'name' => tra('Altcha Not Available'),
            'description' => tra('Altcha requires PHP 8.2 or later. Your current version is ') . PHP_VERSION,
            'type' => 'static',
            'keywords' => 'captcha altcha',
        ];
    }

    return $prefs;
}
