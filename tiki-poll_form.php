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
        'staticKeyFilters'         => [
        'pollId'               => 'bool',            //get
        ],
    ],
];
require_once('tiki-setup.php');
use Tiki\Sections;
$section = Sections::SECTION_POLL;
Sections::setCurrentSection($section);
$polllib = TikiLib::lib('poll');
$access->check_feature('feature_polls');
$access->check_permission('tiki_p_vote_poll');
if (empty($_REQUEST["pollId"])) {
    Feedback::errorAndDie(tra("No poll indicated"), \Laminas\Http\Response::STATUS_CODE_400);
}
$poll_info = $polllib->get_poll($_REQUEST["pollId"]);
if (empty($poll_info)) {
    Feedback::errorAndDie(tra("No poll indicated"), \Laminas\Http\Response::STATUS_CODE_400);
}
$options = $polllib->list_poll_options($_REQUEST["pollId"]);
$smarty->assign_by_ref('menu_info', $poll_info);
$smarty->assign_by_ref('channels', $options);
$smarty->assign('ownurl', $tikilib->httpPrefix() . $_SERVER["REQUEST_URI"]);
// Display the template
$smarty->assign('title', $poll_info['title']);
$smarty->assign('mid', 'tiki-poll_form.tpl');
$smarty->display("tiki.tpl");
