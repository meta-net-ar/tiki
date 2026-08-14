<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_theme_list($partial = false)
{
    global $prefs;
    $themelib = TikiLib::lib('theme');

    //get list of themes and make the first character of array values uppercase
    $themes = array_map('ucfirst', $themelib->list_themes());
    //get list of theme options
    $theme_options = [
        '' => tr('None'),
    ];
    if (! $partial) {
        $theme_options = $theme_options + $themelib->get_options();
    }

    //admin themes -> add empty option which means that site theme should be used. Also remove Custom URL.
    $admin_themes = [
        '' => tr('Site theme'),
    ];
    $admin_themes = $admin_themes + $themes;
    unset($admin_themes['custom_url']); //remove custom URL from the list

    //get list of icon sets shipped by Tiki
    $iconsets = $themelib->list_base_iconsets();
    $iconsets['theme_specific_iconset'] = tr('Icons of the displayed theme'); //add a specific option to allow theme specific icon set to be used

    $themesHelp = 'Themes';

    return [
        'theme' => [
            'name' => tr('Site theme'),
            'description' => tr('The default theme for the site. Themes are bootstrap.css variants, including original Tiki themes as well as implementations of themes from Bootswatch.com. For more information about Bootstrap, see getbootstrap.com.'),
            'type' => 'list',
            'default' => 'default',
            'options' => $themes,
            'help' => $themesHelp,
            'tags' => ['basic'],
        ],
        'theme_option' => [
            'name' => tra('Site theme option'),
            'type' => 'list',
            'help' => $themesHelp,
            'description' => tra('Supplemental style sheet for the selected theme'),
            'options' => $theme_options,
            'default' => '',
            'tags' => ['basic'],
            'keywords' => tra('theme option, theme-option, style option, options, css'),
        ],
        'theme_custom_url' => [
            'name' => tr('Custom theme URL'),
            'description' => tr('Local or external URL of the custom Bootstrap-compatible CSS file to use.'),
            'type' => 'text',
            'filter' => 'url',
            'default' => '',
            'tags' => ['basic'],
            'help' => 'Look-and-Feel-Theme',
        ],
        'theme_unified_admin_backend' => [
            'name' => tra('Unified Admin Backend'),
            'type' => 'flag',
            'help' => $themesHelp,
            'description' => tra('Use modern layout for control panels.'),
            'default' => 'y',
            'tags' => ['basic'],
        ],
        'theme_admin' => [
            'name' => tra('Admin theme'),
            'type' => 'list',
            'help' => $themesHelp,
            'description' => tra('Theme for the settings panels and other administration pages'),
            'options' => $admin_themes,
            'default' => '',
            'tags' => ['basic'],
        ],
        'theme_option_admin' => [
            'name' => tra('Admin theme option'),
            'type' => 'list',
            'help' => $themesHelp,
            'description' => tra('Supplemental style sheet for the selected theme'),
            'options' => $theme_options,
            'default' => '',
            'tags' => ['basic'],
        ],
        'theme_navbar_color_variant_admin' => [
            'name'        => tra('Admin navbar background color'),
            'type'        => 'radio',
            'options'     => [
                'dark'  => tra('Dark'),
                'light' => tra('Light'),
            ],
            'help'        => $themesHelp,
            'description' => tra('Select a dark or light navbar (containing horizontal menu, etc.), as styled by the theme.'),
            'default'     => 'dark',
        ],
        'theme_option_includes_main' => [
            'name' => tra('Option theme includes main theme CSS'),
            'type' => 'flag',
            'help' => $themesHelp,
            'description' => tra('Don\'t include the main theme stylesheet because its contents are included in the option stylesheet.'),
            'default' => 'n',
        ],
        'theme_navbar_color_variant' => [
            'name' => tra('Navbar background color'),
            'type' => 'radio',
            'options' => [
                'dark' => tra('Dark'),
                'light' => tra('Light'),
            ],
            'help' => $themesHelp,
            'description' => tra('Select a dark or light navbar (containing horizontal menu, etc.), as styled by the theme.'),
            'default' => 'light',
        ],
        'theme_iconset' => [
            'name' => tr('Icons'),
            'description' => tr('Icon set used by the site.'),
            'type' => 'list',
            'options' => $iconsets,
            'default' => 'default',
            'help' => 'Icons',
            'tags' => ['basic'],
        ],
        'theme_customizer' => [
            'name' => tra('Theme Customizer tool'),
            'description' => tra('Activate the theme customizer tool to enable easy theme customization.'),
            'type' => 'flag',
            'help' => $themesHelp,
            'default' => 'n',
            'tags' => ['experimental'],
            'view' => TikiLib::lib('service')->getUrl(['controller' => 'styleguide', 'action' => 'show']),
        ],
        'theme_header_and_address_bar_color' => [
            'name' => tra('Header bar and Address bar color'),
            'type' => 'flag',
            'help' => $themesHelp,
            'description' => tra('Change the color of header bar and address bar according to the theme.'),
            'default' => 'n',
            'tags' => ['basic'],
        ],
        'theme_default_color_mode' => [
            'name' => tra('Default color mode'),
            'type' => 'list',
            'help' => 'Module-switch_color_mode',
            'description' => tra('Change the default color mode for active theme'),
            'options' => $prefs['color_modes_names'] ?? [],
            'default' => 'auto',
            'tags' => ['basic']
        ],
        'theme_model3dviewer_default_background' => [
            'name' => tra('Default 3D model background'),
            'description' => tra('Default background for 3D models. This can be overridden by the model3dviewer parameter in the plugin. ex: <code>#243d40</code> or <code>grey</code>'),
            'type' => 'text',
            'help' => 'Model3DViewer',
            'default' => '#ffffff',
            'keywords' => '3d model viewer glb gltf obj stl fbx 3ds ply background',
            'tags' => ['basic'],
        ],
    ];
}
