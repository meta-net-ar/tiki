<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'faqs';
$inputConfiguration = [
    [
        'staticKeyFilters'         => [
        'faqId'                    => 'int',                //post
        'remove'                   => 'int',                //get
        'save'                     => 'bool',               //post
        'title'                    => 'string',             //post
        'canSuggest'               => 'bool',               //post
        'description'              => 'xss',                //post
        'sort_mode'                => 'word',               //get
        'offset'                   => 'int',                //get
        'find'                     => 'string',             //post
        ],
    ],
];
require_once('tiki-setup.php');
$faqlib = TikiLib::lib('faq');
$auto_query_args = ['offset', 'find', 'sort_mode', 'faqId'];
$access->check_feature('feature_faqs');
$access->check_permission('tiki_p_view_faqs');
//get_strings tra('Admin FAQs')
if (! isset($_REQUEST["faqId"])) {
    $_REQUEST["faqId"] = 0;
}
$smarty->assign('faqId', $_REQUEST["faqId"]);
if ($_REQUEST["faqId"]) {
    $info = $faqlib->get_faq($_REQUEST["faqId"]);
} else {
    $info = [];
    $info["title"] = '';
    $info["description"] = '';
    $info["canSuggest"] = 'n';
}
$smarty->assign('title', $info["title"]);
$smarty->assign('description', $info["description"]);
$smarty->assign('canSuggest', $info["canSuggest"]);
if (isset($_REQUEST["remove"]) && $access->checkCsrf()) {
    if ($tiki_p_admin_faqs != 'y') {
        $smarty->assign('msg', tra("You do not have the permission that is needed to use this feature"));
        $smarty->display("error.tpl");
        die;
    }
    $faqlib->remove_faq($_REQUEST["remove"]);
}
if (isset($_REQUEST["save"])) {
    if (empty($_REQUEST["title"])) {
        $smarty->assign('msg', tra("You can not create a FAQ without a title "));
        $smarty->display("error.tpl");
        die;
    }
    $access->checkCsrf();
    $access->check_permission('tiki_p_admin_faqs');
    if (mb_strlen($_REQUEST["title"]) > 200) {
        $smarty->assign('msg', tra("You have exceeded the number of characters allowed (200 max) for the FAQ title field"));
        $smarty->display("error.tpl");
        die;
    }
    if (isset($_REQUEST["canSuggest"]) && $_REQUEST["canSuggest"] == 'on') {
        $canSuggest = 'y';
    } else {
        $canSuggest = 'n';
    }
    $fid = $faqlib->replace_faq($_REQUEST["faqId"], $_REQUEST["title"], $_REQUEST["description"], $canSuggest);
    $cat_type = 'faq';
    $cat_objid = $fid;
    $cat_desc = substr($_REQUEST["description"], 0, 200);
    $cat_name = $_REQUEST["title"];
    $cat_href = "tiki-view_faq.php?faqId=" . $cat_objid;
    include_once("categorize.php");
    $smarty->assign('faqId', 0);
    $smarty->assign('title', '');
    $smarty->assign('description', '');
    $smarty->assign('canSuggest', '');
}
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'title_asc';
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
$channels = $faqlib->list_faqs($offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('channels', $channels["data"]);
$smarty->assign_by_ref('cant', $channels["cant"]);
$cat_type = 'faq';
$cat_objid = $_REQUEST["faqId"];
include_once("categorize_list.php");
include_once('tiki-section_options.php');
// Display the template
$smarty->assign('mid', 'tiki-list_faqs.tpl');
$smarty->display("tiki.tpl");
