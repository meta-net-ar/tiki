<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Lib\Quizzes\Helper\QuizUtils;

$inputConfiguration = [
    [
        'staticKeyFilters'                => [
            'quizId'                      => 'int',            //post
            'questionId'                  => 'int',            //post
            'question'                    => 'text',           //post
            'remove'                      => 'int',            //post
            'save'                        => 'bool',           //post
            'questionType'                => 'string',         //post
            'position'                    => 'string',         //post
            'import'                      => 'bool',           //post
            'input_data'                  => 'text',           //post
            'useQuestion'                 => 'bool',           //post
            'usequestionid'               => 'int',            //post
            'sort_mode'                   => 'text',          //get
            'offset'                      => 'int',            //get
            'find'                        => 'alpha',          //post
        ],
    ],
];
require_once('tiki-setup.php');
$quizlib = TikiLib::lib('quiz');

$auto_query_args = ['quizId', 'questionId', 'sort_mode', 'offset', 'find'];
$access->check_feature('feature_quizzes');

if (! isset($_REQUEST['quizId'])) {
    Feedback::errorAndDie(tra('No quiz indicated'), \Laminas\Http\Response::STATUS_CODE_409);
}

$tikilib->get_perm_object($_REQUEST['quizId'], 'quiz');

$access->check_permission('tiki_p_admin_quizzes');

$smarty->assign('quizId', $_REQUEST['quizId']);

$smarty->assign('individual', 'n');

$quiz_info = $quizlib->get_quiz($_REQUEST['quizId']);
if (! $quiz_info) {
    Feedback::errorAndDie(tra("The requested quiz was not found. Please check the quiz ID and try again."), \Laminas\Http\Response::STATUS_CODE_404);
}
$smarty->assign('quiz_info', $quiz_info);

if (! isset($_REQUEST['questionId'])) {
    $_REQUEST['questionId'] = 0;
}

$smarty->assign('questionId', $_REQUEST['questionId']);

$result = $quizlib->get_quiz_question($_REQUEST['questionId']);
if ($_REQUEST['questionId'] > 0 && ! $result) {
    Feedback::error(tra("The question you are trying to edit was not found. Please verify the question ID or create a new one."));
}

if (! $result) {
    $info = [];

    $info['question'] = '';
    $info['type'] = '';
    $info['position'] = '';
} else {
    $info = $result;
}

$smarty->assign('question', $info['question']);
$smarty->assign('type', $info['type']);
$smarty->assign('position', $info['position']);

if (isset($_REQUEST['remove']) && $access->checkCsrf()) {
    $quizlib->remove_quiz_question($_REQUEST['remove']);
    Feedback::success(tr('The question has been successfully deleted'));
}

if (isset($_REQUEST['save'])) {
    $access->checkCsrf();

    $result = $quizlib->replace_quiz_question(
        $_REQUEST['questionId'],
        $_REQUEST['question'],
        $_REQUEST['questionType'],
        $_REQUEST['quizId'],
        $_REQUEST['position']
    );
    if (is_numeric($result) && $result > 0) {
        Feedback::success(tr('The question has been saved successfully'));
    }
    $smarty->assign('question', '');
    $smarty->assign('questionId', 0);
}

if (isset($_REQUEST['import'])) {
    $access->checkCsrf();

    $questions = QuizUtils::TextToQuestions($_REQUEST['input_data']);

    foreach ($questions as $index => $question) {
        $question_text = $question->getQuestion();
        $id = $quizlib->replace_quiz_question(0, $question_text, 'o', $_REQUEST['quizId'], $index);
        $temp_max = $question->getChoiceCount();
        for ($i = 0; $i < $temp_max; $i++) {
            $a = $question->GetChoice($i);
            $b = $question->GetCorrect($i);
            $quizlib->replace_question_option(0, $a, $b, $id);
        }
    }

    $smarty->assign('question', '');
    $smarty->assign('questionId', 0);
}

if (isset($_REQUEST['useQuestion'])) {
    $access->checkCsrf();
    $info = $quizlib->get_quiz_question($_REQUEST['usequestionid']);

    $qid = $quizlib->replace_quiz_question(0, $info['question'], $info['type'], $_REQUEST['quizId'], $_REQUEST['position']);
    $options = $quizlib->list_quiz_question_options($info['questionId'], 0, -1, 'points_desc', '');

    foreach ($options['data'] as $opt) {
        $quizlib->replace_question_option(0, $opt['optionText'], $opt['points'], $qid);
    }
}

if (! isset($_REQUEST['sort_mode'])) {
    $sort_mode = 'position_asc';
} else {
    $sort_mode = $_REQUEST['sort_mode'];
}

if (! isset($_REQUEST['offset'])) {
    $offset = 0;
} else {
    $offset = $_REQUEST['offset'];
}

$smarty->assign_by_ref('offset', $offset);

if (isset($_REQUEST['find'])) {
    $find = $_REQUEST['find'];
} else {
    $find = '';
}

$smarty->assign('find', $find);

$smarty->assign_by_ref('sort_mode', $sort_mode);
$channels = $quizlib->list_quiz_questions($_REQUEST['quizId'], $offset, $maxRecords, $sort_mode, $find);
// GGG turned this off as we now have too many questions in the db for this to work.
// $questions = $quizlib->list_all_questions(0, -1, 'position_desc', '');
// $smarty->assign('questions', $questions["data"]);

$smarty->assign_by_ref('pages_count', $channels['count']);

$smarty->assign_by_ref('channels', $channels['data']);

// Fill array with possible number of questions per page
$positions = [];

for ($i = 1; $i < 100; $i++) {
    $positions[] = $i;
}

$smarty->assign('positions', $positions);

$questionTypes = [];
$questionTypes['o'] = tr('Optional');
$questionTypes['f'] = tr('Optional + File');

$smarty->assign('questionTypes', $questionTypes);


// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

// Display the template
$smarty->assign('mid', 'tiki-edit_quiz_questions.tpl');
$smarty->display('tiki.tpl');
