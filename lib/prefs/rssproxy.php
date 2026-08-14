<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_rssproxy_list()
{
    $generalSettingsHelp = 'General-Settings';

    return  [
        'rssproxy_host' => [
            'name' => tra('Proxy host name'),
            'description' => tra('Proxy host - without http:// or similar, just the host name'),
            'type' => 'text',
            'size' => '20',
            'filter' => 'url',
            'help' => $generalSettingsHelp,
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
            'help' => $generalSettingsHelp,
            'size' => '5',
            'dependencies' => [
                'use_rss_proxy',
            ],
            'default' => '',
        ],
        'rssproxy_user' => [
            'name' => tra('Proxy username'),
            'description' => tra('Optional username for proxy authentication on RSS feed requests when Use RSS proxy is enabled.'),
            'type' => 'text',
            'help' => $generalSettingsHelp,
            'size' => 10,
            'filter' => 'none',
            'default' => '',
        ],
        'rssproxy_pass' => [
            'name' => tra('Proxy password'),
            'description' => tra('Optional password for proxy authentication on RSS feed requests when Use RSS proxy is enabled.'),
            'type' => 'text',
            'help' => $generalSettingsHelp,
            'size' => 10,
            'filter' => 'none',
            'default' => '',
        ],
    ];
}
