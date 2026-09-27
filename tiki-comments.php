<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once('tiki-setup.php');

$smarty = TikiLib::lib('smarty');
$access = TikiLib::lib('access');
$access->check_permission('tiki_p_view');

// Mirrors the filtering Services_Comment_Controller::action_list applies, so the page can only
// hand the module a context the service would accept. Missing parameters filter down to null.
$commentsType = (string) $jitRequest->type->alphaspace();
$commentsObjectId = (string) ($commentsType === 'wiki page' ? $jitRequest->objectId->pagename() : $jitRequest->objectId->digits());

$smarty->assign('commentsVueType', $commentsType);
$smarty->assign('commentsVueObjectId', $commentsObjectId);

$smarty->assign('mid', 'tiki-comments.tpl');
$smarty->display("tiki.tpl");
