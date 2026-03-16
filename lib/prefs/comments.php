<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_comments_list()
{
    return [
        'comments_notitle' => [
            'name' => tra('Disable comment titles'),
            'description' => tra('Don\'t display the title input field on comments and their replies.'),
            'type' => 'flag',
            'default' => 'y',
        ],
        'comments_field_email' => [
            'name' => tra('Email field'),
            'description' => tra('Email field for comments (only for anonymous users).'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'comments_field_website' => [
            'name' => tra('Website field'),
            'description' => tra('Website field for comments (only for anonymous users).'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'comments_vote' => [
            'name' => tra('Use vote system for comments'),
            'description' => tra('Allow users with permission to vote on comments.'),
            'hint' => tr('Permissions involved: %0', 'vote_comments, wiki_view_comments, ratings_view_results'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'comments_archive' => [
            'name' => tra('Archive comments'),
            'description' => tra('If a comment is archived, only admins can see it'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'comments_allow_correction' => [
            'name' => tr('Allow comments to be edited by their author'),
            'description' => tr('Allow a comment to be modified by its author after posting it, for clarifications, correction of errors, etc.'),
            'type' => 'flag',
            'default' => 'y',
            'tags' => ['advanced'],
        ],
        'comments_correction_timeout' => [
            'name' => tra('Comment correction timeout'),
            'description' => tra('The time in minutes during which a comment can be modified by its author after posting it, for clarifications, correction of errors, etc.'),
            'type' => 'text',
            'filter' => 'digits',
            'units' => tra('minutes'),
            'default'  => 90,
            'tags' => ['advanced'],
            'dependencies' => [
                'comments_allow_correction',
            ],
        ],
        'comments_inline_annotator' => [
            'name' => tr('Inline comments using Apache Annotator'),
            'description' => tr('Use the Open/Apache Annotator JavaScript based library for managing inline comments as annotations.'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['advanced', 'experimental'],
            'dependencies' => [
                'feature_inline_comments',
            ],
            'keywords' => 'annotation annotatorjs',
        ],
        'comments_inline_annotator_with_info' => [
            'name' => tr('Show extra info'),
            'description' => tr('Show author and date on Open Annotator inline comments.'),
            'type' => 'flag',
            'default' => 'y',
            'tags' => ['advanced', 'experimental'],
            'dependencies' => [
                'comments_inline_annotator',
            ],
            'keywords' => 'annotation annotatorjs',
        ],
        'comments_heading_links' => [
            'name' => tr('Anchor links on headings'),
            'description' => tr('Displays a link icon on hover over each comment heading, allowing users to copy the link for easy sharing.'),
            'keywords' => 'Display hidden anchor on mouseover of headings',
            'type' => 'flag',
            'default' => 'y',
            'dependencies' => [],
        ],
        'comments_per_page'      => [
            'name'         => tr('Number of comments per page'),
            'type'         => 'text',
            'filter'       => 'digits',
            'default'      => 25,
            'dependencies' => [],
        ],
        'comments_sort_mode'     => [
            'name'    => tr('Sort mode for comments'),
            'type'    => 'list',
            'default' => 'commentDate_asc',
            'options' => [
                'commentDate_asc'  => tra('Oldest first'),
                'commentDate_desc' => tra('Newest first'),
            ],
        ],
        'comments_threshold_indent'     => [
            'name'    => tr('Limit indentation on thread reply'),
            'type'    => 'list',
            'default' => '5',
            'filter' => 'digits',
            'options' => [
                '5' => tra('Limit Indentation (to 5)'),
                '0' => tra('All indented'),
            ],
        ],
    ];
}
