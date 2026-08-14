<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_box_list()
{
    $shadowLayersHelp = 'Shadow-Layers';

    return [
        'box_shadow_start' => [
            'name' => tra('Module (box) shadow start'),
            'description' => tra('Extra HTML before each boxed module (with Shadow layer on). Pair with Module shadow end.'),
            'type' => 'textarea',
            'help' => $shadowLayersHelp,
            'size' => '2',
            'default' => '',
        ],
        'box_shadow_end' => [
            'name' => tra('Module (box) shadow end'),
            'description' => tra('Extra HTML after each boxed module.'),
            'type' => 'textarea',
            'help' => $shadowLayersHelp,
            'size' => '2',
            'default' => '',
        ],
    ];
}
