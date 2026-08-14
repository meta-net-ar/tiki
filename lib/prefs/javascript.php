<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_javascript_list()
{
    return [
        'javascript_cdn' => [
            'name' => tra('Use CDN for JavaScript'),
            'description' => tra('Obtain jQuery and jQuery UI libraries through a content delivery network (CDN).'),
            'type' => 'list',
            'help' => 'Content-delivery-network',
            'options' => [
                'none' => tra('None'),
                'google' => tra('Google'),
                'jquery' => tra('jQuery'),
            ],
            'default' => 'none',
            'tags' => ['basic'],
        ],
    ];
}
