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
            'which'                    => 'word',                //post
            'limit'                    => 'digits',              //post
        ],
    ]
];
require_once('tiki-setup.php');
include_once('lib/rankings/ranklib.php');
use Tiki\Sections;
$section = Sections::SECTION_BLOGS;
Sections::setCurrentSection($section);
$access->check_feature(['feature_blogs', 'feature_blog_rankings']);
$access->check_permission('tiki_p_read_blog');

$allrankings = [
    [
        'name' => tra('Top visited blogs') ,
        'value' => 'blog_ranking_top_blogs'
    ] ,
    [
        'name' => tra('Last posts') ,
        'value' => 'blog_ranking_last_posts'
    ] ,
    [
        'name' => tra('Top active blogs') ,
        'value' => 'blog_ranking_top_active_blogs'
    ]
];
$smarty->assign('allrankings', $allrankings);
$allowedRankings = array_column($allrankings, 'value');
$defaultWhich = 'blog_ranking_top_blogs';
if (! isset($_REQUEST["which"]) || ! in_array($_REQUEST["which"], $allowedRankings, true)) {
    $which = $defaultWhich;
} else {
    $which = $_REQUEST["which"];
}
$smarty->assign('which', $which);
// Get the page from the request var or default it to HomePage
$limit = isset($_REQUEST["limit"]) ? (int) $_REQUEST["limit"] : 10;
if ($limit < 1) {
    $limit = 10;
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
include_once('tiki-section_options.php');
$smarty->assign_by_ref('rankings', $rankings);
$smarty->assign('rpage', 'tiki-blog_rankings.php');

// Display the template
$smarty->assign('mid', 'tiki-ranking.tpl');
$smarty->display("tiki.tpl");
