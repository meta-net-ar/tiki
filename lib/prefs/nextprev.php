<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_nextprev_list()
{
    return [
        'nextprev_pagination' => [
            'name' => tra('Use relative (next / previous) pagination links'),
            'description' => tra('Show Previous and Next links in paginated listings to move one page at a time.'),
            'type' => 'flag',
            'default' => 'y',
            'help' => 'Pagination-Links',
        ],
    ];
}
