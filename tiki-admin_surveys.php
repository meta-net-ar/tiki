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
        'staticKeyFilters'          => [
            'surveyId'              => 'int',                //get
            'restriction'           => 'alpha',              //post
            'name'                  => 'string',             //post
            'sort_mode'             => 'alnumdash',          //get
            'offset'                => 'int',                //get
            'description'           => 'xss',                //post
            'save'                  => 'alpha',              //post
            'status'                => 'alpha',              //post
            'remove'                => 'int',                //post
            'find'                  => 'string',             //post
        ],
    ],
];
require_once('tiki-setup.php');
include_once('lib/surveys/surveylib.php');
use Tiki\Sections;
$section = Sections::SECTION_ADMIN;
Sections::setCurrentSection($section);
$access->check_feature('feature_surveys');

$auto_query_args = [
    'surveyId',
    'offset',
    'sort_mode',
    'find'
];

if (! isset($_REQUEST["surveyId"])) {
    $_REQUEST["surveyId"] = 0;
}
$smarty->assign('surveyId', $_REQUEST["surveyId"]);
$tikilib->get_perm_object($_REQUEST['surveyId'], 'survey');
$access->check_permission('tiki_p_admin_surveys');
if (isset($_REQUEST["save"])) {
    $access->checkCsrf();
    if (empty($_REQUEST["name"]) || trim($_REQUEST["name"]) === '') {
        Feedback::error(tr('Survey name is required, Please provide a name'));
    } else {
        if (isset($_REQUEST["restriction"]) && $_REQUEST["restriction"] == 'on') {
            $restriction = 'y';
        } else {
            $restriction = 'n';
        }

        $isUpdating = ! empty($_REQUEST["surveyId"]);

        $sid = $srvlib->replace_survey($_REQUEST["surveyId"], $_REQUEST["name"], $_REQUEST["description"], $restriction, $_REQUEST["status"]);
        if ($sid) {
            if ($isUpdating) {
                Feedback::success(tr('Survey updated successfully.'));
            } else {
                Feedback::success(tr('Survey created successfully.'));
            }
            $cat_type = 'survey';
            $cat_objid = is_int($sid) ? $sid : $_REQUEST["surveyId"];
            $cat_desc = substr($_REQUEST["description"], 0, 200);
            $cat_name = $_REQUEST["name"];
            $cat_href = "tiki-take_survey.php?surveyId=" . $cat_objid;
            include_once("categorize.php");
            $cookietab = 1;
            $_REQUEST["surveyId"] = 0;
        } else {
            Feedback::error(tr('Failed to save survey.'));
        }
    }
}
if (! empty($_REQUEST["surveyId"])) {
    $info = $srvlib->get_survey($_REQUEST["surveyId"]);
    $cookietab = 2;
} else {
    $info = [];
    $info["name"] = '';
    $info["description"] = '';
    $info["status"] = 'o'; //check to see if survey is open
    $cookietab = 1;
}
$smarty->assign('info', $info);
if (isset($_REQUEST["remove"]) && $access->checkCsrf()) {
    $srvlib->remove_survey($_REQUEST["remove"]);
    Feedback::success(tr('The survey has been deleted successfully.'));
}
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
$temp_max = count($channels["data"]);
for ($i = 0; $i < $temp_max; $i++) {
    if ($userlib->object_has_one_permission($channels["data"][$i]["surveyId"], 'survey')) {
        $channels["data"][$i]["individual"] = 'y';
        if ($userlib->object_has_permission($user, $channels["data"][$i]["surveyId"], 'survey', 'tiki_p_take_survey')) {
            $channels["data"][$i]["individual_tiki_p_take_survey"] = 'y';
        } else {
            $channels["data"][$i]["individual_tiki_p_take_survey"] = 'n';
        }
        if ($userlib->object_has_permission($user, $channels["data"][$i]["surveyId"], 'survey', 'tiki_p_view_survey_stats')) {
            $channels["data"][$i]["individual_tiki_p_view_survey_stats"] = 'y';
        } else {
            $channels["data"][$i]["individual_tiki_p_view_survey_stats"] = 'n';
        }
        if ($tiki_p_admin == 'y' || $userlib->object_has_permission($user, $channels["data"][$i]["surveyId"], 'survey', 'tiki_p_admin_surveys')) {
            $channels["data"][$i]["individual_tiki_p_take_survey"] = 'y';
            $channels["data"][$i]["individual_tiki_p_view_survey_stats"] = 'y';
            $channels["data"][$i]["individual_tiki_p_admin_surveys"] = 'y';
        }
    } else {
        $channels["data"][$i]["individual"] = 'n';
    }
}
$smarty->assign_by_ref('pages_count', $channels["count"]);
$smarty->assign_by_ref('channels', $channels["data"]);
// Fill array with possible number of questions per page (qpp)
$qpp = [
    1,
    2,
    3,
    4
];
for (
    $i = 5; $i < 50;
    $i += 5
) {
    $qpp[] = $i;
}
$hrs = [];
for (
    $i = 0; $i < 10;
    $i++
) {
    $hrs[] = $i;
}
$mins = [];
for (
    $i = 1; $i < 120;
    $i++
) {
    $mins[] = $i;
}
$smarty->assign('qpp', $qpp);
$smarty->assign('hrs', $hrs);
$smarty->assign('mins', $mins);
$cat_type = 'survey';
$cat_objid = $_REQUEST["surveyId"];
include_once("categorize_list.php");
include_once('tiki-section_options.php');
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('mid', 'tiki-admin_surveys.tpl');
$smarty->display("tiki.tpl");
