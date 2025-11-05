<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'         => [
        'page'                     => 'pagename',           //get
        'version'                  => 'int',                //post
        'rollback'                 => 'bool',               //post
        'comment'                  => 'text',               //post
        ],
    ],
];
require_once('tiki-setup.php');
$histlib = TikiLib::lib('hist');
$wikilib = TikiLib::lib('wiki');

$access->check_feature('feature_wiki');

// Get the page from the request var or default it to HomePage
if (! isset($_REQUEST["page"])) {
    Feedback::errorAndDie(tra("No page indicated"), \Laminas\Http\Response::STATUS_CODE_409);
} else {
    $page = $_REQUEST["page"];
    $smarty->assign_by_ref('page', $_REQUEST["page"]);
}
if (! isset($_REQUEST["version"])) {
    Feedback::errorAndDie(tra("No version indicated"), \Laminas\Http\Response::STATUS_CODE_409);
} else {
    $version = $_REQUEST["version"];
    $smarty->assign_by_ref('version', $_REQUEST["version"]);
}
if (! ($info = $tikilib->get_page_info($page))) {
    // First, try cleaning the url to see if it matches an existing page.
    $wikilib->clean_url_suffix_and_redirect($page, $type = '', $path = '', $prefix = '');

    // If after cleaning the url, the page does not exist then display an error
    Feedback::errorAndDie(tra('Page cannot be found'), \Laminas\Http\Response::STATUS_CODE_404);
}
if (! $histlib->version_exists($page, $version)) {
    Feedback::errorAndDie(tra("Non-existent version"), \Laminas\Http\Response::STATUS_CODE_404);
}

$tikilib->get_perm_object($page, 'wiki page', $info);
$access->check_permission(['tiki_p_rollback', 'tiki_p_edit']);

if (isset($_REQUEST["rollback"], $_REQUEST["comment"]) && $access->checkCsrf()) {
    $comment = $_REQUEST["comment"];
    $histlib->use_version($page, $version, $comment);
    $tikilib->invalidate_cache($page);

    header("location: tiki-index.php?page=" . urlencode($page));
    die;
}
$version = $histlib->get_version($page, $version);
$version["data"] = TikiLib::lib('parser')->parse_data($version["data"], ['preview_mode' => true, 'is_html' => $version['is_html']]);
$smarty->assign_by_ref('preview', $version);
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('mid', 'tiki-rollback.tpl');
$smarty->display("tiki.tpl");
