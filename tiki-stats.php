<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// TIKI_PRINTING_PDF is true only for PDF exports (?display=pdf).
define('TIKI_PRINTING_PDF', isset($_REQUEST['display']) && $_REQUEST['display'] === 'pdf');

$inputConfiguration = [
    [
        'staticKeyFilters'     => [
        'days'                 => 'digits',       //get
        'pv_chart'             => 'striptags',    //get
        'chart'                => 'striptags',    //get
        'startDate_Year'       => 'digits',       //get
        'startDate_Month'      => 'digits',       //get
        'startDate_Day'        => 'digits',       //get
        'endDate_Year'         => 'digits',       //get
        'endDate_Month'        => 'digits',       //get
        'endDate_Day'          => 'digits',       //get
        'display'              => 'striptags'
        ],
        'catchAllUnset' => null
    ],
];

require_once('tiki-setup.php');
$statslib = TikiLib::lib('stats');

$access->check_feature('feature_stats');
$access->check_permission('tiki_p_view_stats');

if (! isset($_REQUEST["days"])) {
    $_REQUEST["days"] = 7;
}
$smarty->assign('pv_chart', 'n');
if (isset($_REQUEST["pv_chart"])) {
    $smarty->assign('pv_chart', 'y');
}
$smarty->assign('days', $_REQUEST["days"]);
$smarty->assign('usage_chart', 'n');
if (isset($_REQUEST["chart"])) {
    $smarty->assign($_REQUEST["chart"] . "_chart", 'y');
}
if ($prefs['feature_wiki'] == "y") {
    $wiki_stats = $statslib->wiki_stats();
} else {
    $wiki_stats = false;
}
$smarty->assign_by_ref('wiki_stats', $wiki_stats);
if ($prefs['feature_file_galleries'] == 'y') {
    $fgal_stats = $statslib->file_gal_stats();
} else {
    $fgal_stats = false;
}
$smarty->assign_by_ref('fgal_stats', $fgal_stats);
if ($prefs['feature_articles'] == 'y') {
    $cms_stats = $statslib->cms_stats();
} else {
    $cms_stats = false;
}
$smarty->assign_by_ref('cms_stats', $cms_stats);
if ($prefs['feature_forums'] == 'y') {
    $forum_stats = $statslib->forum_stats();
} else {
    $forum_stats = false;
}
$smarty->assign_by_ref('forum_stats', $forum_stats);
if ($prefs['feature_blogs'] == 'y') {
    $blog_stats = $statslib->blog_stats();
} else {
    $blog_stats = false;
}
$smarty->assign_by_ref('blog_stats', $blog_stats);
if ($prefs['feature_polls'] == 'y') {
    $poll_stats = $statslib->poll_stats();
} else {
    $poll_stats = false;
}
$smarty->assign_by_ref('poll_stats', $poll_stats);
if ($prefs['feature_faqs'] == 'y') {
    $faq_stats = $statslib->faq_stats();
} else {
    $faq_stats = false;
}
$smarty->assign_by_ref('faq_stats', $faq_stats);
if ($prefs['feature_quizzes'] == 'y') {
    $quiz_stats = $statslib->quiz_stats();
} else {
    $quiz_stats = false;
}
$smarty->assign_by_ref('quiz_stats', $quiz_stats);
if ($prefs['feature_trackers'] == 'y') {
    // Retrieve trackers statistics and prepare extracted data for display.
    $tracker_stats = $statslib->trackerStats();
} else {
    $tracker_stats = false;
}
$smarty->assign_by_ref('tracker_stats', $tracker_stats);
$user_stats = $statslib->user_stats();
$smarty->assign_by_ref('user_stats', $user_stats);
$site_stats = $statslib->site_stats();
$smarty->assign_by_ref('site_stats', $site_stats);
if (isset($_REQUEST['startDate_Year']) || isset($_REQUEST['endDate_Year'])) {
    $start_date = $tikilib->make_time(23, 59, 59, $_REQUEST['startDate_Month'], $_REQUEST['startDate_Day'], $_REQUEST['startDate_Year']);
    $end_date = $tikilib->make_time(23, 59, 59, $_REQUEST['endDate_Month'], $_REQUEST['endDate_Day'], $_REQUEST['endDate_Year']);
    $smarty->assign('startDate', $start_date);
    $smarty->assign('endDate', $end_date);
} else {
    $start_date = (int)$site_stats['started'];
    $end_date = $tikilib->make_time(23, 59, 59, date("m"), date("d"), date("Y"));
    $smarty->assign('startDate', $start_date);
}
$smarty->assign('start_year', date('Y', $start_date));
$smarty->assign('end_year', date('Y', $tikilib->now));
$best_objects_stats_lastweek = $statslib->best_overall_object_stats(20, 7);
$smarty->assign_by_ref('best_objects_stats_lastweek', $best_objects_stats_lastweek);
$best_objects_stats_between = $statslib->best_overall_object_stats(20, 0, $start_date, $end_date);
$smarty->assign_by_ref('best_objects_stats_between', $best_objects_stats_between);
$smarty->assign('mid', 'tiki-stats.tpl');
if (TIKI_PRINTING_PDF) {
    $smarty->assign('display', $_REQUEST['display']);
    $smarty->assign('print_page', 'y');
    $pdata = $smarty->fetch("tiki.tpl");
    $generator = new PdfGenerator();
    if (! empty($generator->error)) {
        Feedback::error($generator->error);
        $access->redirect($_SERVER['HTTP_REFERER']);
    } else {
        $pdf = $generator->getPdf('tiki-stats.php', ['display' => 'print'], $pdata);
        header('Cache-Control: private, must-revalidate');
        header('Pragma: private');
        header("Content-Description: File Transfer");
        header('Content-disposition: attachment; filename="stats.pdf"');
        header("Content-Type: application/pdf");
        header("Content-Transfer-Encoding: binary");
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
    }
} else {
    $smarty->assign('display', '');
    $smarty->assign('pdf_export', ($prefs['print_pdf_from_url'] != 'none') ? 'y' : 'n');
    $smarty->display("tiki.tpl");
}
