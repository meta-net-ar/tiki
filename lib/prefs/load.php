<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_load_list()
{
    $siteAccessHelp = 'Site-Access';

    return  [
        'load_threshold' => [
            'name' => tra('Maximum average server load threshold in the last minute'),
            'description' => tra('When server load threshold protection is enabled, close the site if the one-minute average load exceeds this value.'),
            'type' => 'text',
            'filter' => 'digits',
            'size' => '3',
            'help' => $siteAccessHelp,
            'dependencies' => [
                'use_load_threshold',
            ],
            'default' => 3,
        ],
        'load_retry_after' => [
            'name' => tra('Retry-After header value (in seconds) when site is closed due to high load'),
            'description' => tra('Number of seconds sent in the Retry-After HTTP header when the site is closed because server load exceeds the threshold.'),
            'type' => 'text',
            'filter' => 'digits',
            'size' => '3',
            'help' => $siteAccessHelp,
            'dependencies' => [
                'use_load_threshold',
            ],
            'default' => 120,
        ],
    ];
}
