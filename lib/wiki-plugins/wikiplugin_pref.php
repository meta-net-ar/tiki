<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_pref_info()
{
    return [
        'name' => tra('Preference'),
        'documentation' => 'PluginPref',
        'description' => tra('Display content based on global preference settings'),
        'body' => tr('Wiki text to display if conditions are met. The body may contain %0{ELSE}%1. Text after the marker
            will be displayed if not matching the conditions.', '<code>', '</code>'),
        'prefs' => ['wikiplugin_pref'],
        'filter' => 'wikicontent',
        'extraparams' => true,
        'iconname' => 'settings',
        'introduced' => 11,
        'params' => [
        ],
    ];
}

function wikiplugin_pref($data, $params)
{
    global $tikilib;
    $dataelse = '';
    if (str_contains($data, '{ELSE}')) {
        $dataelse = substr($data, strpos($data, '{ELSE}') + 6);
        $data = substr($data, 0, strpos($data, '{ELSE}'));
    }

    foreach ($params as $prefName => $prefValue) {
        if ($tikilib->get_preference($prefName) != $prefValue) {
            return $dataelse;
        }
    }
    return $data;
}
