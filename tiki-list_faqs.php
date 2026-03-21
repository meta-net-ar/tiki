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
use Tiki\Sections;
$section = Sections::SECTION_FAQS;
Sections::setCurrentSection($section);
$faqlib = TikiLib::lib('faq');
$auto_query_args = ['offset', 'find', 'sort_mode', 'faqId'];
$access->check_feature('feature_faqs');
$access->check_permission('tiki_p_view_faqs');
//get_strings tra('Admin FAQs')
$maxFaqTitleLength = $faqlib->getFaqTitleMaxLength();
$smarty->assign('MAX_FAQ_TITLE_LENGTH', $maxFaqTitleLength);

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
        Feedback::errorAndDie(tra("You do not have the permission that is needed to use this feature"), \Laminas\Http\Response::STATUS_CODE_401);
    }
    try {
        $faqToRemove = $faqlib->get_faq($_REQUEST["remove"]);
        if ($faqToRemove) {
            $faqTitle = htmlspecialchars($faqToRemove['title'] ?? tra('Untitled'), ENT_QUOTES, 'UTF-8');
            $faqlib->remove_faq($_REQUEST["remove"]);
            Feedback::success(tr("FAQ '%0' has been successfully deleted.", $faqTitle));
        } else {
            Feedback::error(tra("The FAQ you are trying to delete was not found."));
        }
    } catch (Exception $e) {
        Feedback::error(tr("An error occurred while deleting the FAQ: %0", $e->getMessage()));
    }
}
if (isset($_REQUEST["save"])) {
    $access->checkCsrf();
    $access->check_permission('tiki_p_admin_faqs');

    $title = trim($_REQUEST["title"] ?? '');
    $description = trim($_REQUEST["description"] ?? '');
    $canSuggest = (isset($_REQUEST["canSuggest"]) && $_REQUEST["canSuggest"] === 'on') ? 'y' : 'n';
    $submittedFaqId = (int) ($_REQUEST["faqId"] ?? 0);

    // Preserve submitted values when validation fails.
    $smarty->assign('faqId', $submittedFaqId);
    $smarty->assign('title', $title);
    $smarty->assign('description', $description);
    $smarty->assign('canSuggest', $canSuggest);

    if ($title === '') {
        Feedback::error(tra("You cannot create a FAQ without a title."));
    } elseif (! Feedback::validateFieldLength("Title", $title, $maxFaqTitleLength)) {
    } else {
        $isEdit = $submittedFaqId > 0;
        $fid = $faqlib->replace_faq($submittedFaqId, $title, $description, $canSuggest);
        $escapedTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $successMessage = $isEdit ?
            tr("FAQ '%0' has been successfully updated.", $escapedTitle) :
            tr("FAQ '%0' has been successfully created.", $escapedTitle);
        Feedback::success($successMessage);
        // Categorize
        $cat_type = 'faq';
        $cat_objid = $fid;
        $cat_desc = substr($description, 0, 200);
        $cat_name = $title;
        $cat_href = "tiki-view_faq.php?faqId=" . $cat_objid;
        include_once("categorize.php");

        // Clear the form
        $smarty->assign('faqId', 0);
        $smarty->assign('title', '');
        $smarty->assign('description', '');
        $smarty->assign('canSuggest', '');
    }
}
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'title_asc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$offset = $_REQUEST["offset"] ?? 0;
$smarty->assign_by_ref('offset', $offset);
$find = $_REQUEST["find"] ?? '';
$smarty->assign('find', $find);
$smarty->assign_by_ref('sort_mode', $sort_mode);
$channels = $faqlib->list_faqs($offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('channels', $channels["data"]);
$smarty->assign_by_ref('count', $channels["count"]);
$cat_type = 'faq';
$cat_objid = $_REQUEST["faqId"];
include_once("categorize_list.php");
include_once('tiki-section_options.php');
// Display the template
$smarty->assign('mid', 'tiki-list_faqs.tpl');
$smarty->display("tiki.tpl");
