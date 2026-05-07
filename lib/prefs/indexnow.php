<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_indexnow_list()
{
    return [
        'indexnow_enable' => [
            'name' => tra('IndexNow Protocol'),
            'description' => tra('Enable instant notification to search engines when content changes with IndexNow protocol.'),
            'help' => 'https://www.indexnow.org/',
            'type' => 'flag',
            'default' => 'n',
            'since' => '30',
            'admin' => 'seoprefs',
        ],
        'indexnow_key' => [
            'name' => tra('IndexNow API Key'),
            'description' => tra('The API key used to authenticate IndexNow submissions. generated automatically.'),
            'type' => 'text',
            'size' => 32,
            'default' => '',
            'since' => '30',
            'readonly' => true,
        ],
    ];
}
