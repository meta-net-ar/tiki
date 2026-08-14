<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('tiki-setup.php');
$access->check_feature('feature_mailin');
include_once('tiki-mailin-code.php');
require_once('tiki-setup.php');
global $mailin_results;
$smarty->assign('results', $mailin_results ?? []);
$smarty->assign('mid', 'tiki-mailin.tpl');
$smarty->display("tiki.tpl");
