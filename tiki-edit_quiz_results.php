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
        'staticKeyFilters'                => [
            'quizId'                      => 'int',            //post
            'resultId'                    => 'int',            //post
            'remove'                      => 'int',            //post
            'save'                        => 'bool',           //post
            'fromPoints'                  => 'float',         //post
            'toPoints'                    => 'float',         //post
            'answer'                      => 'xss',            //post
            'sort_mode'                   => 'alnumdash',         //get
            'offset'                      => 'int',            //get
            'find'                        => 'striptags',          //post
        ],
    ],
];
require_once('tiki-setup.php');
$quizlib = TikiLib::lib('quiz');

$auto_query_args = ['quizId', 'resultId', 'sort_mode', 'offset', 'find'];
$access->check_feature('feature_quizzes');

if (! isset($_REQUEST["quizId"])) {
    Feedback::errorAndDie(tra("No quiz indicated"), \Laminas\Http\Response::STATUS_CODE_409);
}

$tikilib->get_perm_object($_REQUEST["quizId"], 'quiz');

$smarty->assign('individual', 'n');
$access->check_permission('tiki_p_admin_quizzes');
$smarty->assign('quizId', $_REQUEST["quizId"]);
$quiz_info = $quizlib->get_quiz($_REQUEST["quizId"]);
if (! $quiz_info) {
    Feedback::errorAndDie(tra("The requested quiz was not found. Please check the quiz ID and try again."), \Laminas\Http\Response::STATUS_CODE_404);
}


$smarty->assign('quiz_info', $quiz_info);

if (! isset($_REQUEST["resultId"])) {
    $_REQUEST["resultId"] = 0;
}

$smarty->assign('resultId', $_REQUEST["resultId"]);

if ($_REQUEST["resultId"]) {
    $info = $quizlib->get_quiz_result($_REQUEST["resultId"]);
} else {
    $info = [];

    $info["fromPoints"] = 0;
    $info["toPoints"] = 0;
    $info["answer"] = '';
}

$smarty->assign('answer', $info["answer"]);
$smarty->assign('fromPoints', $info["fromPoints"]);
$smarty->assign('toPoints', $info["toPoints"]);

if (isset($_REQUEST["remove"]) && $access->checkCsrf()) {
    $quizlib->remove_quiz_result($_REQUEST["remove"]);
}

if (isset($_REQUEST["save"])) {
    $access->checkCsrf();
    $quizlib->replace_quiz_result(
        $_REQUEST["resultId"],
        $_REQUEST["quizId"],
        $_REQUEST["fromPoints"],
        $_REQUEST["toPoints"],
        $_REQUEST["answer"]
    );

    $smarty->assign('answer', '');
    $smarty->assign('resultId', 0);
}

if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'fromPoints_asc';
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
$channels = $quizlib->list_quiz_results($_REQUEST["quizId"], $offset, $maxRecords, $sort_mode, $find);

$smarty->assign_by_ref('pages_count', $channels["count"]);

$smarty->assign_by_ref('channels', $channels["data"]);

// Fill array with possible number of questions per page
$positions = [];

for ($i = 1; $i < 100; $i++) {
    $positions[] = $i;
}

$smarty->assign('positions', $positions);

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

// Display the template
$smarty->assign('mid', 'tiki-edit_quiz_results.tpl');
$smarty->display("tiki.tpl");
