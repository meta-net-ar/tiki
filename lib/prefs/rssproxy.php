<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_rssproxy_list()
{
    return  [
        'rssproxy_host' => [
            'name' => tra('Proxy host name'),
            'description' => tra('Proxy host - without http:// or similar, just the host name'),
            'type' => 'text',
            'size' => '20',
            'filter' => 'url',
            'dependencies' => [
                'use_rss_proxy',
            ],
            'default' => '',
        ],
        'rssproxy_port' => [
            'name' => tra('Port'),
            'description' => tra('Proxy port'),
            'type' => 'text',
            'filter' => 'digits',
            'size' => '5',
            'dependencies' => [
                'use_rss_proxy',
            ],
            'default' => '',
        ],
        'rssproxy_user' => [
            'name' => tra('Proxy username'),
            'type' => 'text',
            'size' => 10,
            'filter' => 'none',
            'default' => '',
        ],
        'rssproxy_pass' => [
            'name' => tra('Proxy password'),
            'type' => 'text',
            'size' => 10,
            'filter' => 'none',
            'default' => '',
        ],
    ];
}
