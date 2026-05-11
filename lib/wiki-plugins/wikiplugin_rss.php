<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_rss_info()
{
    return [
        'name' => tra('RSS Feed'),
        'documentation' => 'PluginRSS',
        'description' => tra('Display items from one or more RSS feeds'),
        'prefs' => [ 'wikiplugin_rss' ],
        'iconname' => 'rss',
        'introduced' => 1,
        'format' => 'html',
        'filter' => 'striptags',
        'tags' => [ 'basic' ],
        'params' => [
            'id' => [
                'required' => false,
                'name' => tra('IDs'),
                'separator' => ':',
                'filter' => 'int',
                'description' => tr('List of feed IDs separated by colons (e.g., %0). You can find the IDs in the RSS Administration page:  %1', '<code>feedId:feedId2</code>', '<code>tiki-admin_rssmodules.php</code>.'),
                'since' => '1',
                'profile_reference' => 'rss',
            ],
            'url' => [
                'required' => false,
                'name' => tra('URL'),
                'filter' => 'url',
                'description' => tr('The full URL of the RSS feed. Use this parameter if you want to directly link to an RSS feed without adding it to the RSS Administration page.'),
                'since' => '29.0',
                'default' => '',
                'profile_reference' => 'rss',
            ],
            'refresh' => [
                'required' => false,
                'name' => tra('Refresh Interval'),
                'filter' => 'digits',
                'description' => tra('Refresh period in minutes, determining how frequently the RSS feed is updated.'),
                'since' => '29.0',
                'default' => 60,
                'profile_reference' => 'rss',
            ],
            'max' => [
                'required' => false,
                'name' => tra('Result Count'),
                'filter' => 'int',
                'description' => tra('Number of results displayed.'),
                'since' => '1',
                'default' => 10,
            ],
            'date' => [
                'required' => false,
                'name' => tra('Date'),
                'filter' => 'digits',
                'description' => tra('Show date of each item (not shown by default)'),
                'since' => '1',
                'default' => 0,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ]
            ],
            'desc' => [
                'required' => false,
                'name' => tra('Description'),
                'filter' => 'digits',
                'description' => tra('Show feed descriptions (not shown by default)'),
                'since' => '1',
                'default' => 0,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ]
            ],
            'author' => [
                'required' => false,
                'name' => tra('Author'),
                'filter' => 'digits',
                'description' => tra('Show authors (not shown by default)'),
                'since' => '1',
                'default' => 0,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ]
            ],
            'icon' => [
                'required' => false,
                'name' => tra('Icon'),
                'filter' => 'url',
                'description' => tra('URL to a favicon to put before each entry'),
                'since' => '5.0',
                'default' => '',
            ],
            'showtitle' => [
                'required' => false,
                'name' => tra('Show Title'),
                'filter' => 'digits',
                'description' => tra('Show the title of the feed (shown by default)'),
                'since' => '6.0',
                'default' => 1,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ]
            ],
            'ticker' => [
                'required' => false,
                'name' => tra('Ticker'),
                'filter' => 'digits',
                'description' => tra('Turn static feed display into ticker news like'),
                'since' => '10.1',
                'default' => 1,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ]
            ],
            'desclen' => [
                'required' => false,
                'name' => tra('Description Length'),
                'filter' => 'digits',
                'description' => tra('Max characters/length, truncates text to fit design'),
                'since' => '10.1',
                'default' => 0,
            ],
            'sortBy' => [
                'required' => false,
                'name' => tra('Sort By'),
                'filter' => 'text',
                'description' => tra('Sort by field'),
                'options' => [
                    ['text' => tra('Title'), 'value' => 'title'],
                    ['text' => tra('Date'), 'value' => 'publication_date'],
                    ['text' => tra('Author'), 'value' => 'author'],
                ],
                'default' => 'publication_date',
                'since' => '28.0',
                'advanced' => true,
            ],
            'sortOrder' => [
                'required' => false,
                'name' => tra('Sort Order'),
                'filter' => 'text',
                'description' => tra('Sort order'),
                'options' => [
                    ['text' => tra('Ascending'), 'value' => 'ASC'],
                    ['text' => tra('Descending'), 'value' => 'DESC'],
                ],
                'default' => 'DESC',
                'since' => '28.0',
                'advanced' => true,
            ],
            'tplWiki' => [
                'required' => false,
                'name' => tra('Template Wiki Page'),
                'description' => tra('Custom wiki page with smarty content to use for displaying feed items instead of the default template'),
                'filter' => 'text',
                'since' => '28.0',
                'advanced' => true,
            ]
        ],
    ];
}

function wikiplugin_rss($data, $params)
{
    $params = WikiPlugin_Helper::applySeparators($params, wikiplugin_rss_info());

    $rsslib = TikiLib::lib('rss');
    $params = array_merge(
        [
            'ticker' => 0,
        ],
        $params
    );

    if (is_null($params['id']) && empty($params['url'])) {
        return WikiParser_PluginOutput::argumentError([ 'id or url' ]);
    }

    if (! is_null($params['id']) && ! empty($params['url'])) {
        return WikiParser_PluginOutput::argumentError(['id or url, not both']);
    }

    $items = [];
    $title = null;

    if (! is_null($params['id'])) {
        $params['id'] = (array) $params['id'];
        $items = $rsslib->get_feed_items($params['id'], $params['max'], $params['sortBy'], $params['sortOrder']);
        if (count($params['id']) == 1) {
            $module = $rsslib->get_rss_module(reset($params['id']));
            if (isset($module['sitetitle'])) {
                $title = [
                    'title' => $module['sitetitle'],
                    'link' => $module['siteurl'],
                ];
            }
        }
    } elseif (! empty($params['url'])) {
        ['params' => $params, 'items' => $items, 'meta' => $title] = $rsslib->loadRss($params);
    }

    if ($params['desc'] > 0 && $params['desclen'] > 0) {
        foreach ($items as &$item) {
            $item['description'] = limit_text($item['description'], $params['desclen']);
        }
    }

    $smarty = TikiLib::lib('smarty');
    $smarty->assign('rsstitle', $title);
    $smarty->assign('items', $items);
    $smarty->assign('showdate', $params['date'] > 0);
    $smarty->assign('showtitle', $params['showtitle'] > 0);
    $smarty->assign('showdesc', $params['desc'] > 0);
    $smarty->assign('showauthor', $params['author'] > 0);
    $smarty->assign('icon', $params['icon']);
    $smarty->assign('ticker', $params['ticker']);

    $template = isset($params['tplWiki']) ? ('tplwiki:' . $params['tplWiki']) : 'wiki-plugins/wikiplugin_rss.tpl';
    return $smarty->fetch($template);
}

function limit_text($text, $limit)
{
    if (str_word_count($text, 0) > $limit) {
        $words = str_word_count($text, 2);
        $pos = array_keys($words);
        $text = substr($text, 0, $pos[$limit]) . '...';
    }
    return $text;
}
