<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_main_list()
{
    $shadowLayerHelp = 'Shadow-Layer';

    return [
        'main_shadow_start' => [
            'name' => tra('Main shadow start'),
            'description' => tra('Extra HTML before the main content area (with Shadow layer on). Pair with Main shadow end.'),
            'type' => 'textarea',
            'size' => '2',
            'default' => '',
            'help' => $shadowLayerHelp,
        ],
        'main_shadow_end' => [
            'name' => tra('Main shadow end'),
            'description' => tra('Extra HTML after the main content area.'),
            'type' => 'textarea',
            'size' => '2',
            'default' => '',
            'help' => $shadowLayerHelp,
        ],
    ];
}
