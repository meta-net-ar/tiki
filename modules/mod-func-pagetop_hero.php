<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * @return array
 */
function module_pagetop_hero_info()
{
    return [
        'name' => tr('Page Topbar Hero'),
        'description' => tr('An easy page-top hero section located in Tiki\'s topbar module zone'),
        'params' => [
            'pagetitle' => [
                'required' => false,
                'name' => tr('Page title'),
                'description' => tr('Page title'),
            ],
            'description' => [
                'required' => false,
                'name' => tr('Page description'),
            ],
            'breadcrumbs' => [
                'required' => false,
                'name' => tr('Breadcrumbs'),
                'description' => tr('Allows you to specify the navigation paths to arrive at the current page. Separate items with commas. Use pipe (|) to specify custom text and URL: "Text|URL".'),
            ],
            'breadcrumbs_auto_link' => [
                'required' => false,
                'name' => tr('Breadcrumbs auto-link'),
                'description' => tr('Automatically convert breadcrumb items to links. When enabled, each item becomes a link to itself unless pipe (|) is used to override. (y|n)'),
                'default' => 'n'
            ],
            'content_position' => [
                'required' => false,
                'name' => tra('Content position'),
                'description' => tra('Content position inside the hero image'),
                'default' => 'center',
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Center'), 'value' => 'center'],
                    ['text' => tra('Left-Center'), 'value' => 'leftcenter'],
                    ['text' => tra('Top-Left'), 'value' => 'topleft'],
                    ['text' => tra('Top-Center'), 'value' => 'topcenter'],
                    ['text' => tra('Top-Right'), 'value' => 'topright'],
                    ['text' => tra('Bottom-Left'), 'value' => 'bottomleft'],
                    ['text' => tra('Bottom-Center'), 'value' => 'bottomcenter'],
                    ['text' => tra('Bottom-Right'), 'value' => 'bottomright'],
                ],
            ],
            'bgimage' => [
                'required' => false,
                'name' => tra('Page Topbar Hero background image URL'),
                'description' => tra('Enter image URL, in the case of a single image.'),
            ],
            'usepagename' => [
                'required' => false,
                'name' => tr('Use page title'),
                'description' => tra('Allows the page title to be used as title of the pagetop module (y|n)'),
                'default' => 'n'
            ]
        ]
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_pagetop_hero($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');

    $pagetitle = '';
    $breadcrumbs = [];
    $auto_link = isset($module_params["breadcrumbs_auto_link"]) && $module_params["breadcrumbs_auto_link"] == 'y';

    if (isset($module_params["usepagename"]) && $module_params["usepagename"] == 'y') {
        $pagetitle = $_REQUEST['page'] ?? '';
        $breadcrumbs[] = ['text' => tra("Home"), 'url' => null];
        $breadcrumbs[] = ['text' => $pagetitle, 'url' => null];
    } else {
        $pagetitle = $module_params["pagetitle"] ?? '';
        if (! empty($module_params["breadcrumbs"])) {
            $items = explode(",", $module_params["breadcrumbs"]);
            foreach ($items as $item) {
                $item = trim($item);
                if (strpos($item, '|') !== false) {
                    // Item has pipe: Text|URL
                    list($text, $url) = explode('|', $item, 2);
                    $breadcrumbs[] = ['text' => trim($text), 'url' => trim($url)];
                } else {
                    // Item without pipe
                    if ($auto_link) {
                        $breadcrumbs[] = ['text' => $item, 'url' => $item];
                    } else {
                        $breadcrumbs[] = ['text' => $item, 'url' => null];
                    }
                }
            }
            // Add page title as last breadcrumb (non-clickable)
            if (! empty($pagetitle)) {
                $breadcrumbs[] = ['text' => $pagetitle, 'url' => null];
            }
        }
    }

    $smarty->assign('pagetitle', $pagetitle);
    $smarty->assign('description', $module_params["description"] ?? '');
    $smarty->assign('content_position', $module_params["content_position"] ?? 'center');
    $smarty->assign('breadcrumbs', $breadcrumbs);
    $smarty->assign('bgimage', $module_params["bgimage"] ?? '');
}
