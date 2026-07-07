<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function wikiplugin_titlesearch_info()
{
    return [
        'name' => tra('Title Search'),
        'documentation' => 'PluginTitleSearch',
        'description' => tra('Deprecated: Use PluginListPages instead. Search page titles.'),
        'prefs' => [ 'feature_wiki', 'wikiplugin_titlesearch' ],
        'iconname' => 'search',
        'introduced' => 1,
        'params' => [
            'search' => [
                'required' => true,
                'name' => tra('Search Criteria'),
                'description' => tra('Portion of a page name. Maps to the "find" parameter in ListPages.'),
                'since' => '1',
                'filter' => 'text',
                'default' => '',
            ],
            'info' => [
                'required' => false,
                'name' => tra('Information'),
                'description' => tra('Show page hits or user. Note: This is now controlled by wiki_list_hits and wiki_list_user preferences.'),
                'since' => '1',
                'filter' => 'alpha',
                'separator' => '|',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Hits'), 'value' => 'hits'],
                    ['text' => tra('User'), 'value' => 'user'],
                    ['text' => tra('Hits and user'), 'value' => 'hits|user'],
                    ['text' => tra('User and hits'), 'value' => 'user|hits']
                ]
            ],
            'exclude' => [
                'required' => false,
                'name' => tra('Exclude'),
                'description' => tra('Pipe-separated list of page names to exclude from results. Maps to "exclude_pages" in ListPages.'),
                'since' => '1',
                'filter' => 'text',
                'separator' => '|',
                'profile_reference' => 'wiki_page',
            ],
            'noheader' => [
                'required' => false,
                'name' => tra('No Header'),
                'description' => tr('Set to Yes (%0) to have no header for the search results.', '<code>1</code>'),
                'since' => '1',
                'filter' => 'digits',
                'default' => 0,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ]
            ],
        ],
    ];
}

function wikiplugin_titlesearch($data, $params)
{
    $params = WikiPlugin_Helper::applySeparators($params, wikiplugin_titlesearch_info());

    include_once('lib/wiki-plugins/wikiplugin_listpages.php');

    // Default parameters required by wikiplugin_listpages
    $listpagesParams = [];
    $info = wikiplugin_listpages_info();
    $listpagesParams = WikiPlugin_Helper::applyParamsDefaults($listpagesParams, $info);

    // Force list view (TitleSearch legacy behavior)
    $listpagesParams['showNameOnly'] = 'y';

    // Map parameters: search -> find
    if (isset($params['search'])) {
        $listpagesParams['find'] = $params['search'];
    }

    // Map parameters: exclude -> exclude_pages
    if (isset($params['exclude'])) {
        if (is_array($params['exclude'])) {
            $listpagesParams['exclude_pages'] = implode('|', $params['exclude']);
        } else {
            $listpagesParams['exclude_pages'] = $params['exclude'];
        }
    }

    if (isset($params['noheader'])) {
        $listpagesParams['noheader'] = $params['noheader'];
    }

    return wikiplugin_listpages($data, $listpagesParams);
}
