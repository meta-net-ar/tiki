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
        'staticKeyFilters'       => [
        'remove'                 => 'int',                //get
        'refresh'                => 'bool',               //get
        'sort_mode'              => 'word',               //get
        'offset'                 => 'int',                //get
        'find'                   => 'string',             //get
        ],
    ],
];
require_once('tiki-setup.php');
$access->check_permission('tiki_p_admin');
if (isset($_REQUEST["remove"]) && $access->checkCsrf()) {
    $tikilib->remove_cache($_REQUEST["remove"]);
}
if (isset($_REQUEST["refresh"])) {
    $access->checkCsrf();
    $tikilib->refresh_cache($_REQUEST["refresh"]);
}
// This script can receive the threshold
// for the information as the number of
// days to get in the log 1,3,4,etc
// it will default to 1 recovering information for today
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'url_desc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$smarty->assign_by_ref('sort_mode', $sort_mode);

if (! isset($_REQUEST["offset"])) {
    $offset = 0;
} else {
    $offset = $_REQUEST["offset"];
}
$smarty->assign_by_ref('offset', $offset);
if (! isset($_REQUEST["find"])) {
    $find = '';
} else {
    $find = $_REQUEST["find"];
}
$smarty->assign('find', $find);
// Get a list of last changes to the Wiki database
$listpages = $tikilib->list_cache($offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('pages_count', $listpages["count"]);
$smarty->assign_by_ref('listpages', $listpages["data"]);

// Display the template
$smarty->assign('mid', 'tiki-list_cache.tpl');
$smarty->display("tiki.tpl");
