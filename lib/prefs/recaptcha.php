<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_recaptcha_list()
{
    $spamProtectionHelp = 'Spam-protection';

    return  [
        'recaptcha_pubkey' => [
            'name' => tra('Site key'),
            'description' => tra('reCAPTCHA public key obtained after registering.'),
            'type' => 'text',
            'help' => $spamProtectionHelp,
            'size' => 60,
            'default' => '',
        ],
        'recaptcha_privkey' => [
            'name' => tra('Secret key'),
            'description' => tra('reCAPTCHA private key obtained after registering.'),
            'type' => 'text',
            'help' => $spamProtectionHelp,
            'size' => 60,
            'default' => '',
        ],
        'recaptcha_theme' => [
            'name' => tra('reCAPTCHA theme'),
            'description' => tra('Choose a theme for the reCAPTCHA widget.'),
            'type' => 'list',
            'help' => $spamProtectionHelp,
            'options' => [
                'clean' => tra('Clean'),
                'blackglass' => tra('Black Glass'),
                'red' => tra('Red'),
                'white' => tra('White'),
            ],
            'default' => 'clean',
        ],
        'recaptcha_version' => [
            'name' => tra('Version'),
            'description' => tra('reCAPTCHA version.'),
            'help' => $spamProtectionHelp,
            'type' => 'list',
            'options' => [
                '1' => tra('1.0'),
                '2' => tra('2.0'),
                '3' => tra('3.0'),
            ],
            'default' => '2',
        ],
    ];
}
