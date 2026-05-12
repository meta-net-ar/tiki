<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_webmonetization_list()
{
    $webMonetizationHelp = 'Web-Monetization';

    return [
        'webmonetization_enabled' => [
            'name' => tra('Enable Web Monetization'),
            'description' => tra('Enable Web Monetization showing a message in page header.'),
            'type' => 'flag',
            'help' => $webMonetizationHelp,
            'tags' => ['experimental'],
            'default' => 'n',
        ],
        'webmonetization_all_website' => [
            'name' => tra('Enable for all pages'),
            'description' => tra('Enable Web Monetization in all website pages.'),
            'type' => 'flag',
            'tags' => ['experimental'],
            'default' => 'n',
            'help' => $webMonetizationHelp,
        ],
        'webmonetization_always_default' => [
            'name' => tra('Always use default site pointer'),
            'description' => tra('Always use default Web Monetization site pointer, even when users add their username.'),
            'type' => 'flag',
            'tags' => ['experimental'],
            'default' => 'n',
            'help' => $webMonetizationHelp,
        ],
        'webmonetization_default_payment_pointer' => [
            'name' => tra('Default payment pointer'),
            'description' => tra('Payment pointer to stream the payments.'),
            'type' => 'text',
            'tags' => ['experimental'],
            'default' => '',
            'help' => $webMonetizationHelp,
        ],
        'webmonetization_default_paywall_text' => [
            'name' => tra('Default paywall text'),
            'description' => tra('Default text to be used in web monetized content paywall.'),
            'type' => 'textarea',
            'tags' => ['experimental'],
            'default' => '',
            'help' => $webMonetizationHelp,
        ],
    ];
}
