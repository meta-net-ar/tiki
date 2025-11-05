<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_articles_info()
{
    global $prefs;

    // Get topics and types for dropdown options
    $artlib = TikiLib::lib('art');
    $topics = $artlib->list_topics();
    $types = $artlib->list_types_byname();

    // Convert topics to options format
    $topicOptions = [];
    foreach ($topics as $topicId => $topic) {
        $topicOptions[] = ['text' => $topic['name'], 'value' => $topicId];
    }

    // Convert types to options format
    $typeOptions = [];
    foreach ($types as $typeName => $type) {
        $typeOptions[] = ['text' => $typeName, 'value' => $typeName];
    }

    return [
        'name' => tra('Article List'),
        'documentation' => 'PluginArticles',
        'description' => tra('Display multiple articles'),
        'prefs' => [ 'feature_articles', 'wikiplugin_articles' ],
        'iconname' => 'articles',
        'tags' => [ 'basic' ],
        'introduced' => 1,
        'params' => [
            'usePagination' => [
                'required' => false,
                'name' => tra('Use Pagination'),
                'description' => tr('Activate pagination when the articles list is long. Default is %0', '<code>n</code>'),
                'filter' => 'alpha',
                'default' => 'n',
                'since' => '1',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
            ],
            'max' => [
                'required' => false,
                'name' => tra('Maximum Displayed'),
                'description' => tr('The number of articles to display in the list (use %0 to show all)', '<code>-1</code>'),
                'filter' => 'int',
                'since' => '1',
                'default' => $prefs['maxRecords'],
            ],
            'topicId' => [
                'required' => false,
                'name' => tra('Topic Filter'),
                'description' => tra('Filter articles by topic. You can select multiple topics from the dropdown. Use negation (e.g., !topic1+topic2) to exclude articles from specific topics.'),
                'filter' => 'striptags',
                'accepted' => tra('Valid topic IDs'),
                'profile_reference' => 'article_topic',
                'since' => '2.0',
                'options' => $topicOptions,
                'separator' => '+',
                'select_multiple' => 'y',
            ],
            'type' => [
                'required' => false,
                'name' => tra('Type Filter'),
                'description' => tra('Filter the list of articles by types. Example: ') . '<code>[!]type+type+type</code>',
                'filter' => 'striptags',
                'since' => '1',
                'accepted' => tra('Valid article types'),
                'profile_reference' => 'article_type',
                'options' => $typeOptions,
                'separator' => '+',
                'select_multiple' => 'y',
            ],
            'categId' => [
                'required' => false,
                'name' => tra('Category Filter'),
                'description' => tra('Filter articles by category. You can select multiple categories from the dropdown. Only articles in all selected categories will be listed.'),
                'filter' => 'digits',
                'profile_reference' => 'category',
                'since' => '1',
                'separator' => '|',
            ],
            'lang' => [
                'required' => false,
                'name' => tra('Language'),
                'description' => tra('List only articles in this language'),
                'filter' => 'lang',
                'since' => '1',
                'default' => '',
            ],
            'sort' => [
                'required' => false,
                'name' => tra('Sort Order'),
                'description' => tra('Choose how to sort the articles. Default is by publication date (newest first).'),'filter' => 'word',
                'default' => 'publishDate_desc',
                'since' => '2.0',
                'options' => [
                    ['text' => tra('Publication Date (Newest First)'), 'value' => 'publishDate_desc'],
                    ['text' => tra('Publication Date (Oldest First)'), 'value' => 'publishDate_asc'],
                    ['text' => tra('Title (A-Z)'), 'value' => 'title_asc'],
                    ['text' => tra('Title (Z-A)'), 'value' => 'title_desc'],
                    ['text' => tra('Author Name (A-Z)'), 'value' => 'authorName_asc'],
                    ['text' => tra('Author Name (Z-A)'), 'value' => 'authorName_desc'],
                    ['text' => tra('Article ID (Lowest First)'), 'value' => 'articleId_asc'],
                    ['text' => tra('Article ID (Highest First)'), 'value' => 'articleId_desc'],
                    ['text' => tra('Topic Name (A-Z)'), 'value' => 'topicName_asc'],
                    ['text' => tra('Topic Name (Z-A)'), 'value' => 'topicName_desc'],
                    ['text' => tra('Creation Date (Newest First)'), 'value' => 'created_desc'],
                    ['text' => tra('Creation Date (Oldest First)'), 'value' => 'created_asc'],
                    ['text' => tra('Language (A-Z)'), 'value' => 'lang_asc'],
                    ['text' => tra('Language (Z-A)'), 'value' => 'lang_desc'],
                    ['text' => tra('Random Order'), 'value' => 'random'],
                ],
            ],
            'order' => [
                'required' => false,
                'name' => tra('Specific order'),
                'description' => tra('List of ArticleId that must appear in this order if present'),
                'filter' => 'digits',
                'separator' => '|',
                'since' => '9.0',
            ],
            'articleId' => [
                'required' => false,
                'name' => tra('Only these articles'),
                'description' => tr('List of article IDs to display, separated by "%0"', '<code>|</code>'),
                'filter' => 'digits',
                'separator' => '|',
                'profile_reference' => 'article',
                'since' => '9.0',
            ],
            'notArticleId' => [
                'required' => false,
                'name' => tra('Not these articles'),
                'description' => tra('List of article IDs to not display, separated by "%0"', '<code>|</code>'),
                'filter' => 'digits',
                'separator' => '|',
                'profile_reference' => 'article',
                'since' => '5.0',
            ],
            'quiet' => [
                'required' => false,
                'name' => tra('Quiet'),
                'description' => tra('Whether to not report when there are no articles (no reporting by default)'),
                'filter' => 'alpha',
                'default' => 'n',
                'since' => '1',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'titleonly' => [
                'required' => false,
                'name' => tra('Title Only'),
                'description' => tra('Whether to only show the title of the articles (not set to title only by default)'),
                'filter' => 'alpha',
                'since' => '1',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'fullbody' => [
                'required' => false,
                'name' => tra('Show Article Body'),
                'description' => tra('Whether to show the body of the articles instead of the heading (not set by default).'),
                'filter' => 'alpha',
                'default' => 'n',
                'since' => '5',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'start' => [
                'required' => false,
                'name' => tra('Starting Article'),
                'description' => tra('The article number that the list should start with (starts with first article by
                    default)') . '. ' . tra('This will not work if Pagination is used.'),
                'filter' => 'int',
                'since' => '1',
                'default' => 0,
            ],
            'dateStart' => [
                'required' => false,
                'name' => tra('Start Date'),
                'description' => tra('Earliest date to select articles from.') . tr(' (%0YYYY-MM-DD%1)', '<code>', '</code>'),
                'filter' => 'date',
                'default' => '',
                'since' => '5.0',
            ],
            'dateEnd' => [
                'required' => false,
                'name' => tra('End date'),
                'description' => tra('Latest date to select articles from.') . tr(' (%0YYYY-MM-DD%1)', '<code>', '</code>'),
                'filter' => 'date',
                'default' => '',
                'since' => '5.0',
            ],
            'periodQuantity' => [
                'required' => false,
                'name' => tra('Period quantity'),
                'description' => tr('Numeric value to display only last articles published within a user defined
                    time-frame. Used in conjunction with the next parameter "Period unit", this parameter indicates how
                    many of those units are to be considered to define the time frame. If this parameter is set,
                    "Start Date" and "End Date" are ignored.'),
                'filter' => 'digits',
                'since' => '1',
            ],
            'periodUnit' => [
                'required' => false,
                'name' => tra('Period unit'),
                'description' => tr('Time unit used with "Period quantity"'),
                'filter' => 'word',
                'since' => '1',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tr('Hour'), 'value' => 'hour'],
                    ['text' => tr('Day'), 'value' => 'day'],
                    ['text' => tr('Week'), 'value' => 'week'],
                    ['text' => tr('Month'), 'value' => 'month'],
                ],
            ],
            'overrideDates' => [
                'required' => false,
                'name' => tra('Override Dates'),
                'description' => tra('Whether to comply with the article type\'s "show before publish" settings (not complied with by default)'),
                'filter' => 'alpha',
                'default' => 'n',
                'since' => '1',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'containerClass' => [
                'required' => false,
                'name' => tra('Containing class'),
                'description' => tr(
                    'CSS class to add to the containing "div.article" (default: "%0")',
                    '<code>wikiplugin_articles</code>'
                ),
                'filter' => 'text',
                'since' => '1',
                'accepted' => tra('Valid CSS class'),
                'default' => 'wikiplugin_articles',
            ],
            'largefirstimage' => [
                'required' => false,
                'name' => tra('Large First Image'),
                'description' => tr('If set to %0 (Yes), the first image will be displayed with the dimension used to
                    view of the article', '<code>y</code>'),
                'filter' => 'alpha',
                'default' => 'n',
                'since' => '6.0',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'urlparam' => [
                'required' => false,
                'name' => tra('Additional URL parameter for the link to read the article'),
                'filter' => 'text',
                'default' => '',
                'since' => '6.0',
            ],
            'actions' => [
                'required' => false,
                'name' => tra('Show actions (buttons and links)'),
                'description' => tra('Whether to show the buttons and links to do actions on each article (for the
                    actions you have permission to do'),
                'filter' => 'alpha',
                'default' => 'n',
                'since' => '6.1',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'translationOrphan' => [
                'required' => false,
                'name' => tra('No translation'),
                'description' => tra('User- or pipe-separated list of two-letter language codes for additional languages
                    to display. List pages with no language or with a missing translation in one of the language'),
                'filter' => 'alpha',
                'separator' => '|',
                'since' => '1',
            ],
            'useLinktoURL' => [
                'required' => false,
                'name' => tra('Use Source URL'),
                'description' => tra('Use the external source URL as link for articles.'),
                'filter' => 'alpha',
                'since' => '1',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
        ],
    ];
}

function wikiplugin_articles($data, $params)
{
    global $prefs, $pageLang;
    $smarty = TikiLib::lib('smarty');
    $tikilib = TikiLib::lib('tiki');
    $artlib = TikiLib::lib('art');

    if (! isset($params['topic'])) {
        $params['topic'] = '';
    }
    if (! isset($params['headerLinks'])) {
        $params['headerLinks'] = 'n';
    }
    if (! isset($params['showtable'])) {
        $params['showtable'] = 'n';
    }

    $auto_args = ['lang', 'topicId', 'topic', 'sort', 'type', 'lang', 'categId'];

    extract($params, EXTR_SKIP);
    $filter = [];
    if ($prefs['feature_articles'] != 'y') {
        //  the feature is disabled or the user can't read articles, not even article headings
        return("");
    }

    $urlnext = '';
    if ($usePagination == 'y') {
        //Set offset when pagination is used
        $start = $_REQUEST["offset"] ?? 0;
        foreach ($auto_args as $arg) {
            if (! empty($$arg)) {
                $paramsnext[$arg] = $$arg;
            }
        }
        $paramsnext['_type'] = 'absolute_path';
        $urlnext = smarty_function_query($paramsnext, $smarty->getEmptyInternalTemplate());
    }

    $smarty->assign_by_ref('quiet', $quiet);
    $smarty->assign_by_ref('urlparam', $urlparam);
    $smarty->assign_by_ref('urlnext', $urlnext);
    $smarty->assign_by_ref('useLinktoURL', $useLinktoURL);

    $smarty->assign('container_class', $containerClass);

    $dateStartTS = 0;
    $dateEndTS = 0;

    // if a period of time is set, date start and end are ignored
    if (! is_null($periodQuantity)) {
        $periodQuantity = match ($periodQuantity) {
            'hour' => 3600,
            'day' => 86400,
            'week' => 604800,
            'month' => 2628000,
        };

        if (is_int($periodUnit)) {
            $dateStartTS = $tikilib->now - ($periodQuantity * $periodUnit);
            $dateEndTS = $tikilib->now;
        }
    } else {
        $dateStartTS = strtotime($dateStart);
        $dateEndTS = strtotime($dateEnd);
    }

    $smarty->assign('fullbody', $fullbody);
    $smarty->assign('largefirstimage', $largefirstimage);

    if (! is_null($translationOrphan)) {
        $filter['translationOrphan'] = $translationOrphan;
    }
    if (! is_null($articleId)) {
        $filter['articleId'] = $articleId;
    }
    if (! is_null($notArticleId)) {
        $filter['notArticleId'] = $notArticleId;
    }

    if (! is_null($topicId)) {
        if (is_array($topicId)) {
            $separator = $pluginInfo['params']['topicId']['separator'] ?? '+';
            $topicId = implode($separator, $topicId);
        }
        $filter['topicId'] = $topicId;
    }

    if (! is_null($type)) {
        if (is_array($type)) {
            $separator = $pluginInfo['params']['type']['separator'] ?? '+';
            $type = implode($separator, $type);
        }
        $filter['type'] = $type;
    }

    if (! is_array($categId) || count($categId) == 0) {
        $categIds = '';
    } elseif (count($categId) == 1) {
        // For performance reasons, if there is only one value, the SQL query should not return IN () as it does with arrays
        // So we send a single value instead of a single-value array
        $categIds = $categId[0];
    } else {
        // We want the list of articles which are in all categories
        $categIds = [ 'AND' => $categId];
    }

    $listpages = $artlib->list_articles($start, $max, $sort, '', $dateStartTS, $dateEndTS, 'admin', $type, $topicId, 'y', $topic, $categIds, '', '', $lang, '', '', ($overrideDates == 'y'), 'y', $filter);
    if ($prefs['feature_multilingual'] == 'y' && is_null($translationOrphan)) {
        $multilinguallib = TikiLib::lib('multilingual');
        $listpages['data'] = $multilinguallib->selectLangList('article', $listpages['data'], $pageLang);
        foreach ($listpages['data'] as &$article) {
            $article['translations'] = $multilinguallib->getTranslations('article', $article['articleId'], $article["title"], $article['lang']);
        }
    }

    for ($i = 0, $icount_listpages = count($listpages["data"]); $i < $icount_listpages; $i++) {
        $listpages["data"][$i]["parsed_heading"] = TikiLib::lib('parser')->parse_data(
            $listpages["data"][$i]["heading"],
            [
                'min_one_paragraph' => true,
                'is_html' => $artlib->is_html($listpages["data"][$i], true),
                'objectType' => 'articles',
                'objectId' => $listpages["data"][$i]['articleId'],
                'fieldName' => 'heading'
            ]
        );
        if ($fullbody == 'y') {
            $listpages["data"][$i]["parsed_body"] = TikiLib::lib('parser')->parse_data(
                $listpages["data"][$i]["body"],
                [
                    'min_one_paragraph' => true,
                    'is_html' => $artlib->is_html($listpages["data"][$i]),
                    'objectType' => 'articles',
                    'objectId' => $listpages["data"][$i]['articleId'],
                    'fieldName' => 'body'
                ]
            );
        }
        $comments_prefix_var = 'article:';
        $comments_object_var = $listpages["data"][$i]["articleId"];
        $comments_objectId = $comments_prefix_var . $comments_object_var;
        $listpages["data"][$i]["comments_count"] = TikiLib::lib('comments')->count_comments($comments_objectId);
    }

    $topics = $artlib->list_topics();
    $smarty->assign_by_ref('topics', $topics);

    if (! empty($topic) && ! str_contains($topic, '!') && ! str_contains($topic, '+')) {
        $smarty->assign_by_ref('topic', $topic);
    } elseif (! empty($topicId) &&  is_numeric($topicId)) {
        $smarty->assign_by_ref('topicId', $topicId);
        if (! empty($listpages['data'][0]['topicName'])) {
            $smarty->assign_by_ref('topic', $listpages['data'][0]['topicName']);
        } else {
            $topic_info = $artlib->get_topic($topicId);
            if (isset($topic_info['name'])) {
                $smarty->assign_by_ref('topic', $topic_info['name']);
            }
        }
    } elseif (empty($topicId)) {
        $smarty->assign_by_ref('topicId', $topicId);
    }
    if (! empty($type) && ! str_contains($type, '!') && ! str_contains($type, '+')) {
        $smarty->assign_by_ref('type', $type);
    } elseif (empty($type)) {
        $smarty->assign_by_ref('type', $type);
    }

    if ($usePagination == 'y') {
        $smarty->assign('maxArticles', $max);
        $smarty->assign_by_ref('offset', $start);
        $smarty->assign_by_ref('count', $listpages['count']);
    }
    if (! is_null($order)) {
        foreach ($listpages['data'] as $i => $article) {
            $memo[$article['articleId']] = $i;
        }
        foreach ($order as $articleId) {
            if (isset($memo[$articleId])) {
                $list[] = $listpages['data'][$memo[$articleId]];
            }
        }
        foreach ($listpages['data'] as $i => $article) {
            if (! in_array($article['articleId'], $order)) {
                $list[] = $article;
            }
        }
        $smarty->assign_by_ref('listpages', $list);
    } else {
        $smarty->assign_by_ref('listpages', $listpages["data"]);
    }
    $smarty->assign('usePagination', $usePagination);
    $smarty->assign_by_ref('actions', $actions);
    $smarty->assign('headerLinks', $headerLinks);

    if ($titleonly == 'y') {
        return "~np~ " . $smarty->fetch('tiki-view_articles-titleonly.tpl') . " ~/np~";
    } else {
        return "~np~ " . $smarty->fetch('tiki-view_articles.tpl') . " ~/np~";
    }
}
