<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_menus_list()
{
    $menuIconsHelp = 'Menu#Add_icons_to_each_option_of_a_menu';

    return [
        'menus_items_icons' => [
            'name' => tra('Menu icons'),
            'description' => tra('Allows icons to be defined for menu entries'),
            'type' => 'flag',
            'default' => 'n',
            'help' => $menuIconsHelp,
        ],
        'menus_items_icons_path' => [
            'name' => tra('Default path for the icons'),
            'description' => tra('Default directory path for menu item icon image files (for example: img/icons/large).'),
            'type' => 'text',
            'default' => 'img/icons/large',
            'help' => $menuIconsHelp,
        ],
        'menus_edit_icon' => [
            'name' => tra('Edit menu icon'),
            'description' => tra('Adds an icon on the navbar to edit menu entries'),
            'type' => 'flag',
            'default' => 'n',
            'help' => $menuIconsHelp,
        ],
    ];
}
