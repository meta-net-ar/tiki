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
        'sort_mode'                => 'word',               //post
        'offset'                   => 'int',                //get
        'find'                     => 'string',             //post
        ],
    ],
];
$section = 'quizzes';
require_once('tiki-setup.php');
$quizlib = TikiLib::lib('quiz');
$auto_query_args = ['sort_mode', 'offset', 'find'];
$access->check_feature('feature_quizzes');
$access->check_permission('tiki_p_take_quiz');
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
Perms::bulk([ 'type' => 'quiz' ], 'object', $channels['data'], 'quizId');
$temp_max = count($channels["data"]);
for ($i = 0; $i < $temp_max; $i++) {
    $quizperms = Perms::get([ 'type' => 'quiz', 'object' => $channels['data'][$i]['quizId'] ]);
    $channels["data"][$i]["individual_tiki_p_take_quiz"] = $quizperms->take_quiz ? 'y' : 'n';
    $channels["data"][$i]["individual_tiki_p_view_quiz_stats"] = $quizperms->view_quiz_stats ? 'y' : 'n';
    $channels["data"][$i]["individual_tiki_p_view_user_stats"] = $quizperms->view_user_stats ? 'y' : 'n';
    $channels["data"][$i]["individual_tiki_p_admin_quizzes"] = $quizperms->admin_quizzes ? 'y' : 'n';
}
$smarty->assign_by_ref('pages_count', $channels["count"]);
$smarty->assign_by_ref('channels', $channels["data"]);
include_once('tiki-section_options.php');
// Display the template
$smarty->assign('mid', 'tiki-list_quizzes.tpl');
$smarty->display("tiki.tpl");
