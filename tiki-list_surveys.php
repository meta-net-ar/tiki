<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'surveys';
$inputConfiguration = [
    [
        'staticKeyFilters'         => [
        'sort_mode'                => 'alnumdash',          //get
        'offset'                   => 'int',                //get
        'find'                     => 'string',             //post
        ],
    ],
];
require_once('tiki-setup.php');
include_once('lib/surveys/surveylib.php');
$access->check_feature('feature_surveys');
$access->check_permission('tiki_p_take_survey');

if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'created_desc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$offset = $_REQUEST["offset"] ?? 0;
$smarty->assign_by_ref('offset', $offset);
$find = $_REQUEST["find"] ?? '';
$smarty->assign('find', $find);
$smarty->assign_by_ref('sort_mode', $sort_mode);
$channels = $srvlib->list_surveys($offset, $maxRecords, $sort_mode, $find);
Perms::bulk([ 'type' => 'survey' ], 'object', $channels['data'], 'surveyId');
$temp_max = count($channels["data"]);
for ($i = 0; $i < $temp_max; $i++) {
    $survperms = Perms::get([ 'type' => 'survey', 'object' => $channels['data'][$i]['surveyId'] ]);
    $channels["data"][$i]["individual_tiki_p_take_survey"] = $survperms->take_survey ? 'y' : 'n';
    $channels["data"][$i]["individual_tiki_p_view_survey_stats"] = $survperms->view_survey_stats ? 'y' : 'n';
    $channels["data"][$i]["individual_tiki_p_admin_surveys"] = $survperms->admin_surveys ? 'y' : 'n';

    if ($tikilib->user_has_voted($user, 'survey' . $channels["data"][$i]["surveyId"])) {
        $channels["data"][$i]["taken_survey"] = 'y';
    } else {
        $channels["data"][$i]["taken_survey"] = 'n';
    }
}
$smarty->assign_by_ref('pages_count', $channels["count"]);
$smarty->assign_by_ref('channels', $channels["data"]);
include_once('tiki-section_options.php');
// Display the template
$smarty->assign('mid', 'tiki-list_surveys.tpl');
$smarty->display("tiki.tpl");
