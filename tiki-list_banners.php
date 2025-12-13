<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'       => [
        'remove'                 => 'word',               //post
        'sort_mode'              => 'word',               //get
        'offset'                 => 'int',                //get
        ],
    ],
];
require_once('tiki-setup.php');

$access->check_feature('feature_banners');

$bannerlib = TikiLib::lib('banner');

if (isset($_REQUEST["remove"]) && $access->checkCsrf()) {
    if ($tiki_p_admin_banners != 'y') {
        Feedback::errorAndDie(tra("You do not have permission to remove banners"), \Laminas\Http\Response::STATUS_CODE_401);
    }
    $bannerlib->remove_banner($_REQUEST["remove"]);
}
// This script can receive the threshold
// for the information as the number of
// days to get in the log 1,3,4,etc
// it will default to 1 recovering information for today
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'created_desc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$smarty->assign_by_ref('sort_mode', $sort_mode);
// If offset is set use it if not then use offset =0
// use the maxRecords php variable to set the limit
// if sortMode is not set then use lastModif_desc
$offset = $_REQUEST["offset"] ?? 0;
$smarty->assign_by_ref('offset', $offset);
$find = $_REQUEST["find"] ?? '';
$smarty->assign('find', $find);
// Get a list of last changes to the Wiki database
if ($tiki_p_admin_banners == 'y') {
    $who = 'admin';
} else {
    $who = $user;
}
$listpages = $bannerlib->list_banners($offset, $maxRecords, $sort_mode, $find, $who);
$smarty->assign_by_ref('pages_count', $listpages["count"]);
$smarty->assign_by_ref('listpages', $listpages["data"]);
$smarty->assign('mid', 'tiki-list_banners.tpl');
$smarty->display("tiki.tpl");
