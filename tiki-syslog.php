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
        'clean'              => 'bool',              //get
        'months'             => 'int',               //get
        'sort_mode'          => 'alnumdash',         //get
        'offset'             => 'int',               //get
        'max'                => 'int',               //get
        'find'               => 'text',              //post
        'actionId'           => 'int',               //post
        'page'               => 'alnumdash',         //post
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
    // Check if months is valid
    if (! isset($_REQUEST["months"]) || ! is_numeric($_REQUEST["months"]) || intval($_REQUEST["months"]) < 1) {
        Feedback::error(['mes' => tr('You must enter a valid number of months (positive integer).')]);
    } else {
        $months = intval($_REQUEST["months"]);

        $smarty->assign('confirmation_text', tr('Please confirm action'));
        $smarty->assign('confirm_detail', tr('You are about to delete all logs older than %0 months. This action cannot be undone.', $months));
        $access->checkCsrf();

        $date = strtotime("-" . $months . " months");
        $clearedLogs = $logslib->clean_logs($date);

        if ($clearedLogs->numrows > 0) {
            Feedback::success(['mes' => tr('%0 logs have been cleared.', $clearedLogs->numrows)]);
        } else {
            Feedback::note(['mes' => tr('No logs older than %0 months were found.', $months)]);
        }
    }
}

if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'actionid_desc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$smarty->assign_by_ref('sort_mode', $sort_mode);
$find = $_REQUEST["find"] ?? '';
$smarty->assign('find', $find);
$offset = $_REQUEST["offset"] ?? 0;
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
$smarty->assign_by_ref('count', $list['count']);
$smarty->assign('list', $list['data']);
$urlquery['sort_mode'] = $sort_mode;
$urlquery['find'] = $find;
$smarty->assign_by_ref('urlquery', $urlquery);
$smarty->assign('mid', 'tiki-syslog.tpl');
$smarty->display('tiki.tpl');
