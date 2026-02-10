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
        'quizId'                   => 'int',            //get
        'resultId'                 => 'int',            //get
        'userResultId'             => 'int',            //get
        ],
    ],
];

require_once('tiki-setup.php');
use Tiki\Sections;
$section = Sections::SECTION_QUIZZES;
Sections::setCurrentSection($section);
$quizlib = TikiLib::lib('quiz');
$access->check_feature('feature_quizzes');
if (! isset($_REQUEST["quizId"])) {
    Feedback::errorAndDie(tra("No quiz indicated"), \Laminas\Http\Response::STATUS_CODE_409);
}
$smarty->assign('individual', 'n');

$tikilib->get_perm_object($_REQUEST["quizId"], 'quiz');
$access->check_permission('tiki_p_view_user_results');
$quiz_info = $quizlib->get_quiz($_REQUEST["quizId"]);
$smarty->assign('quizId', $_REQUEST["quizId"]);
$smarty->assign('quiz_info', $quiz_info);
if (! isset($_REQUEST["resultId"])) {
    Feedback::errorAndDie(tra("No result indicated"), \Laminas\Http\Response::STATUS_CODE_409);
}
$smarty->assign('resultId', $_REQUEST["resultId"]);
if (! isset($_REQUEST["userResultId"])) {
    Feedback::errorAndDie(tra("No result indicated"), \Laminas\Http\Response::STATUS_CODE_409);
}
$smarty->assign('userResultId', $_REQUEST["userResultId"]);
$ur_info = $quizlib->get_user_quiz_result($_REQUEST["userResultId"]);
$smarty->assign('ur_info', $ur_info);
$result = $quizlib->get_quiz_result($_REQUEST["resultId"]);

$smarty->assign_by_ref('result', $result);
$questions = $quizlib->get_user_quiz_questions($_REQUEST["userResultId"]);
$smarty->assign('questions', $questions);
include_once('tiki-section_options.php');

$smarty->assign('mid', 'tiki-quiz_result_stats.tpl');
$smarty->display("tiki.tpl");
