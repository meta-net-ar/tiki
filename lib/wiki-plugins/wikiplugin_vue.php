<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_vue_info()
{
    return [
        'name' => tra('Vue'),
        'documentation' => 'PluginVue',
        'description' => tra('Add a Vue.js component'),
        'prefs' => [ 'wikiplugin_vue' ],
        'body' => tra('HTML'),
        'validate' => 'all',
        'filter' => 'none',
        'iconname' => 'vuejs',
        'introduced' => 21,
        'format' => 'html',
        'params' => [
            'app' => [
                'required' => false,
                'name' => tra('Create App'),
                'description' => tra('Make the base App object and initialise Vue.js'),
                'since' => '21.0',
                'filter' => 'alpha',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
                'default' => '',
                'advanced' => true,
            ],
            'name' => [
                'required' => false,
                'name' => tra('Name'),
                'description' => tra('Identifier of the component.'),
                'since' => '21.0',
                'default' => '',
            ],
        ]
    ];
}

function wikiplugin_vue($data, $params)
{
    $smarty = TikiLib::lib('smarty');

    $repeat = false;

    $ret = \SmartyTiki\BlockHandler\Vue::render($params, $data, $smarty->getEmptyInternalTemplate(), $repeat);
    return $ret;
}
