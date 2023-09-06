<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_menu_info()
{
    return [
        'name' => tra('Menu'),
        'description' => tra('Displays a menu or a structure as a menu.'),
        'params' => [
            'id' => [
                'name' => tra('Menu'),
                'description' => tra('Identifier of the menu (from tiki-admin_menus.php)'),
                'filter' => 'int',
                'profile_reference' => 'menu',
            ],
            'structureId' => [
                'name' => tra('Structure'),
                'description' => tra('Identifier of a structure of wiki pages (name or number from tiki-admin_structures.php)'),
                'filter' => 'text',
                'profile_reference' => 'structure',
            ],
            'type' => [
                'name' => tra('Type'),
                'description' => tra('Orientation of menu: horiz or vert (default: vert)'),
                'filter' => 'text',
            ],
            'bootstrap' => [
                'name' => tra('Use Bootstrap menu'),
                'description' => tra('If Bootstrap menu isn\'t chosen, the result will be a legacy-style vertical menu.') . ' ( y / n )',
                'default' => 'y',
            ],
            'megamenu' => [
                'name' => tra('Use Smartmenu megamenu'),
                'description' => tra('This is a Smartmenu that has a flattened structure of level 1 (Smartmenu preference must be turned on).') . ' ( y / n )',
                'default' => 'n',
            ],
            'megamenu_static' => [
                'name' => tra('Align megamenu dropdown with the full width of the menu'),
                'description' => tra('This is instead of the dropdown being in the standard position under its parent item in the menu') . ' ( y / n )',
                'default' => 'y',
            ],
            // TODO - needs image url field
            // 'megamenu_images' => [
            //  'name' => tra('Use Megamenu Images'),
            //  'description' => tra('Adds an image to each Megamenu') . ' ( y / n )',
            //  'default' => 'n',
            // ],

            'navbar_toggle' => [
                'name' => tra('Show navbar toggle button'),
                'description' => tra('Used in Bootstrap navbar menus when viewport is too narrow for menu items') . ' ( y / n )',
                'default' => 'y',
            ],
            'navbar_brand' => [
                'name' => tra('The URL of the navbar brand (logo)'),
                'description' => tra('Used in Bootstrap navbar menus, if there is a brand logo to be attached to the menu'),
                'default' => '',
            ],
            'navbar_class' => [
                'name' => tra('CSS class(es) for the menu nav element'),
                'description' => tra('To set the color style of horizontal menus, use the Look and Feel Admin "Navbar background color" option and, for horizontal menus in the top and topbar module zones especially and also in other locations, use CSS variables (recommended method - see https://themes.tiki.org/Pagetop Styles and CSS Variables). Note: 1) the previous default classes "navbar navbar-expand-lg" and "navbar-light bg-light" or "navbar-dark bg-dark" are no longer needed here, 2) special coloring of vertical menus will require specific CSS rules in a stylesheet or Look and Feel Admin Custom CSS, etc.'),
                'default' => '',
            ],
            'menu_id' => [
                'name' => tra('DOM #id'),
                'description' => tra('HTML id of the menu in the DOM'),
            ],
            'menu_class' => [
                'name' => tra('CSS class'),
                'description' => tra('Class of the menu container'),
                'filter' => 'text',
            ],
            'sectionLevel' => [
                'name' => tra('Lower limit on visible menu levels'),
                'description' => tra('In a menu with many levels such as made with a wiki structure, menu levels lower than this won\'t be displayed.'),
                'filter' => 'int',
            ],
            'toLevel' => [
                'name' => tra('Upper limit on visible menu levels'),
                'description' => tra('Menu levels higher than this won\'t be displayed.'),
                'filter' => 'int',
            ],
            'link_on_section' => [
                'name' => tra('Link on section'),
                'description' => tra('Create links on menu sections') . ' ' . tra('(y/n default y)'),
                'filter' => 'alpha',
            ],
            'translate' => [
                'name' => tra('Translate'),
                'description' => tra('Enable translation of menu text') . ' ' . tra('(y/n default y)'),
                'filter' => 'alpha',
            ],
            'menu_cookie' => [
                'name' => tra('Menu cookie'),
                'description' => tra('Open the menu to show current option if possible') . ' ' . tra('(y/n default y)'),
                'filter' => 'alpha',
            ],
            'show_namespace' => [
                'name' => tra('Show namespace'),
                'description' => tra('Show namespace prefix in page names') . ' ( y / n )', // Do not translate y/n
                'default' => 'y'
            ],
            'setSelected' => [
                'name' => tra('Set selected'),
                'description' => tra('Process all menu items to show currently selected item and other dynamic states. Useful when disabled on very large menus where performance becomes an issue.') . ' ( y / n )',
                'default' => 'y'
            ],
        ]
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_menu($mod_reference, &$module_params)
{
    $smarty = TikiLib::lib('smarty');
    $smarty->assign('module_error', '');

    if (! isset($module_params['navbar_brand'])) {
        $module_params['navbar_brand'] = "";
    }
    if (! isset($module_params['type'])) {
        $module_params['type'] = "vert";
    }
    if (! isset($module_params['megamenu'])) {
        $module_params['megamenu'] = "n";
    }
    if (! isset($module_params['megamenu_static'])) {
        $module_params['megamenu_static'] = "y";
    }
    if (empty($module_params['id']) && empty($module_params['structureId'])) {
        $smarty->assign('module_error', tr('One of these parameters has to be set:') . ' ' . tr('Menu') . ', ' . tr('Structure') . '.');
    }
    if (! empty($module_params['structureId'])) {
        $structlib = TikiLib::lib('struct');

        if (empty($module_params['title'])) {
            $smarty->assign('tpl_module_title', $module_params['structureId']);
        }
    }
    $smarty->assign('module_type', 'menu');
    $show_namespace = isset($module_params['show_namespace']) ? $module_params['show_namespace'] : 'y';
    $smarty->assign('show_namespace', $show_namespace);
}
