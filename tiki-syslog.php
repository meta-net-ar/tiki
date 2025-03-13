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
        'staticKeyFilters'   => [
        'clean'              => 'bool',              //post
        'months'             => 'int',               //post
        'sort_mode'          => 'word',              //get
        'offset'             => 'int',               //get
        'max'                => 'int',               //get
        'find'               => 'string',            //post
        ],
    ],
];
require_once('tiki-setup.php');

$access->check_permission('tiki_p_admin');

$auto_query_args = ['offset', 'numrows', 'maxRecords', 'find', 'sort_mode'];
if (isset($_POST["actionId"]) && ! empty($_POST["page"])) {
    $prefslib = TikiLib::lib('prefs');
    $adminPage = $_POST["page"];
    $logResult = $logslib->get_info_action($_POST["actionId"]);
    if (! empty($logResult['log']) && ! empty($logResult['object'])) {
        $access->checkCsrf();

        $logObject = $logResult['object'];
        $_POST['pp'] = $logObject;
        $revertInfo = unserialize($logResult['log']);
        if (! isset($revertInfo['reverted'])) {
            $_POST["revertInfo"] = $revertInfo;
            $logslib->revert_action($_POST["actionId"], $logObject, $adminPage, $revertInfo);
            if (file_exists("admin/include_$adminPage.php")) {
                include_once("admin/include_$adminPage.php");
            }
            if (! empty($revertedActions)) {
                Feedback::note(['mes' => $revertedActions, 'title' => tra('The following list of changes has been reverted:')]);
            }
        } else {
            Feedback::error(['mes' => tr('Log already reverted')]);
        }
    } else {
        Feedback::error(['mes' => tr('Invalid System Log ID')]);
    }
}

if (isset($_REQUEST["clean"])) {
    $access->checkCsrf();
    $date = strtotime("-" . $_REQUEST["months"] . " months");
    $clearedLogs = $logslib->clean_logs($date);

    if ($clearedLogs->numrows > 0) {
        Feedback::success(['mes' => tr('%0 logs have been cleared.', $clearedLogs->numrows)]);
    }
}

if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'actionid_desc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$smarty->assign_by_ref('sort_mode', $sort_mode);
if (isset($_REQUEST["find"])) {
    $find = $_REQUEST["find"];
} else {
    $find = '';
}
$smarty->assign('find', $find);
if (! isset($_REQUEST["offset"])) {
    $offset = 0;
} else {
    $offset = $_REQUEST["offset"];
}
$smarty->assign_by_ref('offset', $offset);
if (isset($_REQUEST["max"])) {
    $maxRecords = $_REQUEST["max"];
}
$smarty->assign_by_ref('maxRecords', $maxRecords);

$list = $logslib->list_logs('', '', $offset, $maxRecords, $sort_mode, $find);
foreach ($list['data'] as &$row) {
    if (! empty($row['log'])) {
        $row['log_pretty'] = print_r(unserialize($row['log']), true);
    }
}
$smarty->assign_by_ref('cant', $list['cant']);
$smarty->assign('list', $list['data']);
$smarty->assign('api_tiki', $api_tiki);
$urlquery['sort_mode'] = $sort_mode;
$urlquery['find'] = $find;
$smarty->assign_by_ref('urlquery', $urlquery);
$smarty->assign('mid', 'tiki-syslog.tpl');
$smarty->display('tiki.tpl');
