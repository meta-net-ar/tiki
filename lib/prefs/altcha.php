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
        $prefs['altcha_site_url'] = [
            'name' => tra('Altcha URL'),
            'type' => 'text',
            'description' => tra('Base URL of the Altcha service. Example: https://altcha.org or your self-hosted instance.'),
            'size' => 60,
            'keywords' => 'captcha altcha',
            'default' => 'https://altcha.org',
        ];
        $prefs['altcha_theme'] = [
            'name' => tra('Altcha theme'),
            'type' => 'list',
            'keywords' => 'captcha altcha',
            'description' => tra('Choose a visual theme for the Altcha widget.'),
            'options' => [
                'light' => tra('Light'),
                'dark' => tra('Dark'),
            ],
            'default' => 'light',
        ];
        $prefs['altcha_difficulty'] = [
            'name' => tra('Difficulty level'),
            'type' => 'list',
            'keywords' => 'captcha altcha',
            'description' => tra('Select the complexity of the Altcha challenge.'),
            'options' => [
                'easy' => tra('Easy'),
                'medium' => tra('Medium'),
                'hard' => tra('Hard'),
            ],
            'default' => 'medium',
        ];
    } else {
        $prefs['altcha_incompatible'] = [
            'name' => tra('Altcha Not Available'),
            'description' => tra('Altcha requires PHP 8.2 or later. Your current version is ') . PHP_VERSION,
            'type' => 'static',
        ];
    }

    return $prefs;
}


