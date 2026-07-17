<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'         => [
        'receivedArticleId'        => 'int',               //get
        'view'                     => 'word',              //get
        'accept'                   => 'bool',              //post
        'Time_Meridian'            => 'digits',            //post
        'Time_Hour'                => 'digits',            //post
        'Time_Minute'              => 'digits',            //post
        'Date_Month'               => 'digits',            //post
        'Date_Day'                 => 'digits',            //post
        'Date_Year'                => 'digits',            //post
        'title'                    => 'word',              //post
        'authorName'               => 'word',              //post
        'useImage'                 => 'bool',              //post
        'heading'                  => 'xss',               //post
        'image_y'                  => 'digits',            //post
        'image_x'                  => 'digits',            //post
        'body'                     => 'xss',               //post
        'type'                     => 'word',              //post
        'rating'                   => 'digits',            //post
        'topic'                    => 'word',              //post
        'preview'                  => 'bool',              //post
        'image_name'               => 'alpha',             //post
        'image_size'               => 'digits',            //post
        'created'                  => 'bool',              //post
        'remove'                   => 'int',               //get
        'save'                     => 'bool',              //post
        'sort_mode'                => 'word',              //get
        'offset'                   => 'digits',            //get
        'find'                     => 'word',              //post
        ],
    ],
];
require_once('tiki-setup.php');
include_once('lib/commcenter/commlib.php');
$access->check_feature('feature_comm');
$access->check_permission('tiki_p_admin_received_articles');
//Use 12- or 24-hour clock for $publishDate time selector based on admin and user preferences
$artlib = TikiLib::lib('art');
$userprefslib = TikiLib::lib('userprefs');
$smarty->assign('use_24hr_clock', $userprefslib->get_user_clock_pref($user));

if (! isset($_REQUEST["receivedArticleId"])) {
    $_REQUEST["receivedArticleId"] = 0;
}
$smarty->assign('receivedArticleId', $_REQUEST["receivedArticleId"]);
if ($_REQUEST["receivedArticleId"]) {
    $info = $commlib->get_received_article($_REQUEST["receivedArticleId"]);
    $info["topic"] = 1;
} else {
    $info = [];
    $info["title"] = '';
    $info["authorName"] = '';
    $info["size"] = 0;
    $info["useImage"] = 'n';
    $info["image_name"] = '';
    $info["image_type"] = '';
    $info["image_size"] = 0;
    $info["image_x"] = 0;
    $info["image_y"] = 0;
    $info["image_data"] = '';
    $now = $tikilib->now;
    $info["publishDate"] = $now;
    $info["created"] = $now;
    $info["heading"] = '';
    $info["body"] = '';
    $info["hash"] = '';
    $info["author"] = '';
    $info["topic"] = 1;
    $info["type"] = 'Article';
    $info["rating"] = 5;
}
$smarty->assign('view', 'n');
if (isset($_REQUEST["view"])) {
    $info = $tikilib->get_received_article($_REQUEST["view"]);
    $smarty->assign('view', 'y');
    $info["topic"] = 1;
}
if (isset($_REQUEST["accept"])) {
    $access->checkCsrf();
    // CODE TO ACCEPT A PAGE HERE
    //Convert 12-hour clock hours to 24-hour scale to compute time
    if (! empty($_REQUEST['Time_Meridian'])) {
        $_REQUEST['Time_Hour'] = date('H', strtotime($_REQUEST['Time_Hour'] . ':00 ' . $_REQUEST['Time_Meridian']));
    }
    $publishDate = $tikilib->make_time($_REQUEST["Time_Hour"], $_REQUEST["Time_Minute"], 0, $_REQUEST["Date_Month"], $_REQUEST["Date_Day"], $_REQUEST["Date_Year"]);
    $commlib->update_received_article($_REQUEST["receivedArticleId"], $_REQUEST["title"], $_REQUEST["authorName"], $_REQUEST["useImage"], $_REQUEST["image_x"], $_REQUEST["image_y"], $publishDate, $_REQUEST["heading"], $_REQUEST["body"], $_REQUEST["type"], $_REQUEST["rating"]);
    $commlib->accept_article($_REQUEST["receivedArticleId"], $_REQUEST["topic"]);
    $smarty->assign('preview', 'n');
    $smarty->assign('receivedArticleId', 0);
}
$smarty->assign('preview', 'n');
$smarty->assign('topic', $info["topic"]);
if (isset($_REQUEST["preview"])) {
    $smarty->assign('preview', 'y');
    if (! empty($_REQUEST['Time_Meridian'])) {
        $_REQUEST['Time_Hour'] = date('H', strtotime($_REQUEST['Time_Hour'] . ':00 ' . $_REQUEST['Time_Meridian']));
    }
    $info["publishDate"] = $tikilib->make_time($_REQUEST["Time_Hour"], $_REQUEST["Time_Minute"], 0, $_REQUEST["Date_Month"], $_REQUEST["Date_Day"], $_REQUEST["Date_Year"]);
    $info["title"] = $_REQUEST["title"];
    $info["authorName"] = $_REQUEST["authorName"];
    $info["receivedArticleId"] = $_REQUEST["receivedArticleId"];
    $info["useImage"] = $_REQUEST["useImage"];
    $info["image_name"] = $_REQUEST["image_name"];
    $info["image_size"] = $_REQUEST["image_size"];
    $info["image_x"] = $_REQUEST["image_x"];
    $info["image_y"] = $_REQUEST["image_y"];
    $info["created"] = $_REQUEST["created"];
    $info["heading"] = $_REQUEST["heading"];
    $info["body"] = $_REQUEST["body"];
    $info["topic"] = $_REQUEST["topic"];
    $info["type"] = $_REQUEST["type"];
    $info["rating"] = $_REQUEST["rating"];
}
$smarty->assign('topic', $info["topic"]);
$smarty->assign('title', $info["title"]);
$smarty->assign('authorName', $info["authorName"]);
$smarty->assign('useImage', $info["useImage"]);
$smarty->assign('image_name', $info["image_name"]);
$smarty->assign('image_size', $info["image_size"]);
$smarty->assign('image_x', $info["image_x"]);
$smarty->assign('image_y', $info["image_y"]);
$smarty->assign('publishDate', $info["publishDate"]);
$smarty->assign('created', $info["created"]);
$smarty->assign('heading', $info["heading"]);
$smarty->assign('body', $info["body"]);
$smarty->assign('type', $info["type"]);
$smarty->assign('rating', $info["rating"]);
// Assign parsed
$smarty->assign(
    'parsed_heading',
    TikiLib::lib('parser')->parse_data(
        $info["heading"],
        [
            'min_one_paragraph' => true,
            'is_html' => $artlib->is_html($info, true),
        ]
    )
);
$smarty->assign('parsed_body', TikiLib::lib('parser')->parse_data($info["body"], ['is_html' => $artlib->is_html($info)]));
if (isset($_REQUEST["remove"]) && $access->checkCsrf()) {
    $commlib->remove_received_article($_REQUEST["remove"]);
}
if (isset($_REQUEST["save"])) {
    $access->checkCsrf();
    //Convert 12-hour clock hours to 24-hour scale to compute time
    if (! empty($_REQUEST['Time_Meridian'])) {
        $_REQUEST['Time_Hour'] = date('H', strtotime($_REQUEST['Time_Hour'] . ':00 ' . $_REQUEST['Time_Meridian']));
    }
    $publishDate = $tikilib->make_time($_REQUEST["Time_Hour"], $_REQUEST["Time_Minute"], 0, $_REQUEST["Date_Month"], $_REQUEST["Date_Day"], $_REQUEST["Date_Year"]);
    $commlib->update_received_article($_REQUEST["receivedArticleId"], $_REQUEST["title"], $_REQUEST["authorName"], $_REQUEST["useImage"], $_REQUEST["image_x"], $_REQUEST["image_y"], $publishDate, $_REQUEST["heading"], $_REQUEST["body"]);
    $smarty->assign('receivedArticleId', $_REQUEST["receivedArticleId"]);
    $smarty->assign('title', $_REQUEST["title"]);
    $smarty->assign('authorName', $_REQUEST["authorName"]);
    $smarty->assign('size', strlen($_REQUEST["body"]));
    $smarty->assign('useImage', $_REQUEST["useImage"]);
    $smarty->assign('image_x', $_REQUEST["image_x"]);
    $smarty->assign('image_y', $_REQUEST["image_y"]);
    $smarty->assign('publishDate', $publishDate);
    $smarty->assign('heading', $_REQUEST["heading"]);
    $smarty->assign('body', $_REQUEST["body"]);
}
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'receivedDate_desc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
if (! isset($_REQUEST["offset"])) {
    $offset = 0;
} else {
    $offset = $_REQUEST["offset"];
}
$smarty->assign_by_ref('offset', $offset);
if (isset($_REQUEST["find"])) {
    $find = $_REQUEST["find"];
} else {
    $find = '';
}
$smarty->assign('find', $find);
$smarty->assign_by_ref('sort_mode', $sort_mode);
$channels = $commlib->list_received_articles($offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('channels', $channels["data"]);
$smarty->assign_by_ref('cant', $channels["cant"]);
$topics = $artlib->list_topics();
$smarty->assign_by_ref('topics', $topics);
$types = $artlib->list_types();
$smarty->assign_by_ref('types', $types);
// Display the template
$smarty->assign('mid', 'tiki-received_articles.tpl');
$smarty->display("tiki.tpl");
