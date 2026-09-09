<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_most_commented_info()
{
    return [
        'name' => tra('Most Commented'),
        'description' => tra('Display the most commented objects of a certain type.'),
        'prefs' => [],
        'params' => [
            'objectType' => [
                'name' => tra('Object Type'),
                'description' => tra('Type of objects to consider.'),
                'options' => [
                    ['text' => tra('Default: Wiki pages'), 'value' => ''],
                    ['text' => tra('Wiki pages'), 'value' => 'wiki'],
                    ['text' => tra('Blog posts'), 'value' => 'blog'],
                    ['text' => tra('Articles'), 'value' => 'article'],
                ],
            ],
            'objectLanguageFilter' => [
                'name' => tra('Language Filter'),
                'description' => tra('If set to a RFC1766 language tag, restricts the objects considered to those in the specified language.')
            ]
        ],
        'common_params' => ['nonums', 'rows']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_most_commented($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');

    $type = 'wiki';
    if (isset($module_params['objectType'])) {
        $type = $module_params['objectType'];
        if ($type != 'article' && $type != 'blog' && $type != 'wiki') {
            //If parameter is not properly set then default to wiki
            $type = 'wiki';
        }
    }

    $result = TikiLib::lib('comments')->order_comments_by_count($type, $module_params['objectLanguageFilter'] ?? '', $mod_reference['rows']);
    if ($result === false) {
        $smarty->assign('module_error', tra('Feature disabled'));
        return;
    }
    $smarty->assign('modMostCommented', $result['data']);
    $smarty->assign('modContentType', $type);
}
