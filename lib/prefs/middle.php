<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_middle_list()
{
    $shadowLayersHelp = 'Shadow-Layers';

    return [
        'middle_shadow_start' => [
            'name' => tra('Middle shadow div start'),
            'description' => tra('Extra HTML before the middle content area (with Shadow layer on). Pair with Middle shadow div end.'),
            'type' => 'textarea',
            'size' => '2',
            'default' => '',
            'help' => $shadowLayersHelp,
        ],
        'middle_shadow_end' => [
            'name' => tra('Middle shadow div end'),
            'description' => tra('Extra HTML after the middle content area.'),
            'type' => 'textarea',
            'size' => '2',
            'default' => '',
            'help' => $shadowLayersHelp,
        ],
    ];
}
