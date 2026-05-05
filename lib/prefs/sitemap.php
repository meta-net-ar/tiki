<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_sitemap_list()
{
    return [
        'sitemap_enable' => [
            'name' => tra('Sitemap protocol'),
            'description' => tra('Allows generating site maps based on the Sitemap protocol, in the form of XML documents. Mostly used to facilitate indexation of a site by web search engines.'),
            'type' => 'flag',
            'help' => 'https://www.sitemaps.org/protocol.html',
            'default' => 'n',
            'since' => '18',
            'tags' => ['advanced'],
            'admin' => 'tiki-admin_sitemap.php',
        ],
        'sitemap_method' => [
            'name' => tra('Generation Method'),
            'description' => tra('Choose how sitemap files are generated and updated.'),
            'type' => 'list',
            'help' => 'Sitemap',
            'options' => [
                'auto' => tra('Automatic'),
                'manual' => tra('Manual'),
            ],
            'since' => '30',
            'default' => 'auto',
        ],
        'sitemap_split' => [
            'name' => tra('Sitemap Splitting'),
            'description' => tra('Split sitemap files by time periods to optimize file size and crawl efficiency.'),
            'type' => 'list',
            'options' => [
                'none' => tra('No split'),
                'year' => tra('Per Year'),
                'year_month' => tra('Per Year–Month (recommended for large sites)'),
            ],
            'since' => '30',
            'tags' => ['advanced'],
            'default' => 'none',
        ],
    ];
}
