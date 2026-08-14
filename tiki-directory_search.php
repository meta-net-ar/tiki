<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

$inputConfiguration = [
    [ 'staticKeyFilters' =>
        [
            'offset' => 'digits',
            'parent' => 'digits',
            'find' => 'striptags',
            'where' => 'word',
            'how' => 'word',
            'words' => 'striptags',
            'sort_mode' => 'alnumdash',
        ],
    ]
];
require_once('tiki-setup.php');
include_once('lib/directory/dirlib.php');
use Tiki\Sections;
$section = Sections::SECTION_DIRECTORY;
Sections::setCurrentSection($section);

$access->check_feature('feature_directory');
$access->check_permission('tiki_p_view_directory');

$_REQUEST['words'] = $_REQUEST['words'] ?? '';
$_REQUEST['where'] = $_REQUEST['where'] ?? '';
$_REQUEST['how'] = $_REQUEST['how'] ?? '';
$_REQUEST['parent'] = $_REQUEST['parent'] ?? '';
$smarty->assign('words', $_REQUEST['words']);
$smarty->assign('where', $_REQUEST['where']);
$smarty->assign('how', $_REQUEST['how']);
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'hits_desc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$offset = $_REQUEST["offset"] ?? 0;
$find = $_REQUEST["find"] ?? '';
$smarty->assign_by_ref('offset', $offset);
$smarty->assign_by_ref('sort_mode', $sort_mode);
$smarty->assign('find', $find);
if (isset($_REQUEST['where']) && $_REQUEST['where'] == 'all') {
    $items = $dirlib->dir_search($_REQUEST['words'], $_REQUEST['how'], $offset, $maxRecords, $sort_mode);
} else {
    $items = $dirlib->dir_search_cat($_REQUEST['parent'], $_REQUEST['words'], $_REQUEST['how'], $offset, $maxRecords, $sort_mode);
}
$smarty->assign_by_ref('pages_count', $items["count"]);
$smarty->assign_by_ref('items', $items["data"]);
include_once('tiki-section_options.php');
// Display the template
$smarty->assign('mid', 'tiki-directory_search.tpl');
$smarty->display("tiki.tpl");
