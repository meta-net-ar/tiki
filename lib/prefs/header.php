<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_header_list()
{
    $customizationHelp = 'Customization';
    $shadowLayersHelp = 'Shadow-Layers';

    return [
        'header_shadow_start' => [
            'name' => tra('Header shadow div start'),
            'description' => tra('Extra HTML before the header area (with Shadow layer on). Pair with Header shadow div end.'),
            'type' => 'textarea',
            'size' => '2',
            'default' => '',
            'help' => $shadowLayersHelp,
        ],
        'header_shadow_end' => [
            'name' => tra('Header shadow div end'),
            'description' => tra('Extra HTML after the header area.'),
            'type' => 'textarea',
            'size' => '2',
            'default' => '',
            'help' => $shadowLayersHelp,
        ],
        'header_custom_css' => [
            'name' => tra('Custom CSS'),
            'description' => tra('Additional CSS rules can be entered here and will apply to all pages, or the CSS ID of a page can be used to limit the scope of the rule (check the HTML source of the particular page to find its body ID tag.)'),
            'help' => $customizationHelp,
            'type' => 'textarea',
            'size' => 5,
            'default' => '',
            'filter' => 'none',
        ],
        'header_custom_js' => [
            'name' => tra('Custom JavaScript'),
            'description' => tra('Includes a block of inline JavaScript after the inclusion of jQuery and other JavaScript libs in all pages.'),
            'type' => 'textarea',
            'help' => $customizationHelp,
            'size' => 5,
            'hint' => tr('Use [https://doc.tiki.org/PluginJS|PluginJS] to include Javascript on a single wiki page.'),
            'default' => '',
            'shorthint' => tra('Do not include the < script > tags.'),
        ],
    ];
}
