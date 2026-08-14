<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_adminbar_info()
{
    return [
        'name' => tra('Quick Admin Bar'),
        'description' => tra('Consolidated admin bar with an easy access to quick admin links, recent changes, and important admin features'),
        'prefs' => [],
        'params' => [
            'mode' => [
                'name' => tra('Mode'),
                'description' => tra('Display mode: module or header. Leave empty for module mode'),
            ],
        ]
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_adminbar($mod_reference, $module_params)
{
    global $tiki_p_admin;
    if ($tiki_p_admin != 'y') {
        return;
    }
    TikiLib::lib('smarty')->assign('recent_prefs', TikiLib::lib('prefs')->getRecent());
}
