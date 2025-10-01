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
        'staticKeyFilters'    => [
            'name'            => 'striptags',  //post
            'dsn'             => 'striptags',  //post
            'dsnId'           => 'int',        //post
            'remove'          => 'int',        //post
            'sort_mode'       => 'striptags',  //get
            'offset'          => 'int',        //get
            'find'            => 'striptags',  //post
            'save'            => 'bool',       //post
        ],
    ],
];

require_once('tiki-setup.php');
$adminlib = TikiLib::lib('admin');

$access->check_permission('tiki_p_admin');

if (! isset($_REQUEST["dsnId"])) {
    $_REQUEST["dsnId"] = 0;
}
$smarty->assign('dsnId', $_REQUEST["dsnId"]);
if ($_REQUEST["dsnId"]) {
    $info = $adminlib->get_dsn($_REQUEST["dsnId"]);
} else {
    $info = [];
    $info["dsn"] = '';
    $info['name'] = '';
}
$smarty->assign('info', $info);
if (isset($_REQUEST["remove"]) && $access->checkCsrf(true)) {
    $result = $adminlib->remove_dsn($_REQUEST["remove"]);
    if ($result && $result->numRows()) {
        Feedback::success(tr('DSN removed'));
    } else {
        Feedback::error(tr('DSN not removed'));
    }
}
if (isset($_REQUEST["save"]) && $access->checkCsrf()) {
    if (empty(trim($_REQUEST["name"])) || empty(trim($_REQUEST["dsn"]))) {
        Feedback::error(tr('Both DSN and name fields are required.'));
    } else {
        $result = $adminlib->replace_dsn($_REQUEST["dsnId"], $_REQUEST["dsn"], $_REQUEST['name']);
        if ($result && $result->numRows()) {
            Feedback::success(tr('DSN created or modified'));
        } else {
            Feedback::error(tr('DSN not created or modified'));
        }
    }
    $info = [];
    $info["dsn"] = '';
    $info['name'] = '';
    $smarty->assign('info', $info);
    $smarty->assign('name', '');
    $smarty->assign('dsnId', '');
}
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'dsnId_desc';
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
$channels = $adminlib->list_dsn($offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('pages_count', $channels["count"]);
$smarty->assign_by_ref('channels', $channels["data"]);
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('mid', 'tiki-admin_dsn.tpl');
$smarty->display("tiki.tpl");
