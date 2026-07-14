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
        'clear'                    => 'bool',              //post
        'sort_mode'                => 'alnumdash',         //get
        'offset'                   => 'digits',            //post
        'find'                     => 'string',            //post
        ],
    ],
];

require_once('tiki-setup.php');
include_once('lib/refererstats/refererlib.php');
use Tiki\Sections;
$section = Sections::SECTION_ADMIN_LAYOUT;
Sections::setCurrentSection($section);

$access->check_feature('feature_referer_stats');
$access->check_permission('tiki_p_view_referer_stats');

if (isset($_REQUEST["clear"]) && $access->checkCsrf()) {
    $refererlib->clear_referer_stats();
}
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'hits_desc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$offset = $_REQUEST["offset"] ?? 0;
$smarty->assign_by_ref('offset', $offset);
$find = $_REQUEST["find"] ?? '';
$smarty->assign('find', $find);
$smarty->assign_by_ref('sort_mode', $sort_mode);
$channels = $refererlib->list_referer_stats($offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('pages_count', $channels["count"]);
$smarty->assign_by_ref('channels', $channels["data"]);
// Display the template
$smarty->assign('mid', 'tiki-referer_stats.tpl');
$smarty->display("tiki.tpl");
