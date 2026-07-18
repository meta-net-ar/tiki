<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_forums_list()
{
    return [
        'forums_ordering' => [
            'name' => tra('Default order'),
            'description' => tra('Default sort order used to list forums in forum administration and listings.'),
            'type' => 'list',
            'help' => 'Forum-Admin',
            'options' => [
                'created_asc' => tra('Creation date (asc)'),
                'created_desc' => tra('Creation Date (desc)'),
                'threads_desc' => tra('Topics (desc)'),
                'comments_desc' => tra('Threads (desc)'),
                'lastPost_desc' => tra('Latest post (desc)'),
                'hits_desc' => tra('Visits (desc)'),
                'name_desc' => tra('Name (desc)'),
                'name_asc' => tra('Name (asc)'),
                'forumOrder_desc' => tra('Arbitrary (desc)'),
                'forumOrder_asc' => tra('Arbitrary (asc)'),
            ],
            'default' => 'created_desc',
        ],
        'forums_section_ordering' => [
            'name' => tra('Section order'),
            'description' => tra('Default sort order used to list forum sections.'),
            'type' => 'list',
            'help' => 'Forum-Listing',
            'options' => [
                'section_asc' => tra('Name (asc)'),
                'section_desc' => tra('Name (desc)'),
            ],
            'default' => 'section_asc',
        ],
    ];
}
