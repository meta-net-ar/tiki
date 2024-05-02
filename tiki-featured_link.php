<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('tiki-setup.php');
include_once('lib/featured_links/flinkslib.php');

$access->check_feature('feature_featuredLinks');

// Get the page from the request var or default it to HomePage
if (! isset($_REQUEST["url"]) || ! $flinkslib->get_featured_link($_REQUEST['url'])) {
    Feedback::errorPage(tr('No page indicated'));
} else {
    $flinkslib->add_featured_link_hit($_REQUEST["url"]);
}

$section = 'featured_links';
include_once('tiki-section_options.php');

$smarty->assign_by_ref('url', $_REQUEST["url"]);
$smarty->assign('mid', 'tiki-featured_link.tpl');
$smarty->display("tiki.tpl");
