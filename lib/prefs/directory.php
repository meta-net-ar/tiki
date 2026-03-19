<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_directory_list()
{
    return [
        'directory_country_flag' => [
            'name' => tra('Show country flag'),
            'description' => tra('Show the country flag'),
            'type' => 'flag',
            'help' => 'Directory',
            'default' => 'y',
        ],
        'directory_cool_sites' => [
            'name' => tra('Enable "popular sites"'),
            'description' => tra('Show popular sites in the directory.'),
            'type' => 'flag',
            'help' => 'Directory',
            'default' => 'y',
        ],
        'directory_validate_urls' => [
            'name' => tra('Validate URLs'),
            'description' => tra('Should Tiki check the URL?'),
            'type' => 'flag',
            'help' => 'Directory',
            'default' => 'n',
        ],
        'directory_columns' => [
            'name'        => tra('Columns per page'),
            'description' => tra('Number of columns per page when listing directory categories'),
            'type'        => 'list',
            'help' => 'Directory',
            'units'       => tra('columns'),
            'options'     => [
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
                '5' => '5',
                '6' => '6',
            ],
            'default'     => 3,
        ],
        'directory_links_per_page' => [
            'name' => tra('Links per page'),
            'description' => tra('How many links should be displayed per page.'),
            'type' => 'text',
            'help' => 'Directory',
            'units' => tra('links'),
            'default' => 20,
            ],
        'directory_open_links' => [
            'name' => tra('Method to open Directory links'),
            'description' => tra('The linked-to website can be opened in various ways'),
            'type' => 'list',
            'help' => 'Directory',
            'options' => [
                'r' => tra('Replace the current window'),
                'n' => tra('Open a new window'),
                'f' => tra('Open an iframe')],
            'default' => 'n',
            ],
    ];
}
