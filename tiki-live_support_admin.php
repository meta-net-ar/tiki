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
        'staticKeyFilters'     => [
        'adduser'              => 'bool',               //post
        'user'                 => 'username',           //post
        'offline'              => 'bool',               //post
        'removeuser'           => 'username',           //post
        'find_users'           => 'string',             //post
        'show_html'            => 'alpha',              //get
        ],
    ],
];
require_once('tiki-setup.php');
include_once('lib/live_support/lsadminlib.php');
include_once('lib/live_support/lslib.php');
$access->check_feature('feature_live_support');
if ($tiki_p_live_support_admin != 'y' && ! $lsadminlib->is_operator($user)) {
    Feedback::errorAndDie(tr("You do not have the permission that is needed to use this feature"), 401);
}
$smarty->assign('html', false);
if (isset($_REQUEST['show_html'])) {
    $html = '<a href="#" onclick=\'javascript:window.open("tiki-live_support_client.php","","menubar=,scrollbars=yes,resizable=yes,height=450,width=300");\'><img src="tiki-live_support_server.php?operators_online" alt="image" /></a>';
    $smarty->assign('html', $html);
}
if ($tiki_p_live_support_admin == 'y') {
    if (isset($_REQUEST['adduser'])) {
        if (isset($_REQUEST['user'])) {
            $access->checkCsrf();
            $lsadminlib->add_operator($_REQUEST['user']);
        } else {
            Feedback::error(tr('No Tiki User Selected'));
        }
    }
    if (isset($_REQUEST['offline'])) {
        $access->checkCsrf();
        $lslib->set_operator_status($_REQUEST['offline'], 'offline');
    }
    if (isset($_REQUEST['removeuser']) && $access->checkCsrf()) {
        $lsadminlib->remove_operator($_REQUEST['removeuser']);
    }
}
// Get the list of operators
$online_operators = $lsadminlib->get_operators('online');
$offline_operators = $lsadminlib->get_operators('offline');
$smarty->assign_by_ref('online_operators', $online_operators);
$smarty->assign_by_ref('offline_operators', $offline_operators);
// Get the list of users
if (! isset($_REQUEST['find_users'])) {
    $_REQUEST['find_users'] = '';
}
$users = $userlib->get_users(0, -1, 'login_asc', $_REQUEST['find_users']);
$ok_users = [];
$temp_max = count($users['data']);
for ($i = 0; $i < $temp_max; $i++) {
    foreach ($online_operators as $op) {
        if ($op['user'] == $users['data'][$i]['user']) {
            unset($users[$i]);
        }
    }
    foreach ($offline_operators as $op) {
        if (isset($users['data'][$i]) && $op['user'] == $users['data'][$i]['user']) {
            unset($users['data'][$i]);
        }
    }
    if (isset($users['data'][$i])) {
        $ok_users[] = $users['data'][$i];
    }
}
$smarty->assign_by_ref('users', $ok_users);
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('mid', 'tiki-live_support_admin.tpl');
$smarty->display("tiki.tpl");
