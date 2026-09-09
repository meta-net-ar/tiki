<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_poweredby_info()
{
    return [
        'name' => tra('Powered By'),
        'description' => tra('Powered by Tiki, and others'),
        'params' => [
            'tiki' => [
                'name' => tra('Tiki'),
                'description' => tra('Shows "Powered by Tiki Wiki CMS Groupware" message'),
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Default: Yes'), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'version' => [
                'name' => tra('Version'),
                'description' => tra('Tiki version info'),
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Default: Yes'), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'credits' => [
                'name' => tra('Credits'),
                'description' => tra('Shows theme credits (contents of credits.tpl)'),
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Default: Yes'), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'icons' => [
                'name' => tra('Icons'),
                'description' => tra('Shows various "powered by" icons'),
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Default: Yes'), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
        ],
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_poweredby($mod_reference, $module_params)
{
}
