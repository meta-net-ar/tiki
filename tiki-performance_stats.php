<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'         => [
        'clear'                    => 'digits',            //get
        'find'                     => 'text',              //post
        'average_stat_offset'      => 'digits',            //get
        'average_stat_order'       => 'text',              //get
        'maximum_stat_offset'      => 'digits',            //get
        'maximum_stat_order'       => 'text',              //get
        'no_of_requests'           => 'alpha',             //get
        'details_url'              => 'text',              //get
        ],
    ],
];
require_once('tiki-setup.php');
$performanceLib = TikiLib::lib('performancestats');

use Tiki\Sections;
$section = Sections::SECTION_ADMIN_LAYOUT;
Sections::setCurrentSection($section);

$access->check_feature('tiki_monitor_performance');
$access->check_permission('tiki_p_admin');

if (! empty($_REQUEST['clear']) && $access->checkCsrf()) {
    $performanceLib->clearPerformanceRecords();
}

$find = $_REQUEST['find'] ?? '';
$averageStatOffset = $_REQUEST['average_stat_offset'] ?? 0;
$maximumStatOffset = $_REQUEST['maximum_stat_offset'] ?? 0;
$detailsUrl = $_REQUEST['details_url'] ?? '';
$maximumStatOrder = $_REQUEST['maximum_stat_order'] ?? 'DESC';
$maximumStatOrder = strtoupper($maximumStatOrder);
if (! in_array($maximumStatOrder, ['ASC', 'DESC'])) {
    $maximumStatOrder = 'DESC';
}
$averageStatOrder = $_REQUEST['average_stat_order'] ?? 'DESC';
$averageStatOrder = strtoupper($averageStatOrder);
if (! in_array($averageStatOrder, ['ASC', 'DESC'])) {
    $averageStatOrder = 'DESC';
}
$orderType = 'average_stat_order';
$noOfRequests = $_REQUEST['no_of_requests'] ?? '';
if (! empty($noOfRequests)) {
    $noOfRequests = strtoupper($noOfRequests);
    $averageStatOrder = in_array($noOfRequests, ['ASC', 'DESC']) ? $noOfRequests : 'DESC';
    $orderType = 'no_of_requests';
} else {
    $orderType = 'average_stat_order';
}

$smarty->assign('performance_stats_lib', $performanceLib);
$smarty->assign('find', $find);
$smarty->assign('pages_count', $performanceLib->getRequestsGroupedByAmount($find));
$smarty->assign_by_ref('average_stat_offset', $averageStatOffset);
$smarty->assign_by_ref('average_stat_order', $averageStatOrder);
$smarty->assign_by_ref('maximum_stat_offset', $maximumStatOffset);
$smarty->assign_by_ref('maximum_stat_order', $maximumStatOrder);
$smarty->assign_by_ref('average_load_time_stats', $performanceLib->getRequestsBasedOnAverageRequestTime(25, $averageStatOffset, $find, $averageStatOrder, $orderType)->result);
$smarty->assign_by_ref('maximum_load_time_stats', $performanceLib->getRequestsBasedOnMaximumProcessingTime(25, $maximumStatOffset, $find, $maximumStatOrder)->result);
$smarty->assign('details_url', $detailsUrl);
$smarty->assign('request_detail_summary', $detailsUrl ? $performanceLib->getRequestDetailsByUrl($detailsUrl) : false);
$smarty->assign('request_detail_samples', $detailsUrl ? $performanceLib->getSlowestSamplesByUrl($detailsUrl, 25) : []);
$smarty->assign('mid', 'tiki-performance_stats.tpl');
$smarty->display("tiki.tpl");
