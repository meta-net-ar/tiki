<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('tiki-setup.php');
use Tiki\Sections;
$section = Sections::SECTION_ADMIN_LAYOUT;
Sections::setCurrentSection($section);
$access->check_feature('feature_banners');
$access->check_permission('tiki_p_admin_banners');

// Display the template
$smarty->assign('mid', 'tiki-edit_banner.tpl');
$smarty->display("tiki.tpl");
