<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_showpages_info()
{
    return [
        'name' => tra('Show Pages'),
        'documentation' => 'PluginShowPages',
        'description' => tra('Deprecated: Use PluginListPages instead. Find pages by searching within page names.'),
        'prefs' => [ 'wikiplugin_showpages' ],
        'iconname' => 'search',
        'introduced' => 1,
        'params' => [
            'find' => [
                'required' => true,
                'name' => tra('Find'),
                'description' => tra('Search criteria'),
                'since' => '1',
            ],
            'max' => [
                'required' => false,
                'name' => tra('Result Count'),
                'description' => tra('Maximum amount of results displayed.'),
                'since' => '1',
                'filter' => 'int',
            ],
            'display' => [
                'required' => false,
                'name' => tra('Display'),
                'description' => tra('Display page name and/or description. Both displayed by default.'),
                'since' => '1',
                'filter' => 'text',
                'default' => 'name|desc',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Name'), 'value' => 'name'],
                    ['text' => tra('Description'), 'value' => 'desc'],
                    ['text' => tra('Name & Description'), 'value' => 'name|desc']
                ]
            ]
        ]
    ];
}

function wikiplugin_showpages($data, $params)
{
    include_once('lib/wiki-plugins/wikiplugin_listpages.php');

    // Default parameters required by wikiplugin_listpages
    $listpagesParams = [];
    $info = wikiplugin_listpages_info();
    $listpagesParams = WikiPlugin_Helper::applyParamsDefaults($listpagesParams, $info);

    if (isset($params['find'])) {
        $listpagesParams['find'] = $params['find'];
    }

    if (isset($params['max'])) {
        $listpagesParams['max'] = $params['max'];
    }

    // Map display parameter to showNameOnly or showNameAndDescriptionOnly
    $display = $params['display'] ?? 'name|desc';
    if ($display === 'name') {
        $listpagesParams['showNameOnly'] = 'y';
    } else {
        $listpagesParams['showNameAndDescriptionOnly'] = 'y';
    }

    return wikiplugin_listpages($data, $listpagesParams);
}
