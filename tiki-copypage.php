<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section_class = "tiki_wiki_page manage";   // This will be body class instead of $section
$inputConfiguration = [
    [
        'staticKeyFilters'                => [
            'page'                         => 'pagename',          // get
            'version'                      => 'word',               // get
            'copy'                         => 'bool',              // post
            'confirm'                      => 'bool',              // post
            'badname'                      => 'pagename',          // post
            'newpage'                      => 'pagename',          // post
            'dupCateg'                     => 'word',              // post
            'dupTags'                      => 'word',              // post
        ],
    ],
];
require_once('tiki-setup.php');
use Tiki\Sections;
$section = Sections::SECTION_WIKI;
Sections::setCurrentSection($section);
$histlib = TikiLib::lib('hist');
$wikilib = TikiLib::lib('wiki');
$userlib = TikiLib::lib('user');

$access->check_feature('feature_wiki');

// Get the page from the request var or default it to HomePage
if (! isset($_REQUEST["page"])) {
    Feedback::errorAndDie(tra("No page indicated"), \Laminas\Http\Response::STATUS_CODE_400);
} else {
    $page = $_REQUEST["page"];
    $smarty->assign_by_ref('page', $_REQUEST["page"]);
}
if (! ($info = $tikilib->get_page_info($page))) {
    // First, try cleaning the url to see if it matches an existing page.
    $wikilib->clean_url_suffix_and_redirect($page, $type = '', $path = '', $prefix = '');

    // If after cleaning the url, the page does not exist then display an error
    Feedback::errorAndDie(tra('Page cannot be found'), \Laminas\Http\Response::STATUS_CODE_404);
}

$tikilib->get_perm_object($page, 'wiki page', $info);
$access->check_permission(['tiki_p_edit']);

if (! empty($_REQUEST["version"]) && $_REQUEST["version"] <> "last") {
    $smarty->assign_by_ref('version', $_REQUEST["version"]);
    $version = $_REQUEST["version"];
} else {
    $smarty->assign('version', 'last'); //get_strings tra('last version');
    $version = "last";
}
// If the page doesn't exist then display an error
if (! $tikilib->page_exists($page)) {
    Feedback::errorAndDie(tra('Page cannot be found'), \Laminas\Http\Response::STATUS_CODE_404);
}

$smarty->assign('tiki_p_add_object', $userlib->user_has_permission($user, 'tiki_p_add_object'));
$smarty->assign('tiki_p_freetags_tag', $userlib->user_has_permission($user, 'tiki_p_freetags_tag'));

if (isset($_REQUEST["copy"]) || isset($_REQUEST["confirm"])) {
    $access->checkCsrf();
    // If the new pagename does match userpage prefix then display an error
    $newName = isset($_REQUEST["confirm"]) ? $_REQUEST['badname'] : $_REQUEST['newpage'];
    if (stristr($newName, $prefs['feature_wiki_userpage_prefix']) == $newName) {
        Feedback::errorAndDie(tra("Cannot rename page because the new name begins with reserved prefix") . ' (' . $prefs['feature_wiki_userpage_prefix'] . ').', \Laminas\Http\Response::STATUS_CODE_409);
    }
    $dupCateg = isset($_REQUEST['dupCateg']) && $_REQUEST['dupCateg'] === 'y';
    $dupTags = isset($_REQUEST['dupTags']) && $_REQUEST['dupTags'] === 'y';

    $smarty->assign('newname', $newName);
    $result = false;

    if (! isset($_REQUEST["confirm"]) && $wikilib->contains_badchars($newName)) {
        $smarty->assign('page_badchars_display', $wikilib->get_badchars());
    } else {
        $result = $wikilib->wiki_duplicate_page($page, $newName, $dupCateg, $dupTags);

        if ($result) {
            if ($prefs['feature_sefurl'] == 'y') {
                include_once('tiki-sefurl.php');
                header('location: ' . urlencode(filter_out_sefurl("tiki-index.php?page=$newName", 'wiki')));
            } else {
                header('location: tiki-index.php?page=' . urlencode($newName));
            }
        } else {
            Feedback::errorAndDie(tra("Cannot copy page because maybe new page name already exists"), \Laminas\Http\Response::STATUS_CODE_409);
        }
        die;
    }
}
include_once('tiki-section_options.php');
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('mid', 'tiki-copypage.tpl');
$smarty->display("tiki.tpl");
