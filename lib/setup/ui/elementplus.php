<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

$elementPlus = [
    'select' => [
        'enabled' => $prefs['elementplus_select'] === 'y',
        'clearable' => $prefs['elementplus_select_clearable'] === 'y',
        'collapseTags' => $prefs['elementplus_select_collapse_tags'] === 'y',
        'maxCollapseTags' => (int) $prefs['elementplus_select_max_collapse_tags'],
        'filterable' => $prefs['elementplus_select_filterable'] === 'y',
        'allowCreate' => $prefs['elementplus_select_allow_create'] === 'y',
        'ordering' => $prefs['elementplus_select_sortable'] === 'y'
    ],
    'autocomplete' => $prefs['elementplus_autocomplete'] === 'y',
];

$headerlib->add_js_config('window.elementPlus = ' . json_encode($elementPlus) . ';');
