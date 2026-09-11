<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_art_list()
{
    $article_sort_orders = [
        'publishDate_desc' => tra('Newest first'),
    ];

    $prefslib = TikiLib::lib('prefs');
    $advanced_columns = $prefslib->getExtraSortColumns();

    foreach ($advanced_columns as $key => $label) {
        $article_sort_orders[ $key . '_asc' ] = $label . ' ' . tr('ascending');
        $article_sort_orders[ $key . '_desc' ] = $label . ' ' . tr('descending');
    }

    $articlesListingHelp = 'Articles-Listing';

    return [
        'art_sort_mode' => [
            'name' => tra('Article order'),
            'description' => tra('Default sort mode for the articles on the list-articles page'),
            'type' => 'list',
            'options' => $article_sort_orders,
            'default' => 'publishDate_desc',
            'help' => $articlesListingHelp,
        ],
        'art_home_title' => [
            'name' => tra('Title of articles homepage'),
            'type' => 'list',
            'description' => tr('Select the default title for the page that displays all articles.'),
            'options' => [
                '' => '',
                'topic' => tra('Topic'),
                'type' => tra('Type'),
                'articles' => tra('Articles'),
            ],
            'default' => '',
            'help' => 'Articles-General-Settings',
        ],
        'art_list_title' => [
            'name' => tra('Title'),
            'description' => tra('Show the title column in article listings.'),
            'type' => 'flag',
            'help' => $articlesListingHelp,
            'default' => 'y',
        ],
        'art_list_title_len' => [
            'name' => tra('Title length'),
            'description' => tra('Maximum number of characters shown for article titles in listings.'),
            'units' => tra('characters'),
            'type' => 'text',
            'help' => $articlesListingHelp,
            'size' => '5',
            'filter' => 'digits',
            'default' => '50',
        ],
        'art_list_type' => [
            'name' => tra('Type'),
            'description' => tra('Show the type column in article listings.'),
            'type' => 'flag',
            'help' => $articlesListingHelp,
            'default' => 'y',
        ],
        'art_list_topic' => [
            'name' => tra('Topic'),
            'description' => tra('Show the topic column in article listings.'),
            'type' => 'flag',
            'help' => $articlesListingHelp,
            'default' => 'y',
        ],
        'art_list_date' => [
            'name' => tra('Publication date'),
            'description' => tra('Show the publication date column in article listings.'),
            'type' => 'flag',
            'help' => $articlesListingHelp,
            'default' => 'y',
        ],
        'art_list_visible' => [
            'name' => tra('Visible'),
            'description' => tra('Show the visibility column in article listings.'),
            'type' => 'flag',
            'help' => $articlesListingHelp,
            'default' => 'n',
        ],
        'art_list_lang' => [
            'name' => tra('Language'),
            'description' => tra('Show the language column in article listings.'),
            'type' => 'flag',
            'help' => $articlesListingHelp,
            'default' => 'n',
        ],
        'art_list_author' => [
            'name' => tra('Author (owner)'),
            'description' => tra('Show the author (owner) column in article listings.'),
            'type' => 'flag',
            'help' => $articlesListingHelp,
            'default' => 'y',
        ],
        'art_list_authorName' => [
            'name' => tra('Author name (as displayed)'),
            'description' => tra('Show the displayed author name column in article listings.'),
            'type' => 'flag',
            'help' => $articlesListingHelp,
            'default' => 'n',
        ],
        'art_list_rating' => [
            'name' => tra('Author rating'),
            'description' => tra('Show the author rating column in article listings.'),
            'type' => 'flag',
            'help' => $articlesListingHelp,
            'default' => 'n',
        ],
        'art_list_usersRating' => [
            'name' => tra('Users rating'),
            'description' => tra('Show the users rating column in article listings.'),
            'type' => 'flag',
            'help' => $articlesListingHelp,
            'default' => 'n',
        ],
        'art_list_reads' => [
            'name' => tra('Reads'),
            'description' => tra('Show the reads column in article listings (requires Statistics).'),
            'type' => 'flag',
            'help' => $articlesListingHelp,
            'default' => 'y',
            'dependencies' => [
                'feature_stats',
            ],
        ],
        'art_list_size' => [
            'name' => tra('Size'),
            'description' => tra('Show the size column in article listings.'),
            'type' => 'flag',
            'help' => $articlesListingHelp,
            'default' => 'y',
        ],
        'art_list_img' => [
            'name' => tra('Images'),
            'description' => tra('Show the image column in article listings.'),
            'type' => 'flag',
            'help' => $articlesListingHelp,
            'default' => 'n',
        ],
        'art_list_id' => [
            'name' => tra('Id'),
            'description' => tra('Show the article ID column in article listings.'),
            'type' => 'flag',
            'help' => $articlesListingHelp,
            'default' => 'y',
        ],
        'art_list_ispublished' => [
            'name' => tra('Is published'),
            'description' => tra('Show the published status column in article listings.'),
            'type' => 'flag',
            'help' => $articlesListingHelp,
            'default' => 'y',
        ],
        'art_trailer_pos' => [
            'name' => tra('Trailer position'),
            'description' => tra('Where to show the article trailer (actions and metadata bar) on article view pages.'),
            'type' => 'list',
            'options' => [
                'top' => tra('Top'),
                'between' => tra('Between heading and body'),
            ],
            'default' => 'top',
            'help' => $articlesListingHelp,
        ],
        'art_header_text_pos' => [
            'name' => tra('Header text position'),
            'description' => tra('Header text position') . tra('Requires a smaller image for list view'),
            'type' => 'list',
            'options' => [
                'next' => tra('Next to image'),
                'below' => tra('Below image'),
            ],
            'default' => 'next',
            'help' => $articlesListingHelp,
        ],

    ];
}
