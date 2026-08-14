<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_map_layer_selector_info()
{
    return [
        'name' => tra('Layer Selector'),
        'description' => tra("Replace the map's built-in layer controls."),
        'prefs' => [],
        'params' => [
            'baselayer' => [
                'required' => false,
                'name' => tr('Include base layer'),
                'description' => tr('Include the drop list for the base layers.'),
                'default' => 'y',
            ],
            'optionallayers' => [
                'required' => false,
                'name' => tr('Include optional layers'),
                'description' => tr('Include the checkboxes for the optional layers.'),
                'default' => 'y',
            ],
        ],
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_map_layer_selector($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');

    $smarty->assign(
        'controls',
        [
            'baselayer' => ! isset($module_params['baselayer']) || $module_params['baselayer'] != 'n',
            'optionallayers' => ! isset($module_params['optionallayers']) || $module_params['optionallayers'] != 'n',
        ]
    );
}
