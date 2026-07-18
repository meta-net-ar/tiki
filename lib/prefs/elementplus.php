<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_elementplus_list()
{
    $elementPlusHelp = 'Element-Plus';

    return [
        'elementplus_autocomplete' => [
            'name' => tra('Element Plus Autocomplete'),
            'description' => tra('Provides various dropdown menus on many text input boxes for page names, user names, groups, tags, etc.'),
            'type' => 'flag',
            'help' => $elementPlusHelp,
            'default' => 'y',
            'dependencies' => [
                'feature_elementplus'
            ]
        ],
        'elementplus_select' => [
            'name' => tra('Element Plus Select'),
            'description' => tra('Provides a more advanced select input with features like clearable, collapsible tags, filterable, allow create, and sortable.'),
            'type' => 'flag',
            'help' => $elementPlusHelp,
            'default' => 'y',
            'dependencies' => [
                'feature_elementplus'
            ]
        ],
        'elementplus_select_clearable' => [
            'name' => tra('Clearable Select'),
            'description' => tra('whether select can be cleared'),
            'type' => 'flag',
            'help' => $elementPlusHelp,
            'default' => 'n',
            'dependencies' => [
                'elementplus_select'
            ]
        ],
        'elementplus_select_collapse_tags' => [
            'name' => tra('Collapsible Tags'),
            'description' => tra('whether to collapse tags to a text when multiple selecting'),
            'type' => 'flag',
            'help' => $elementPlusHelp,
            'default' => 'n',
            'dependencies' => [
                'elementplus_select'
            ]
        ],
        'elementplus_select_max_collapse_tags' => [
            'name' => tra('Max Collapse Tags'),
            'description' => tra('max tags to show when collapsed'),
            'type' => 'text',
            'help' => $elementPlusHelp,
            'default' => '3',
            'dependencies' => [
                'elementplus_select_collapse_tags',
                'elementplus_select'
            ]
        ],
        'elementplus_select_filterable' => [
            'name' => tra('Filterable Select'),
            'description' => tra('whether select can be filtered'),
            'type' => 'flag',
            'help' => $elementPlusHelp,
            'default' => 'y',
            'dependencies' => [
                'elementplus_select'
            ]
        ],
        'elementplus_select_allow_create' => [
            'name' => tra('Allow Create'),
            'description' => tra('whether creating new items is allowed'),
            'type' => 'flag',
            'help' => $elementPlusHelp,
            'default' => 'n',
            'dependencies' => [
                'elementplus_select_filterable',
                'elementplus_select'
            ],
        ],
        'elementplus_select_sortable' => [
            'name' => tra('Sortable'),
            'description' => tra('whether selected items can be re-ordered via drag and drop'),
            'type' => 'flag',
            'help' => $elementPlusHelp,
            'default' => 'n',
            'dependencies' => [
                'elementplus_select'
            ]
        ],
        'elementplus_upload' => [
            'name' => tra('Element Plus Upload'),
            'description' => tra('Provides a more advanced upload component for the file gallery with features like drag and drop, upload progress, preview, and more.'),
            'type' => 'flag',
            'help' => $elementPlusHelp,
            'default' => 'y',
            'dependencies' => [
                'feature_elementplus',
                'feature_file_galleries'
            ]
        ]
    ];
}
