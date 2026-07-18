<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_pagination_list()
{
    $paginationLinksHelp = 'Pagination-Links';

    return [
        'pagination_firstlast' => [
            'name' => tra("Display 'First' and 'Last' links"),
            'description' => tra('if set, will display \'first\' and \'last\' links on pages'),
            'type' => 'flag',
            'default' => 'y',
            'help' => $paginationLinksHelp,
        ],
        'pagination_fastmove_links' => [
            'name' => tra('Display "fast-forward" links'),
            'description' => tra('Display "fast-forward" links (to advance 10 percent of the total number of pages) '),
            'type' => 'flag',
            'default' => 'y',
            'help' => $paginationLinksHelp,
        ],
        'pagination_hide_if_one_page' => [
            'name' => tra('Hide pagination when there is only one page'),
            'description' => tra('Don\'t display pagination on single pages.'),
            'type' => 'flag',
            'default' => 'y',
            'help' => $paginationLinksHelp,
        ],
        'pagination_icons' => [
            'name' => tra('Use Icons'),
            'description' => tra('Show « and » symbols on previous and next pagination links instead of text labels.'),
            'type' => 'flag',
            'default' => 'y',
            'help' => $paginationLinksHelp,
        ],
    ];
}
