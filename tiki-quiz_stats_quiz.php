<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'quizzes';

$inputConfiguration = [
    [
        'staticKeyFilters'         => [
        'quizId'                   => 'int',             //get
        'remove'                   => 'int',             //get
        'clear'                    => 'bool',            //post
        'sort_mode'                => 'alnumdash',       //get
        'offset'                   => 'int',             //get
        'find'                     => 'text',            //get
        ],
    ],
];

require_once('tiki-setup.php');
$quizlib = TikiLib::lib('quiz');

$access->check_feature('feature_quizzes');

$tikilib->get_perm_object($_REQUEST["quizId"], 'quiz');

$access->check_permission('tiki_p_view_quiz_stats');

if (! isset($_REQUEST["quizId"])) {
    Feedback::errorAndDie(tra("No quiz indicated"), \Laminas\Http\Response::STATUS_CODE_409);
}

$quiz_info = $quizlib->get_quiz($_REQUEST["quizId"]);
if (! $quiz_info) {
    Feedback::errorAndDie(tra("The requested quiz was not found. Please check the quiz ID and try again."), \Laminas\Http\Response::STATUS_CODE_404);
}
$smarty->assign('quizId', $_REQUEST["quizId"]);
$smarty->assign('quiz_info', $quiz_info);
if (isset($_REQUEST["remove"]) && $tiki_p_admin_quizzes == 'y' && $access->checkCsrf()) {
    $quizlib->remove_quiz_stat($_REQUEST["remove"]);
}
if (isset($_REQUEST["clear"]) && $tiki_p_admin_quizzes == 'y' && $access->checkCsrf()) {
    $quizlib->clear_quiz_stats($_REQUEST["clear"]);
}
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'timestamp_desc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
if (! isset($_REQUEST["offset"])) {
    $offset = 0;
} else {
    $offset = $_REQUEST["offset"];
}
$smarty->assign_by_ref('offset', $offset);
if (isset($_REQUEST["find"])) {
    $find = $_REQUEST["find"];
} else {
    $find = '';
}
$smarty->assign('find', $find);
$smarty->assign_by_ref('sort_mode', $sort_mode);
$channels = $quizlib->list_quiz_stats($_REQUEST["quizId"], $offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('pages_count', $channels["count"]);
$smarty->assign_by_ref('channels', $channels["data"]);
//Get all the statistics for this quiz
$questions = $quizlib->list_quiz_question_stats($_REQUEST["quizId"], 0, -1, 'position_asc', '');
$smarty->assign_by_ref('questions', $questions);
include_once('tiki-section_options.php');
// Display the template
$smarty->assign('mid', 'tiki-quiz_stats_quiz.tpl');
$smarty->display("tiki.tpl");
