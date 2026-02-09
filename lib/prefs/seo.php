<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_seo_list()
{
    return [

        'seo_prevent_crawling' => [
            'name' => tr('Prevent entire site from being crawled'),
            'description' => tr('Blocks all search engine crawlers from indexing the site. Useful for development, staging, or private sites.'),
            'type' => 'flag',
            'default' => 'y',
            'keywords' => 'seo robots crawl search engine noindex block',
        ],
    ];
}
