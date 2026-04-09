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
        ],
    ],
];
require_once('tiki-setup.php');
$performanceLib = TikiLib::lib('performancestats');

$access->check_feature('tiki_monitor_performance');
$access->check_permission('tiki_p_admin');

if (! empty($_REQUEST['clear']) && $access->checkCsrf()) {
    $performanceLib->clearPerformanceRecords();
}

$find = $_REQUEST['find'] ?? '';
$averageStatOffset = $_REQUEST['average_stat_offset'] ?? 0;
$maximumStatOffset = $_REQUEST['maximum_stat_offset'] ?? 0;

/**
 * Validates a sort direction ('ASC' or 'DESC').
 * Falls back to a default if invalid, and provides feedback on invalid input.
 */
function validateDirection(?string $direction, string $default = 'DESC'): string
{
    $allowedDirections = ['ASC', 'DESC'];

    if (! is_string($direction)) {
        return $default;
    }

    $normalized = strtoupper($direction);

    if (in_array($normalized, $allowedDirections, true)) {
        return $normalized;
    }

    // Detect and report invalid input that is not null, not a string, or not an allowed value.
    Feedback::warning(tra(
        'Invalid sort direction provided. ' . $direction . ' only ASC or DESC is allowed'
    ));

    return $default;
}

// Determine the order type and the requested direction.
if (! empty($_REQUEST['no_of_requests'])) {
    $orderType = 'no_of_requests';
    $requestedAverageOrder = $_REQUEST['no_of_requests'];
} else {
    $orderType = 'average_stat_order';
    $requestedAverageOrder = $_REQUEST['average_stat_order'] ?? null;
}

// Validate directions
$averageStatOrder = validateDirection($requestedAverageOrder);
$maximumStatOrder = validateDirection($_REQUEST['maximum_stat_order'] ?? null);

$smarty->assign('performance_stats_lib', $performanceLib);
$smarty->assign('find', $find);
$smarty->assign('pages_count', $performanceLib->getRequestsGroupedByAmount());
$smarty->assign_by_ref('average_stat_offset', $averageStatOffset);
$smarty->assign_by_ref('average_stat_order', $averageStatOrder);
$smarty->assign_by_ref('maximum_stat_offset', $maximumStatOffset);
$smarty->assign_by_ref('maximum_stat_order', $maximumStatOrder);
$smarty->assign_by_ref('average_load_time_stats', $performanceLib->getRequestsBasedOnAverageRequestTime(25, $averageStatOffset, $find, $averageStatOrder, $orderType)->result);
$smarty->assign_by_ref('maximum_load_time_stats', $performanceLib->getRequestsBasedOnMaximumProcessingTime(25, $maximumStatOffset, $find, $maximumStatOrder)->result);
$smarty->assign('mid', 'tiki-performance_stats.tpl');
$smarty->display("tiki.tpl");
