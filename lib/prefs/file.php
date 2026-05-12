<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_file_list()
{
    $commentsHelp = 'Comments';

    return [
        'file_galleries_comments_per_page' => [
            'name' => tra('Number per page'),
            'description' => tra('Number of comments per page'),
            'type' => 'text',
            'help' => $commentsHelp,
            'size' => '5',
            'units' => tra('comments'),
            'default' => 10,
        ],
        'file_galleries_comments_default_ordering' => [
            'name' => tra('Default order'),
            'description' => tra('Default order of comments.'),
            'type' => 'list',
            'help' => $commentsHelp,
            'options' => [
                'commentDate_desc' => tra('Newest first'),
                'commentDate_asc' => tra('Oldest first'),
                'points_desc' => tra('Points'),
            ],
            'default' => 'points_desc',
        ],
        'file_galleries_redirect_from_image_gallery' => [
            'name' => tra('Redirect migrated image gallery files to file galleries'),
            'description' => tra('If enabled, redirect all requests to images that were migrated from the image gallery to the corresponding file in the file gallery'),
            'type' => 'flag',
            'default' => 'n',
            'help' => 'File-Gallery-General-Settings',
        ],
    ];
}
