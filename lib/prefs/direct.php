<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_direct_list()
{
    $paginationLinksHelp = 'Pagination-Links';

    return [
        'direct_pagination' => [
            'name' => tra('Use direct pagination links'),
            'description' => tra('Show page number links in pagination.'),
            'type' => 'flag',
            'help' => $paginationLinksHelp,
            'default' => 'y',
        ],
        'direct_pagination_max_middle_links' => [
            'name' => tra('Maximum number of links around the current item'),
            'description' => tra('Number of page links shown around the current page.'),
            'type' => 'text',
            'help' => $paginationLinksHelp,
            'units' => tra('links'),
            'size' => '4',
            'default' => 2,
        ],
        'direct_pagination_max_ending_links' => [
            'name' => tra('Maximum number of links after the first or before the last item'),
            'description' => tra('Number of page links always shown near the start and end of pagination.'),
            'type' => 'text',
            'help' => $paginationLinksHelp,
            'units' => tra('links'),
            'size' => '4',
            'default' => 0,
        ],
    ];
}
