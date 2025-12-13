<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'                => [
            'quizId'                      => 'int',            //post
            'questionsPerPage'            => 'int',            //post
            'save'                        => 'bool',           //post
            'publish_Meridian'            => 'alpha',         //post
            'publish_Hour'                => 'int',         //post
            'expire_Meridian'             => 'alpha',         //post
            'expire_Hour'                 => 'int',         //post
            'publish_Minute'              => 'int',         //post
            'publish_Month'               => 'int',         //post
            'publish_Day'                 => 'int',         //post
            'publish_Year'                => 'int',         //post
            'expire_Minute'               => 'int',         //post
            'expire_Month'                => 'int',         //post
            'expire_Day'                  => 'int',         //post
            'expire_Year'                 => 'int',         //post
            'canRepeat'                   => 'bool',           //get
            'storeResults'                => 'bool',           //post
            'immediateFeedback'           => 'bool',           //post
            'showAnswers'                 => 'bool',          //get
            'shuffleQuestions'            => 'bool',           //post
            'shuffleAnswers'              => 'bool',
            'timeLimited'                 => 'bool',           //post
            'name'                        => 'text',         //post
            'description'                 => 'xss',            //get
            'timeLimit'                   => 'int',         //post
            'passingperct'                => 'int',         //post
            'remove'                      => 'int',            //post
            'sort_mode'                   => 'alnumdash',          //get
            'offset'                      => 'int',            //get
            'find'                        => 'striptags',          //get
            'cookietab'                   => 'int',            //get - for tab persistence
        ],
    ],
];

require_once('tiki-setup.php');

$access->check_feature('feature_quizzes');

$quizlib = TikiLib::lib('quiz');

if (! isset($_REQUEST["quizId"])) {
    $_REQUEST["quizId"] = 0;
}

$smarty->assign('quizId', $_REQUEST["quizId"]);
$smarty->assign('individual', 'n');

$tikilib->get_perm_object($_REQUEST["quizId"], 'quiz');
$access->check_permission('tiki_p_admin_quizzes');

$auto_query_args = [
    'quizId',
    'offset',
    'sort_mode',
    'find',
    'cookietab',
];

$_REQUEST["questionsPerPage"] = 999;

//Use 12- or 24-hour clock for $publishDate time selector based on admin and user preferences
$userprefslib = TikiLib::lib('userprefs');
$smarty->assign('use_24hr_clock', $userprefslib->get_user_clock_pref($user));

// Initialize validation errors array
$validation_errors = [];
$form_data = [];
$keep_tab_active = false;

$info = [];
$info["name"] = '';
$info["description"] = '';
$info["publishDate"] = $tikilib->now;
$cur_time = explode(',', $tikilib->date_format('%Y,%m,%d,%H,%M,%S', $info["publishDate"]));
$info["expireDate"] = $tikilib->make_time($cur_time[3], $cur_time[4], $cur_time[5], $cur_time[1], $cur_time[2], $cur_time[0] + 1);
$info["canRepeat"] = 'n';
$info["storeResults"] = 'n';
$info["immediateFeedback"] = 'n';
$info["showAnswers"] = 'n';
$info["shuffleQuestions"] = 'n';
$info["shuffleAnswers"] = 'n';
$info["questionsPerPage"] = 10;
$info["timeLimited"] = 'n';
$info["passingperct"] = '';
$info["timeLimit"] = 60 * 60;

// Function to validate form data
function validateQuizForm($data)
{
    $errors = [];
    // Validate quiz name (required)
    if (empty(trim($data['name']))) {
        $errors[] = tra("Quiz name is required and cannot be empty.");
    } elseif (strlen(trim($data['name'])) < 2) {
        $errors[] = tra("Quiz name must be at least 2 characters long.");
    }
    // Validate passing percentage
    if (isset($data['passingperct']) && $data['passingperct'] !== '') {
        if (! is_numeric($data['passingperct']) || $data['passingperct'] < 0 || $data['passingperct'] > 100) {
            $errors[] = tra("Passing percentage must be a number between 0 and 100.");
        }
    }
    // Validate time limit if time limited is enabled
    if (isset($data['timeLimited']) && $data['timeLimited'] == 'on') {
        if (! isset($data['timeLimit']) || ! is_numeric($data['timeLimit']) || $data['timeLimit'] <= 0) {
            $errors[] = tra("Time limit must be a positive number when time limited option is enabled.");
        }
    }
    // Validate dates
    $publishDate = mktime(
        $data["publish_Hour"] ?? 0,
        $data["publish_Minute"] ?? 0,
        0,
        $data["publish_Month"] ?? date('m'),
        $data["publish_Day"] ?? date('d'),
        $data["publish_Year"] ?? date('Y')
    );
    $expireDate = mktime(
        $data["expire_Hour"] ?? 0,
        $data["expire_Minute"] ?? 0,
        0,
        $data["expire_Month"] ?? date('m'),
        $data["expire_Day"] ?? date('d'),
        $data["expire_Year"] ?? date('Y')
    );
    if ($expireDate <= $publishDate) {
        $errors[] = tra("Expiration date must be after the publish date.");
    }
    return $errors;
}

// Handle form submission
if (isset($_REQUEST["save"])) {
    $access->checkCsrf();
    // Store form data for repopulation if validation fails
    $form_data = $_REQUEST;
    // Validate form
    $validation_errors = validateQuizForm($_REQUEST);
    if (empty($validation_errors)) {
        // Validation passed, proceed with saving
        //Convert 12-hour clock hours to 24-hour scale to compute time
        if (! empty($_REQUEST['publish_Meridian'])) {
            $_REQUEST['publish_Hour'] = date('H', strtotime($_REQUEST['publish_Hour'] . ':00 ' . $_REQUEST['publish_Meridian']));
        }
        if (! empty($_REQUEST['expire_Meridian'])) {
            $_REQUEST['expire_Hour'] = date('H', strtotime($_REQUEST['expire_Hour'] . ':00 ' . $_REQUEST['expire_Meridian']));
        }
        # convert from the displayed 'site' time to 'server' time
        $publishDate = $tikilib->make_time($_REQUEST["publish_Hour"], $_REQUEST["publish_Minute"], 0, $_REQUEST["publish_Month"], $_REQUEST["publish_Day"], $_REQUEST["publish_Year"]);
        $expireDate = $tikilib->make_time($_REQUEST["expire_Hour"], $_REQUEST["expire_Minute"], 0, $_REQUEST["expire_Month"], $_REQUEST["expire_Day"], $_REQUEST["expire_Year"]);

        // Process checkbox values
        $_REQUEST["canRepeat"] = (isset($_REQUEST["canRepeat"]) && $_REQUEST["canRepeat"] == 'on') ? 'y' : 'n';
        $_REQUEST["storeResults"] = (isset($_REQUEST["storeResults"]) && $_REQUEST["storeResults"] == 'on') ? 'y' : 'n';
        $_REQUEST["immediateFeedback"] = (isset($_REQUEST["immediateFeedback"]) && $_REQUEST["immediateFeedback"] == 'on') ? 'y' : 'n';
        $_REQUEST["showAnswers"] = (isset($_REQUEST["showAnswers"]) && $_REQUEST["showAnswers"] == 'on') ? 'y' : 'n';
        $_REQUEST["shuffleQuestions"] = (isset($_REQUEST["shuffleQuestions"]) && $_REQUEST["shuffleQuestions"] == 'on') ? 'y' : 'n';
        $_REQUEST["shuffleAnswers"] = (isset($_REQUEST["shuffleAnswers"]) && $_REQUEST["shuffleAnswers"] == 'on') ? 'y' : 'n';
        $_REQUEST["timeLimited"] = (isset($_REQUEST["timeLimited"]) && $_REQUEST["timeLimited"] == 'on') ? 'y' : 'n';
        try {
            // Save quiz
            $qid = $quizlib->replace_quiz(
                $_REQUEST["quizId"],
                trim($_REQUEST["name"]),
                $_REQUEST["description"],
                $_REQUEST["canRepeat"],
                $_REQUEST["storeResults"],
                'n',
                'n',
                'n',
                'n',
                $_REQUEST["questionsPerPage"],
                $_REQUEST["timeLimited"],
                $_REQUEST["timeLimit"],
                $publishDate,
                $expireDate,
                $_REQUEST["passingperct"]
            );
            $cat_type = 'quiz';
            $cat_objid = $qid;
            $cat_desc = substr($_REQUEST["description"], 0, 200);
            $cat_name = $_REQUEST["name"];
            $cat_href = "tiki-take_quiz.php?quizId=" . $cat_objid;
            include_once("categorize.php");
            $isEdit = $_REQUEST["quizId"] > 0;
            $successMessage = $isEdit ?
                tra("Quiz") . " '" . trim($_REQUEST["name"]) . "' " . tra("has been successfully updated.") :
                tra("Quiz") . " '" . trim($_REQUEST["name"]) . "' " . tra("has been successfully created.");
            Feedback::success($successMessage);
            if (! $isEdit) {
                $_REQUEST["quizId"] = 0;
                $smarty->assign('quizId', $_REQUEST["quizId"]);
                $quizId = 0;
                $form_data = [];
            } else {
                $redirect_url = $_SERVER['PHP_SELF'] . "?quizId=" . $_REQUEST["quizId"];
                if (isset($_REQUEST['cookietab'])) {
                    $redirect_url .= "&cookietab=" . $_REQUEST['cookietab'];
                }
                header("Location: " . $redirect_url);
                exit;
            }
        } catch (Exception $e) {
            Feedback::error(tra("An error occurred while saving the quiz: %0", $e->getMessage()));
            $keep_tab_active = true;
        }
    } else {
        foreach ($validation_errors as $error) {
            Feedback::error($error);
        }
        $keep_tab_active = true;
    }
} elseif ($_REQUEST["quizId"]) {
    $result = $quizlib->get_quiz($_REQUEST["quizId"]);
    if (! $result) {
        Feedback::error(tra("The quiz you are trying to edit was not found. Please verify the quiz ID or create a new one."));
    } else {
        $info = $result;
    }
    if (! isset($info["publishDate"])) {
        $info["publishDate"] = $tikilib->now;
    }
    if (! isset($info["expireDate"])) {
        $cur_time = explode(',', $tikilib->date_format('%Y,%m,%d,%H,%M,%S', $tikilib->now));
        $info["expireDate"] = $tikilib->make_time($cur_time[3], $cur_time[4], $cur_time[5], $cur_time[1], $cur_time[2], $cur_time[0] + 1);
    }
}

// Use form data if validation failed, otherwise use info from database
$displayData = array_merge($info, $form_data);
$smarty->assign('name', $displayData["name"]);
$smarty->assign('description', $displayData["description"]);
$smarty->assign('canRepeat', $displayData["canRepeat"]);
$smarty->assign('storeResults', $displayData["storeResults"]);
$smarty->assign('immediateFeedback', $displayData["immediateFeedback"]);
$smarty->assign('showAnswers', $displayData["showAnswers"]);
$smarty->assign('shuffleQuestions', $displayData["shuffleQuestions"]);
$smarty->assign('shuffleAnswers', $displayData["shuffleAnswers"]);
$smarty->assign('questionsPerPage', $displayData["questionsPerPage"]);
$smarty->assign('timeLimited', $displayData["timeLimited"]);
$smarty->assign('timeLimit', $displayData["timeLimit"]);
$smarty->assign('passingperct', $displayData["passingperct"]);
if (isset($_REQUEST["remove"]) && $access->checkCsrf()) {
    try {
        $quizToRemove = $quizlib->get_quiz($_REQUEST["remove"]);
        if ($quizToRemove) {
            $quizName = $quizToRemove['name'];
            $quizlib->remove_quiz($_REQUEST["remove"]);
            Feedback::success(tra("Quiz") . " '" . $quizName . "' " . tra("has been successfully deleted."));
        } else {
            Feedback::error(tra("The quiz you are trying to delete was not found."));
        }
    } catch (Exception $e) {
        Feedback::error(tra("An error occurred while deleting the quiz: %0", $e->getMessage()));
    }
    $_REQUEST["quizId"] = 0;
    $smarty->assign('quizId', 0);
}

// Handle sorting and pagination
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
$channels = $quizlib->list_quizzes($offset, $maxRecords, $sort_mode, $find);

$temp_max = count($channels["data"]);
for ($i = 0; $i < $temp_max; $i++) {
    if ($userlib->object_has_one_permission($channels["data"][$i]["quizId"], 'quiz')) {
        $channels["data"][$i]["individual"] = 'y';

        $channels["data"][$i]["individual_tiki_p_take_quiz"] =
            $userlib->object_has_permission($user, $channels["data"][$i]["quizId"], 'quiz', 'tiki_p_take_quiz') ? 'y' : 'n';
        $channels["data"][$i]["individual_tiki_p_view_quiz_stats"] =
            $userlib->object_has_permission($user, $channels["data"][$i]["quizId"], 'quiz', 'tiki_p_view_quiz_stats') ? 'y' : 'n';
        $channels["data"][$i]["individual_tiki_p_view_user_stats"] =
            $userlib->object_has_permission($user, $channels["data"][$i]["quizId"], 'quiz', 'tiki_p_view_user_stats') ? 'y' : 'n';
        if ($tiki_p_admin == 'y' || $userlib->object_has_permission($user, $channels["data"][$i]["quizId"], 'quiz', 'tiki_p_admin_quizzes')) {
            $channels["data"][$i]["individual_tiki_p_take_quiz"] = 'y';
            $channels["data"][$i]["individual_tiki_p_view_quiz_stats"] = 'y';
            $channels["data"][$i]["individual_tiki_p_admin_quizzes"] = 'y';
            $channels["data"][$i]["individual_tiki_p_view_user_stats"] = 'y';
        }
    } else {
        $channels["data"][$i]["individual"] = 'n';
    }
}

$smarty->assign_by_ref('pages_count', $channels["count"]);
$smarty->assign_by_ref('channels', $channels["data"]);

// Fill array with possible number of questions per page
$qpp = [ 1, 2, 3, 4 ];
for ($i = 5; $i < 50; $i += 5) {
    $qpp[] = $i;
}

$hrs = [];
for ($i = 0; $i < 10; $i++) {
    $hrs[] = $i;
}

$mins = [];
for ($i = 1; $i < 120; $i++) {
    $mins[] = $i;
}

$smarty->assign('qpp', $qpp);
$smarty->assign('hrs', $hrs);
$smarty->assign('mins', $mins);

// Handle categorization
$cat_type = 'quiz';
$cat_objid = $_REQUEST["quizId"];
include_once("categorize_list.php");

// Assign date variables
$smarty->assign('publishDate', $info['publishDate']);
$smarty->assign('publishDateSite', $info['publishDate']);
$smarty->assign('expireDate', $info['expireDate']);
$smarty->assign('expireDateSite', $info['expireDate']);

// Assign validation errors to template
$smarty->assign('validation_errors', $validation_errors);

// Preserve tab state if specified or if validation failed
if ($keep_tab_active && isset($_REQUEST['cookietab'])) {
    $smarty->assign('cookietab', $_REQUEST['cookietab']);
} elseif (isset($_REQUEST['cookietab']) && ! isset($_REQUEST["save"])) {
    // Keep tab active when not saving (normal page load)
    $smarty->assign('cookietab', $_REQUEST['cookietab']);
}
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

// Display the template
$smarty->assign('mid', 'tiki-edit_quiz.tpl');
$smarty->display("tiki.tpl");
