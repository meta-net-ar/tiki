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
        'staticKeyFilters'     => [
        'pageName'              => 'pagename',          //get
        ],
    ],
];

require_once('tiki-setup.php');
include_once('lib/htmlpages/htmlpageslib.php');
use Tiki\Sections;
$section = Sections::SECTION_HTML_PAGES;
Sections::setCurrentSection($section);
$statslib = TikiLib::lib('stats');
$access->check_feature('feature_html_pages');
$access->check_permission('tiki_p_view_html_pages');
if (! isset($_REQUEST["pageName"])) {
    Feedback::errorAndDie(tra("No page indicated"), \Laminas\Http\Response::STATUS_CODE_400);
}
$page_data = $htmlpageslib->get_html_page($_REQUEST["pageName"]);
$smarty->assign('type', $page_data["type"]);
$smarty->assign('refresh', $page_data["refresh"]);
$smarty->assign('pageName', $_REQUEST["pageName"]);
$smarty->assign('headtitle', $_REQUEST["pageName"]);
$parsed = $htmlpageslib->parse_html_page($_REQUEST["pageName"], $page_data["content"]);
$smarty->assign_by_ref('parsed', $parsed);
include_once('tiki-section_options.php');
if ($prefs['feature_theme_control'] == 'y') {
    $cat_type = 'html page';
    $cat_objid = $_REQUEST['pageName'];
    include('tiki-tc.php');
}
//add a hit
$statslib->stats_hit($_REQUEST['pageName'], "html_pages");
// Display the template
$smarty->assign('mid', 'tiki-page.tpl');
$smarty->display("tiki.tpl");
