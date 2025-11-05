<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'cms';
$inputConfiguration = [
    [
        'staticKeyFilters'         => [
        'remove'                   => 'int',                //post
        'approve'                  => 'int',                 //post
        'submit_mult'              => 'string',              //post
        'maxRecords'               => 'int',                 //post
        'sort_mode'                => 'alnumdash',           //get
        'offset'                   => 'int',                 //get
        'find'                     => 'string',              //post
        'topic'                    => 'word',                //post
        'lang'                     => 'lang',                //post
        'type'                     => 'word',                //post
        ],
        'staticKeyFiltersForArrays' => [
            'checked'               => 'int',              //post
        ],
    ],
];
require_once('tiki-setup.php');
$artlib = TikiLib::lib('art');
$access->check_feature('feature_submissions');
$access->check_permission('tiki_p_submit_article');
//get_strings tra('View submissions')

$auto_query_args = [
    'subId',
    'offset',
    'maxRecords',
    'sort_mode',
    'find',
    'type',
    'topic',
    'lang',
];
if (isset($_REQUEST["remove"]) && $access->checkCsrf()) {
    $access->check_permission('tiki_p_remove_submission');
    $artlib->remove_submission($_REQUEST["remove"]);
}
if (isset($_REQUEST["approve"]) && $access->checkCsrf()) {
    $access->check_permission('tiki_p_approve_submission');
    $artlib->approve_submission($_REQUEST["approve"]);
}
if (isset($_REQUEST['submit_mult']) && count($_REQUEST["checked"]) > 0 &&  $access->checkCsrf()) {
    if ($_REQUEST['submit_mult'] === 'remove_subs') {
        $access->check_permission('tiki_p_remove_submission');

        foreach ($_REQUEST["checked"] as $sId) {
            $artlib->remove_submission($sId);
        }
    } elseif ($_REQUEST['submit_mult'] === 'approve_subs') {
        $access->check_permission('tiki_p_approve_submission');

        foreach ($_REQUEST["checked"] as $sId) {
            $artlib->approve_submission($sId);
        }
    }
}

// This script can receive the threshold
// for the information as the number of
// days to get in the log 1,3,4,etc
// it will default to 1 recovering information for today
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'publishDate_desc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$smarty->assign_by_ref('sort_mode', $sort_mode);
// If offset is set use it if not then use offset =0
// use the maxRecords php variable to set the limit
// if sortMode is not set then use lastModif_desc
if (! empty($_REQUEST['maxRecords'])) {
    $maxRecords = $_REQUEST['maxRecords'];
} else {
    $maxRecords = $prefs['maxRecords'];
}
if (! isset($_REQUEST["offset"])) {
    $offset = 0;
} else {
    $offset = $_REQUEST["offset"];
}
$smarty->assign_by_ref('offset', $offset);
if (($tiki_p_admin == 'y') || ($tiki_p_admin_cms == 'y')) {
    $pdate = '';
} elseif (isset($_SESSION["thedate"])) {
    if ($_SESSION["thedate"] < $tikilib->now) {
        $pdate = $_SESSION["thedate"];
    } else {
        $pdate = $tikilib->now;
    }
} else {
    $pdate = $tikilib->now;
}
if (isset($_REQUEST["find"])) {
    $find = $_REQUEST["find"];
} else {
    $find = '';
}
$smarty->assign('find', $find);
if (! isset($_REQUEST['topic'])) {
    $_REQUEST['topic'] = '';
}
if (! isset($_REQUEST['type'])) {
    $_REQUEST['type'] = '';
}
if (! isset($_REQUEST['lang'])) {
    $_REQUEST['lang'] = '';
}
$smarty->assign('find_topic', $_REQUEST['topic']);
$smarty->assign('find_type', $_REQUEST['type']);
$smarty->assign('find_lang', $_REQUEST['lang']);

$smarty->assign('topics', $artlib->list_topics());
$smarty->assign('types', $artlib->list_types());
if ($prefs['feature_multilingual'] == 'y') {
    $langLib = TikiLib::lib('language');
    $languages = $langLib->list_languages(false, 'y');
    $smarty->assign('languages', $languages);
}

$listpages = $artlib->list_submissions($offset, $maxRecords, $sort_mode, $find, $pdate, $_REQUEST['type'], $_REQUEST['topic'], $_REQUEST['lang']);
$smarty->assign_by_ref('pages_count', $listpages["count"]);
include_once('tiki-section_options.php');
$smarty->assign_by_ref('listpages', $listpages["data"]);

// Display the template
$smarty->assign('mid', 'tiki-list_submissions.tpl');
$smarty->display("tiki.tpl");
