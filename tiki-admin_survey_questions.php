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
            'surveyId'              => 'int',              //get
            'questionId'            => 'int',              //post
            'mandatory'             => 'alpha',            //post
            'remove'                => 'int',              //post
            'position'              => 'int',              //post
            'type'                  => 'word',             //post
            'options'               => 'string',           //post
            'sort_mode'             => 'alnumdash',             //get
            'offset'                => 'int',              //get
            'save'                  => 'alpha',            //post
            'question'              => 'text',             //post
            'min_answers'           => 'int',              //post
            'max_answers'           => 'int',              //post
            'find'                  => 'string',           //get
        ],
        'staticKeyFiltersForArrays' => [
            'questionIds'           => 'string',              //get
        ],
    ],
];
require_once('tiki-setup.php');
include_once('lib/surveys/surveylib.php');
use Tiki\Sections;
$section = Sections::SECTION_ADMIN;
Sections::setCurrentSection($section);
$auto_query_args = [
    'surveyId',
    'questionId',
    'offset',
    'find',
    'sort_mode',
    'maxRecords'
];
$access->check_feature('feature_surveys');

if (! isset($_REQUEST["surveyId"])) {
    Feedback::errorAndDie(tra("No survey indicated"), \Laminas\Http\Response::STATUS_CODE_409);
}
$smarty->assign('surveyId', $_REQUEST["surveyId"]);
$tikilib->get_perm_object($_REQUEST['surveyId'], 'survey');

$access->check_permission('tiki_p_admin_surveys');

$survey_info = $srvlib->get_survey($_REQUEST["surveyId"]);
$smarty->assign('survey_info', $survey_info);
if (! isset($_REQUEST["questionId"])) {
    $_REQUEST["questionId"] = 0;
}
$smarty->assign('questionId', $_REQUEST["questionId"]);
if ($_REQUEST["questionId"]) {
    $info = $srvlib->get_survey_question($_REQUEST["questionId"]);
    $cookietab = 2;
} else {
    $info = [];
    $info["question"] = '';
    $info["type"] = '';
    $info["position"] = '';
    $info["options"] = '';
    $info["mandatory"] = '';
    $info["min_answers"] = '';
    $info["max_answers"] = '';
}
if (isset($_REQUEST["remove"]) && $access->checkCsrf()) {
    $srvlib->remove_survey_question($_REQUEST["remove"]);
    $cookietab = 1;
}
if (isset($_REQUEST["save"])) {
    $access->checkCsrf();
    $srvlib->replace_survey_question($_REQUEST["questionId"], $_REQUEST["question"], $_REQUEST["type"], $_REQUEST["surveyId"], $_REQUEST["position"], $_REQUEST["options"], isset($_REQUEST["mandatory"]) ? 'y' : 'n', $_REQUEST["min_answers"], $_REQUEST["max_answers"]);
    $info["question"] = '';
    $info["type"] = '';
    $info["position"] = '';
    $info["options"] = '';
    $info["mandatory"] = ! empty($_REQUEST['mandatory']) ? 'y' : '';
    $info["min_answers"] = '';
    $info["max_answers"] = '';
    $smarty->assign('questionId', 0);
    $cookietab = 1;
}
if (! empty($_REQUEST['questionIds']) && ! empty($_REQUEST['surveyId'])) {
    $ids = explode(',', $_REQUEST['questionIds']);
    $srvlib->reorderQuestions($_REQUEST['surveyId'], $ids);
}
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'position_asc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$offset = $_REQUEST["offset"] ?? 0;
$smarty->assign_by_ref('offset', $offset);
$find = $_REQUEST["find"] ?? '';
$smarty->assign('find', $find);
$smarty->assign_by_ref('sort_mode', $sort_mode);
$channels = $srvlib->list_survey_questions($_REQUEST["surveyId"], $offset, $maxRecords, $sort_mode, $find);
if (empty($info["position"])) {
    $info["position"] = $channels["count"] + 1;
}

$headerlib->add_jsfile('lib/surveys/tiki-admin_survey_questions.js');

$smarty->assign('types', $srvlib->get_types());
$smarty->assign_by_ref('info', $info);
$smarty->assign_by_ref('pages_count', $channels["count"]);
$smarty->assign_by_ref('channels', $channels["data"]);
// Fill array with possible number of questions per page
$positions = [];
for (
    $i = 1; $i < 100;
    $i++
) {
    $positions[] = $i;
}
$smarty->assign('positions', $positions);
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('mid', 'tiki-admin_survey_questions.tpl');
$smarty->display("tiki.tpl");
