<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'cms';
//get_strings tra('Articles Home');

$inputConfiguration = [
    [
        'staticKeyFilters'      => [
            'remove'            => 'int',           //post
            'sort_mode'         => 'alnumdash',     //get
            'offset'            => 'int',           //get
            'date_min'          => 'digits',        //post
            'date_max'          => 'digits',        //post
            'min_rating'        => 'digits',        //post
            'max_rating'        => 'digits',        //post
            'type'              => 'striptags',        //post
            'topic'             => 'int',        //post
            'topicName'         => 'striptags',        //post
            'categId'           => 'int',           //post
            'lang'              => 'lang',          //post
            'find'              => 'striptags',     //get
            'watch_action'      => 'alpha',     //get
        ]
    ],
];
require_once('tiki-setup.php');
$artlib = TikiLib::lib('art');
$categlib = TikiLib::lib('categ');

if ($prefs['article_use_new_list_articles'] == 'y') {
    include "lists/articles.php";
    die;
}

if ($prefs['feature_freetags'] == 'y') {
    $freetaglib = TikiLib::lib('freetag');
}
if ($prefs['feature_categories'] == 'y') {
    $categlib = TikiLib::lib('categ');
}

$access->check_feature('feature_articles');

if (isset($_REQUEST["remove"]) && $access->checkCsrf()) {
    $artperms = Perms::get([ 'type' => 'article', 'object' => $_REQUEST['remove'] ]);
    if ($artperms->remove_article != 'y') {
        Feedback::errorAndDie(tra("You do not have permission to remove articles"), \Laminas\Http\Response::STATUS_CODE_401);
    }
    $artlib->remove_article($_REQUEST["remove"]);
}
// This script can receive the threshold
// for the information as the number of
// days to get in the log 1,3,4,etc
// it will default to 1 recovering information for today
if (empty($_REQUEST["sort_mode"])) {
    $sort_mode = $prefs['art_sort_mode'];
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$smarty->assign_by_ref('sort_mode', $sort_mode);
// If offset is set use it if not then use offset =0
// use the maxRecords php variable to set the limit
// if sortMode is not set then use lastModif_desc
$offset = $_REQUEST["offset"] ?? 0;
$smarty->assign_by_ref('offset', $offset);
if (isset($_REQUEST['date_min']) || isset($_REQUEST['date_max'])) {
    $date_min = $_REQUEST['date_min'] ?? 0;
    $date_max = $_REQUEST['date_max'] ?? $tikilib->now;
} elseif (isset($_SESSION["thedate"])) {
    $date_min = 0;
    if ($_SESSION["thedate"] < $tikilib->now) {
        $date_max = $_SESSION["thedate"];
    } else {
        if ($tiki_p_admin == 'y' || $tiki_p_admin_cms == 'y') {
            $date_max = $_SESSION["thedate"];
        } else {
            $date_max = $tikilib->now;
        }
    }
} else {
    $date_min = 0;
    $date_max = $tikilib->now;
}
//Keep track of month of last viewed article for article months_links module foldable display
$_SESSION['cms_last_viewed_month'] = TikiLib::date_format("%Y-%m", $date_max);
$min_rating = $_REQUEST['min_rating'] ?? '';
$max_rating = $_REQUEST['max_rating'] ?? '';
$find = $_REQUEST["find"] ?? '';

$smarty->assign_by_ref('find', $find);
if (! empty($_REQUEST["type"])) {
    $type = $_REQUEST["type"];
    if (empty($artlib->get_type($type))) {
        Feedback::errorAndDie(tra("Invalid article type or it has been deleted."), \Laminas\Http\Response::STATUS_CODE_400);
    }
} else {
    $type = '';
}
if (! empty($_REQUEST["topic"])) {
    $topic = $_REQUEST["topic"];
    if (empty($artlib->get_topic($topic))) {
         Feedback::errorAndDie(tra("Invalid article topic or it has been deleted."), \Laminas\Http\Response::STATUS_CODE_400);
    }
} else {
    $topic = '';
}
if (! empty($_REQUEST['topicName'])) {
    $topicName = $_REQUEST['topicName'];
    if (empty($artlib->get_topic($topicName))) {
        Feedback::errorAndDie(tra("Invalid article topic name or it has been deleted."), \Laminas\Http\Response::STATUS_CODE_400);
    }
} else {
    $topicName = '';
}
if (! empty($_REQUEST["categId"])) {
    $categId = $_REQUEST["categId"];
    if (empty($categlib->get_category($_REQUEST["categId"]))) {
        Feedback::errorAndDie(tra("Invalid category or it has been deleted."), \Laminas\Http\Response::STATUS_CODE_400);
    }
} else {
    $categId = '';
}
$smarty->assign_by_ref('categId', $categId);
if (! isset($_REQUEST['lang'])) {
    $_REQUEST['lang'] = '';
}
// Get a list of last changes to the Wiki database
$listpages = $artlib->list_articles($offset, $prefs['maxArticles'], $sort_mode, $find, $date_min, $date_max, $user, $type, $topic, 'y', $topicName, $categId, '', '', $_REQUEST['lang'], $min_rating, $max_rating, false, 'y');
if ($prefs['feature_multilingual'] == 'y') {
    $multilinguallib = TikiLib::lib('multilingual');
    $listpages['data'] = $multilinguallib->selectLangList('article', $listpages['data']);
    foreach ($listpages['data'] as &$article) {
        $article['translations'] = $multilinguallib->getTranslations('article', $article['articleId'], $article["title"], $article['lang']);
    }
}
$smarty->assign('useLinktoURL', 'n');
$topics = $artlib->list_topics();
$smarty->assign_by_ref('topics', $topics);
$temp_max = count($listpages["data"]);
for ($i = 0; $i < $temp_max; $i++) {
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
    $comments_prefix_var = 'article:';
    $comments_object_var = $listpages["data"][$i]["articleId"];
    $comments_objectId = $comments_prefix_var . $comments_object_var;
    $listpages["data"][$i]["comments_count"] = TikiLib::lib('comments')->count_comments($comments_objectId);
    if ($prefs['feature_freetags'] == 'y') { // And get the Tags for the posts
        $listpages["data"][$i]["freetags"] = $freetaglib->get_tags_on_object($listpages["data"][$i]["articleId"], "article");
    }
}
if (! empty($topicName) && ! str_contains($topicName, '!') && ! str_contains($topicName, '+')) {
    $smarty->assign_by_ref('topic', $topicName);
} elseif (! empty($topic) && is_numeric($topic)) {
    if (! empty($listpages['data'][0]['topicName'])) {
        $smarty->assign_by_ref('topic', $listpages['data'][0]['topicName']);
    } else {
        $topic_info = $artlib->get_topic($topic);
        if (isset($topic_info['name'])) {
            $smarty->assign_by_ref('topic', $topic_info['name']);
        }
    }
}
if (! empty($type) && ! str_contains($type, '!') && ! str_contains($type, '+')) {
    $smarty->assign_by_ref('type', $type);
}
$smarty->assign('maxArticles', $prefs['maxArticles']);
// If there're more records then assign next_offset
$smarty->assign_by_ref('listpages', $listpages["data"]);
$smarty->assign_by_ref('count', $listpages["count"]);
if ($prefs['feature_user_watches'] == 'y') {
    if ($user && isset($_REQUEST['watch_action'])) {
        $access->checkCsrf();
        if ($_REQUEST['watch_action'] == 'add') {
            $tikilib->add_user_watch($user, 'article_*', '*');
        } else {
            $tikilib->remove_user_watch($user, 'article_*', '*', null);
        }
    }
    $smarty->assign('user_watching_articles', ($user && $tikilib->user_watches($user, 'article_*', '*')) ? 'y' : 'n');
}
$headerLinks = 'y';
$smarty->assign('headerLinks', $headerLinks);
include_once('tiki-section_options.php');
// Display the template
$smarty->assign('mid', 'tiki-view_articles.tpl');
$smarty->display("tiki.tpl");
