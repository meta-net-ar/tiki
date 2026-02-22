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
        'staticKeyFilters'                => [
            'maxRecords'                  => 'int',           //get
            'sort_mode'                   => 'string',        //get
            'offset'                      => 'int',           //get
            'find'                        => 'string',        //get
        ],
    ],
];
require_once('tiki-setup.php');
include_once('lib/directory/dirlib.php');
use Tiki\Sections;
$section = Sections::SECTION_DIRECTORY;
Sections::setCurrentSection($section);

$access->check_feature('feature_directory');
$access->check_permission('tiki_p_view_directory');
if (isset($_REQUEST['maxRecords'])) {
    $maxRecords = $_REQUEST['maxRecords'];
}
// Listing: sites
// Pagination resolution
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'created_desc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$offset = $_REQUEST["offset"] ?? 0;
$find = $_REQUEST["find"] ?? '';

$smarty->assign_by_ref('offset', $offset);
$smarty->assign_by_ref('sort_mode', $sort_mode);
$smarty->assign('find', $find);
$items = $dirlib->dir_list_all_valid_sites($offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('items', $items["data"]);
$smarty->assign_by_ref('count', $items["count"]);
include_once('tiki-section_options.php');
$smarty->assign('mid', 'tiki-directory_ranking.tpl');
$smarty->display("tiki.tpl");
