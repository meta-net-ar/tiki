<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    ['staticKeyFilters'        => [
        'faqId'                => 'int',         //post
        'sort_mode'            => 'text',        //get
        'find'                 => 'text',        //get
        'questionId'           => 'int',         //post
        'remove'               => 'int',         //get
        'filter'               => 'text',        //post
        'useq'                 => 'word',        //post
        'usequestionId'        => 'int',         //post
        'offset'               => 'int',         //get
        'remove_suggested'     => 'int',         //get
        'approve_suggested'    => 'int',         //get
        'save'                 => 'bool',        //post
        'question'             => 'text',        //post
        'answer'               => 'purifier'     //post
        ]
    ],
    [ 'catchAllUnset' => null ],
];

$section = 'faqs';
require_once('tiki-setup.php');

$faqlib = TikiLib::lib('faq');

$access->check_feature('feature_faqs');
$access->check_permission('tiki_p_admin_faqs');

if (! isset($_REQUEST["faqId"])) {
    Feedback::errorAndDie(tra("No questions group indicated"), \Laminas\Http\Response::STATUS_CODE_400);
}

$smarty->assign('faqId', $_REQUEST["faqId"]);
$faq_info = $faqlib->get_faq($_REQUEST["faqId"]);
$smarty->assign('faq_info', $faq_info);

if (! isset($_REQUEST["questionId"])) {
    $_REQUEST["questionId"] = 0;
}

$smarty->assign('questionId', $_REQUEST["questionId"]);

if ($_REQUEST["questionId"]) {
    $info = $faqlib->get_faq_question($_REQUEST["questionId"]);
} else {
    $info = [];

    $info["question"] = '';
    $info["answer"] = '';
}
// $smarty->assign('question',$info["question"]);  AWC moved this
// $smarty->assign('answer',$info["answer"]);      AWC moved this
if (isset($_REQUEST["remove"]) && $access->checkCsrf()) {
    $faqlib->remove_faq_question($_REQUEST["remove"]);
}

if (! isset($_REQUEST["filter"])) {
    $_REQUEST["filter"] = '';
}

$smarty->assign('filter', $_REQUEST["filter"]);

if (isset($_REQUEST["useq"])) {
    $access->checkCsrf();
    $quse = $faqlib->get_faq_question($_REQUEST["usequestionId"]);
    $faqlib->replace_faq_question($_REQUEST["faqId"], 0, $quse["question"], $quse["answer"]);
    $info = $faqlib->get_faq_question($_REQUEST["usequestionId"]); // AWC added
}

if (isset($_REQUEST["save"])) {
    if (! $_REQUEST['question']) {
        Feedback::errorAndDie(tra("You can not create a FAQ question without a question "), \Laminas\Http\Response::STATUS_CODE_409);
    }

    $access->checkCsrf();
    $faqlib->replace_faq_question($_REQUEST["faqId"], $_REQUEST["questionId"], $_REQUEST["question"], $_REQUEST["answer"]);

    $info["question"] = '';
    $info["answer"] = '';
    //$smarty->assign('question',$info["question"]);  AWC moved this
    //$smarty->assign('answer',$info["answer"]);      AWC moved this
    $smarty->assign('questionId', 0);
}

$smarty->assign('question', $info !== false ? $info["question"] : null);
$smarty->assign('answer', $info !== false ? $info["answer"] : null);    // moved from above

if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'position_asc,questionId_asc';
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

if (isset($_REQUEST["remove_suggested"])) {
    $faqlib->remove_suggested_question($_REQUEST["remove_suggested"]);
}

if (isset($_REQUEST["approve_suggested"])) {
    $faqlib->approve_suggested_question($_REQUEST["approve_suggested"]);
}

$smarty->assign_by_ref('sort_mode', $sort_mode);
$channels = $faqlib->list_faq_questions($_REQUEST["faqId"], 0, -1, $sort_mode, $find);
$allq = $faqlib->list_all_faq_questions(0, -1, 'position_asc,questionId_asc', $_REQUEST["filter"]);
$smarty->assign_by_ref('allq', $allq["data"]);

$smarty->assign_by_ref('pages_count', $channels["count"]);

$smarty->assign_by_ref('channels', $channels["data"]);

$suggested = $faqlib->list_suggested_questions(0, -1, 'created_desc', '', $_REQUEST["faqId"]);
$smarty->assign_by_ref('suggested', $suggested["data"]);

include_once('tiki-section_options.php');


$wikilib = TikiLib::lib('wiki');
$plugins = $wikilib->list_plugins(true, 'faqans');
$smarty->assign_by_ref('plugins', $plugins);

// Display the template
$smarty->assign('mid', 'tiki-faq_questions.tpl');
$smarty->display("tiki.tpl");
