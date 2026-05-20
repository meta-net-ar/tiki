<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section_class = 'tiki_wiki_page manage';
$inputConfiguration = [
    [
        'staticKeyFilters' => [
            'offset'    => 'int',
            'sort_mode' => 'alnumdash',
            'find'      => 'string',
        ],
    ],
];
require_once('tiki-setup.php');

$access->check_feature('feature_wiki');
$access->check_permission('tiki_p_remove');

$offset = isset($_REQUEST['offset']) ? (int) $_REQUEST['offset'] : 0;
if (! isset($_REQUEST['sort_mode'])) {
    $sort_mode = 'deletedAt_desc';
} else {
    $sort_mode = $_REQUEST['sort_mode'];
}
$find = isset($_REQUEST['find']) ? strip_tags($_REQUEST['find']) : '';

$table = TikiDb::get()->table('tiki_wiki_recycle_bin');

// Build sort order
$sort_map = [
    'pageName_asc'   => ['pageName' => 'ASC'],
    'pageName_desc'  => ['pageName' => 'DESC'],
    'deletedAt_asc'  => ['deletedAt' => 'ASC'],
    'deletedAt_desc' => ['deletedAt' => 'DESC'],
    'deletedBy_asc'  => ['deletedBy' => 'ASC'],
    'deletedBy_desc' => ['deletedBy' => 'DESC'],
];
$order = $sort_map[$sort_mode] ?? ['deletedAt' => 'DESC'];

$conditions = [];
if ($find !== '') {
    $conditions['pageName'] = $table->like('%' . $find . '%');
}

$maxRecords = isset($_REQUEST['maxRecords']) ? (int) $_REQUEST['maxRecords'] : $prefs['maxRecords'];

$pages = $table->fetchAll(
    ['pageName', 'deletedAt', 'deletedBy', 'comment'],
    $conditions,
    $maxRecords,
    $offset,
    $order
);

$total = $table->fetchCount($conditions);

$smarty->assign('recycle_pages', $pages);
$smarty->assign('sort_mode', $sort_mode);
$smarty->assign('find', $find);
$smarty->assign('offset', $offset);
$smarty->assign('maxRecords', $maxRecords);
$smarty->assign('total', $total);
$pages_count = $maxRecords > 0 ? ceil($total / $maxRecords) : 1;
$smarty->assign('pages_count', $pages_count);
$smarty->assign('next_offset', ($total > $offset + $maxRecords) ? $offset + $maxRecords : -1);
$smarty->assign('prev_offset', ($offset > 0) ? $offset - $maxRecords : -1);
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

$smarty->assign('mid', 'tiki-wiki_recycle_bin.tpl');
$smarty->display('tiki.tpl');
