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
            'attId'        => 'int',           //get
        ]
    ],
];

require_once('tiki-setup.php');
$trklib = TikiLib::lib('trk');

$access->check_feature('feature_trackers');

if (! isset($_REQUEST["attId"])) {
    Feedback::errorAndDie(tra("No item indicated"), \Laminas\Http\Response::STATUS_CODE_400);
}
$info = $trklib->get_moreinfo($_REQUEST["attId"]);
$trackerId = $info['trackerId'];
unset($info['trackerId']);
if (! $trackerId) {
    Feedback::errorAndDie(tra('That tracker does not use extras.'), \Laminas\Http\Response::STATUS_CODE_400, "error_simple.tpl");
}
$smarty->assign('trackerId', $trackerId);
$tikilib->get_perm_object($trackerId, 'tracker');

$access->check_permission('tiki_p_view_trackers');

$smarty->assign("info", $info);
$smarty->display("tiki-view_tracker_more_info.tpl");
