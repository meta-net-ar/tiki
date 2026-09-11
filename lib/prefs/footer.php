<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_footer_list()
{
    $shadowLayerHelp = 'Shadow-Layer';

    return [
        'footer_shadow_start' => [
            'name' => tra('Footer shadow div start'),
            'description' => tra('Extra HTML before the footer area (with Shadow layer on). Pair with Footer shadow div end.'),
            'type' => 'textarea',
            'help' => $shadowLayerHelp,
            'size' => '2',
            'default' => '',
        ],
        'footer_shadow_end' => [
            'name' => tra('Footer shadow div end'),
            'description' => tra('Extra HTML after the footer area.'),
            'type' => 'textarea',
            'help' => $shadowLayerHelp,
            'size' => '2',
            'default' => '',
        ],
    ];
}
