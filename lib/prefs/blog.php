<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_blog_list()
{
    $blogsGeneralSettingsHelp = 'Blogs-General-Settings';
    $blogListingsHelp = 'Blog-Listings';

    return [
        'blog_comments_per_page' => [
            'name' => tra('Number per page'),
            'description' => tra('Set the number of comments per page.'),
            'type' => 'text',
            'size' => '3',
            'units' => tra('comments'),
            'default' => 0,
            'help' => $blogsGeneralSettingsHelp,
        ],
        'blog_comments_default_ordering' => [
            'name' => tra('Default ordering'),
            'description' => tra('Set the display order of comments.'),
            'type' => 'list',
            'options' => [
                'commentDate_desc' => tra('Newest first'),
                'commentDate_asc' => tra('Oldest first'),
                'points_desc' => tra('Points'),
            ],
            'default' => 'commentDate_asc',
            'help' => $blogsGeneralSettingsHelp,
        ],
        'blog_list_order' => [
            'name' => tra('Default order'),
            'description' => tra('Default sort order for blogs on the list-blogs page.'),
            'type' => 'list',
            'options' => [
                'created_desc' => tra('Creation Date (desc)'),
                'lastModif_desc' => tra('Last modification date (desc)'),
                'title_asc' => tra('Blog title (asc)'),
                'posts_desc' => tra('Number of posts (desc)'),
                'hits_desc' => tra('Visits (desc)'),
                'activity_desc' => tra('Activity (desc)'),
            ],
            'default' => 'created_desc',
            'help' => $blogsGeneralSettingsHelp,
        ],
        'blog_list_title' => [
            'name' => tra('Title'),
            'description' => tra('Show the title column in blog listings.'),
            'type' => 'flag',
            'default' => 'y',
            'help' => $blogListingsHelp,
        ],
        'blog_list_title_len' => [
            'name' => tra('Title length'),
            'description' => tra('Maximum number of characters shown for blog titles in listings.'),
            'type' => 'text',
            'size' => '3',
            'units' => tra('characters'),
            'default' => '35',
            'help' => $blogListingsHelp,
        ],
        'blog_list_description' => [
            'name' => tra('Description'),
            'description' => tra('Show the description column in blog listings.'),
            'type' => 'flag',
            'default' => 'y',
            'help' => $blogListingsHelp,
        ],
        'blog_list_created' => [
            'name' => tra('Creation date'),
            'description' => tra('Show the creation date column in blog listings.'),
            'type' => 'flag',
            'default' => 'y',
            'help' => $blogListingsHelp,
        ],
        'blog_list_lastmodif' => [
            'name' => tra('Last modified'),
            'description' => tra('Show the last modified column in blog listings.'),
            'type' => 'flag',
            'default' => 'y',
            'help' => $blogListingsHelp,
        ],
        'blog_list_user' => [
            'name' => tra('User'),
            'description' => tra('How to display the blog author in blog listings.'),
            'type' => 'list',
            'options' => [
                'disabled' => tra('Disabled'),
                'text' => tra('Plain text'),
                'link' => tra('Link to user information'),
                'avatar' => tra('User profile picture'),
            ],
            'default' => 'text',
            'help' => $blogListingsHelp,
        ],
        'blog_list_posts' => [
            'name' => tra('Posts'),
            'description' => tra('Show the number of posts column in blog listings.'),
            'type' => 'flag',
            'default' => 'y',
            'help' => $blogListingsHelp,
        ],
        'blog_list_visits' => [
            'name' => tra('Visits'),
            'description' => tra('Show the visits column in blog listings.'),
            'type' => 'flag',
            'default' => 'y',
            'help' => $blogListingsHelp,
        ],
        'blog_list_activity' => [
            'name' => tra('Activity'),
            'description' => tra('Show the activity column in blog listings.'),
            'type' => 'flag',
            'default' => 'n',
            'help' => $blogListingsHelp,
        ],
        'blog_sharethis_publisher' => [
            'name' => tra('Your ShareThis publisher identifier (optional)'),
            'description' => tra('Set to define your ShareThis publisher identifier'),
            'type' => 'text',
            'size' => '40',
            'default' => '',
            'help' => $blogsGeneralSettingsHelp,
        ],
        'blog_feature_copyrights' => [
            'name' => tra('Blog post copyright'),
            'description' => tra('Apply copyright management preferences to this feature.'),
            'type' => 'flag',
            'dependencies' => [
                'feature_blogs',
            ],
            'default' => 'n',
            'help' => $blogsGeneralSettingsHelp,
        ],
    ];
}
