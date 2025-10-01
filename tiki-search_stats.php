<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'         => [
        'clear'                    => 'bool',              //post
        'sort_mode'                => 'word',             //post
        'offset'                   => 'digits',            //post
        'find'                     => 'string',            //post
        ],
    ],
];
require_once('tiki-setup.php');
$searchstatslib = TikiLib::lib('searchstats');
//get_strings tra('Search Stats')

$access->check_feature('feature_search_stats');
$access->check_permission('tiki_p_admin');

if (isset($_REQUEST["clear"])) {
    $access->checkCsrf();
    $searchstatslib->clear_search_stats();
}
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'hits_desc';
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
$channels = $searchstatslib->list_search_stats($offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('pages_count', $channels["count"]);
$smarty->assign_by_ref('channels', $channels["data"]);
// Display the template
$smarty->assign('mid', 'tiki-search_stats.tpl');
$smarty->display("tiki.tpl");
