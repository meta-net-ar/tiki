<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_altcha_list()
{
    return [
        'altcha_enabled' => [
            'name' => tra('Use Altcha'),
            'description' => tra('Enable Altcha, a privacy-friendly CAPTCHA alternative to protect forms.'),
            'hint' => tra('You can self-host Altcha or use an existing instance like altcha.org.'),
            'keywords' => 'captcha altcha',
            'help' => 'Spam-protection',
            'type' => 'flag',
            'default' => 'n',
        ],
        'altcha_hmac_key' => [
            'name' => tra('Altcha Hmac Key'),
            'type' => 'text',
            'description' => tra('Your Altcha developer Secret Key'),
            'size' => 60,
            'default' => '',
            'keywords' => 'captcha altcha',
        ]
    ];
}
