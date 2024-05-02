<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_helloworld_info()
{
    global $prefs;

    $info = [
        'searchable_by_default' => false,
        'name' => 'HelloWorldSample',
        'description' => 'Sample wikiplugin for _custom',
        'body' => tra('text'),
        'iconname' => 'code',
        'filter' => 'wikicontent',
        'validate' => 'arguments',
        'introduced' => 2,
        'params' => [
        ],
    ];

    return $info;
}

function wikiplugin_helloworld($content, $params)
{
    extract($params);

    return "<h2>Hello world plugin: $content</h2> (_custom/shared/wiki-plugins/wikiplugin_helloworld)";
}
