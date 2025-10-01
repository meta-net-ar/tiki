<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [ 'staticKeyFilters' =>
        [
            'clean' => 'striptags',
            'offset' => 'digits',
            'numrows' => 'digits',
            'maxRecords' => 'digits',
            'find' => 'striptags',
            'sort_mode' => 'striptags',
        ]
    ],
];

include_once('tiki-setup.php');

$access->check_permission('tiki_p_admin');

$query = "show tables like 'tiki_sql_query_logs'";
$result = $tikilib->query($query, []);
if (! $result->numRows()) {
    Feedback::errorAndDie(tra('This feature is disabled') . ': log_sql', \Laminas\Http\Response::STATUS_CODE_401);
}
// let look at the log even if not active for older logs
//if ($prefs['log_sql'] != 'y') {
//  $smarty->assign('msg', tra('This feature is disabled').': log_sql');
//  $smarty->display('error.tpl');
//  die;
//}
if (isset($_REQUEST['clean'])) {
    $access->checkCsrf(tra('Clean the sql logs'));
    $logslib->clean_logsql();
}
$auto_query_args = ['offset', 'numrows', 'find', 'sort_mode'];
$numrows = $_REQUEST['numrows'] ?? $_REQUEST['maxRecords'] ?? $prefs['maxRecords'];
$smarty->assign_by_ref('numrows', $numrows);
$smarty->assign_by_ref('maxRecords', $numrows);
$offset = (isset($_REQUEST['offset'])) ? $_REQUEST['offset'] : 0;
$smarty->assign_by_ref('offset', $offset);
$sort_mode = (isset($_REQUEST['sort_mode'])) ? $_REQUEST['sort_mode'] : 'executed_at_desc';
$smarty->assign_by_ref('sort_mode', $sort_mode);
$find = (isset($_REQUEST['find'])) ? $_REQUEST['find'] : '';
$smarty->assign_by_ref('find', $find);
$logs = $logslib->list_logsql($sort_mode, $offset, $numrows, $find);
$smarty->assign_by_ref('logs', $logs['data']);
$smarty->assign_by_ref('count', $logs['count']);
$smarty->assign('mid', 'tiki-sqllog.tpl');
$smarty->display('tiki.tpl');
