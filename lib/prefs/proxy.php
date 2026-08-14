<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_proxy_list()
{
    $generalSettingsHelp = 'General-Settings';

    return  [
        'proxy_host' => [
            'name' => tra('Proxy host name'),
            'description' => tra('Proxy host - without http:// or similar, just the host name'),
            'type' => 'text',
            'size' => '20',
            'filter' => 'url',
            'dependencies' => [
                'use_proxy',
            ],
            'default' => '',
            'help' => $generalSettingsHelp,
        ],
        'proxy_port' => [
            'name' => tra('Port'),
            'description' => tra('Proxy port'),
            'type' => 'text',
            'filter' => 'digits',
            'size' => '5',
            'dependencies' => [
                'use_proxy',
            ],
            'default' => '',
            'help' => $generalSettingsHelp,
        ],
        'proxy_user' => [
            'name' => tra('Proxy username'),
            'description' => tra('Optional username for proxy authentication on outgoing HTTP requests when Use proxy is enabled.'),
            'type' => 'text',
            'size' => 10,
            'filter' => 'none',
            'default' => '',
            'help' => $generalSettingsHelp,
        ],
        'proxy_pass' => [
            'name' => tra('Proxy password'),
            'description' => tra('Optional password for proxy authentication on outgoing HTTP requests when Use proxy is enabled.'),
            'type' => 'text',
            'size' => 10,
            'filter' => 'none',
            'default' => '',
            'help' => $generalSettingsHelp,
        ],
    ];
}
