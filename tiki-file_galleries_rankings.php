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
            'which'                     => 'word',              //post
            'limit'                     => 'digits',            //post
        ],
    ],
];
require_once('tiki-setup.php');
include_once('lib/rankings/ranklib.php');
$access->check_feature(['feature_file_galleries','feature_file_galleries_rankings']);

if ((isset($tiki_p_list_file_galleries) && $tiki_p_list_file_galleries != 'y') || (! isset($tiki_p_list_file_galleries) && $tiki_p_view_file_gallery != 'y')) {
    Feedback::errorAndDie(tra("You do not have permission to view this section"), \Laminas\Http\Response::STATUS_CODE_401);
}

$allrankings = [
    [
    'name' => tra('Most-visited file galleries'),
    'value' => 'filegal_ranking_top_galleries'
    ],
    [
    'name' => tra('Most downloaded files'),
    'value' => 'filegal_ranking_top_files'
    ],
    [
    'name' => tra('Last files'),
    'value' => 'filegal_ranking_last_files'
    ],
];

$smarty->assign('allrankings', $allrankings);

if (! isset($_REQUEST["which"])) {
    $which = 'filegal_ranking_top_files';
} else {
    $which = $_REQUEST["which"];
}

$smarty->assign('which', $which);

// Get the page from the request var or default it to HomePage
if (! isset($_REQUEST["limit"])) {
    $limit = 10;
} else {
    $limit = $_REQUEST["limit"];
}

$smarty->assign_by_ref('limit', $limit);

// Rankings:
// Top Pages
// Last pages
// Top Authors
$rankings = [];

$rk = $ranklib->$which($limit);
$rank["data"] = $rk["data"];
$rank["title"] = $rk["title"];
$rank["y"] = $rk["y"];
$rank["type"] = $rk["type"];
$rankings[] = $rank;

$smarty->assign_by_ref('rankings', $rankings);
$smarty->assign('rpage', 'tiki-file_galleries_rankings.php');

// Display the template
$smarty->assign('mid', 'tiki-ranking.tpl');
$smarty->display("tiki.tpl");
