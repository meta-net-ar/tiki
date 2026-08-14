<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_back_info()
{
    return [
        'name' => tra('Back'),
        'documentation' => 'PluginBack',
        'description' => tra('Display a link that goes back one page in the browser history'),
        'prefs' => [ 'wikiplugin_back' ],
        'iconname' => 'back',
        'tags' => [ 'basic' ],
        'introduced' => 3,
        'params' => [],
        ];
}

function wikiplugin_back($data, $params)
{
    $begin = "<a href=\"javascript:history.go(-1)\">";

    $content = tra('Back');

    $end = "</a>";

    return "~np~" . $begin . $content . $end . "~/np~";
}
