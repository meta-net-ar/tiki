<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('tiki-setup.php');
include_once('lib/directory/dirlib.php');
$access->check_feature('feature_directory');
$access->check_permission('tiki_p_view_directory');
if (! isset($_REQUEST['siteId'])) {
    Feedback::errorAndDie(tra("No site indicated"), \Laminas\Http\Response::STATUS_CODE_400);
}

$site_info = $dirlib->dir_get_site($_REQUEST['siteId']);
if (! $site_info) {
    Feedback::errorAndDie(tra("Site not found"), \Laminas\Http\Response::STATUS_CODE_404);
}

$url = $site_info['url'];
// Add a hit to the site
$dirlib->dir_add_site_hit($_REQUEST['siteId']);
// Redirect to the site URI
header("location: $url");
die;
