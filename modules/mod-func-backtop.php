<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_backtop_info()
{
    return [
        'name' => tra('Back to Top'),
        'description' => tra('Displays a floating button that appears when the user scrolls down. Clicking it smoothly scrolls the page back to the top.'),
        'prefs' => ['feature_elementplus'],
        'params' => [
            'target' => [
                'name' => tra('Target'),
                'description' => tra('The css selector to trigger scroll.'),
                'filter' => 'text',
                'default' => '',
            ],
            'placement' => [
                'name' => tra('Placement'),
                'description' => tra('Place the button to the left or right side.'),
                'filter' => 'text',
                'default' => 'right',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Left'), 'value' => 'left'],
                    ['text' => tra('Right'), 'value' => 'right'],
                ],
            ],
            'visibilityHeight' => [
                'name' => tra('Visibility height'),
                'description' => tra('The button will not show until the scroll height reaches this value (in px).'),
                'filter' => 'int',
                'default' => '200',
            ],
            'title' => [
                'name' => tra('Title'),
                'description' => tra('Custom title to disply on the button.'),
                'filter' => 'text',
                'default' => 'Back to top',
            ],
            'sideDistance' => [
                'name' => tra('Side distance'),
                'description' => tra('Sets the distance from the side edge of the screen (in px).'),
                'filter' => 'int',
                'default' => '40',
            ],
            'bottom' => [
                'name' => tra('Bottom distance'),
                'description' => tra('Sets the distance from the bottom of the screen (in px).'),
                'filter' => 'int',
                'default' => '40',
            ]
        ]
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_backtop($mod_reference, &$module_params)
{
    $info = module_backtop_info();
    $defaults = [];
    foreach ($info['params'] as $key => $param) {
        $defaults[$key] = $param['default'];
    }

    $module_params = array_merge($defaults, array_filter($module_params));
    $module_params['nobox'] = 'y';
    $headerlib = TikiLib::lib('header');
    $headerlib->add_js_module("import '@vue-widgets/el-backtop';");
}
