<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\SortModeValidator;

$inputConfiguration = [
    [
        'staticKeyFilters' => [
            'offset'       => 'int',                //get
            'login'        => 'username',           //post
            'add'          => 'alpha',              //post
            'maxRecords'   => 'int',                //get
            'removeevent'  => 'digits',             //get
            'removetype'   => 'word',               //get
            'sort_mode'    => 'alnumdash',          //get
            'find'         => 'text',               //get
            'email'        => 'email',              //post
            'event'        => 'text',               //post
            'action'       => 'alpha',              //post
        ],
        'staticKeyFiltersForArrays' => [
            'checked'      => 'text',               //post
        ],
    ]
];
// Initialization
require_once('tiki-setup.php');

use Tiki\Sections;

$section = Sections::SECTION_ADMIN_LAYOUT;
Sections::setCurrentSection($section);
$access->check_permission(['tiki_p_admin_notifications']);

$notificationlib = TikiLib::lib('notification');

$auto_query_args = [
    'offset',
    'sort_mode',
    'find',
    'maxRecords'
];
$watches = $notificationlib->get_global_watch_types();
$save = true;
$login = '';
if (isset($_REQUEST["add"]) && $access->checkCsrf()) {
    if (! empty($_REQUEST['login'])) {
        if ($userlib->user_exists($_REQUEST['login'])) {
            $login = $_REQUEST['login'];
        } else {
            Feedback::error(tra('Invalid username'));
            $save = false;
        }
    } elseif (! empty($_REQUEST['email'])) {
        if (validate_email($_REQUEST['email'], $prefs['validateEmail'])) {
            $email = $_REQUEST['email'];
        } else {
            Feedback::error(tra('Invalid email'));
            $save = false;
        }
    } else {
        Feedback::error(tra('You need to provide a username or an email'));
        $save = false;
    }
    if ($save and isset($_REQUEST['event']) and isset($watches[$_REQUEST['event']])) {
        $result = $tikilib->add_user_watch(
            $login,
            $_REQUEST["event"],
            $watches[$_REQUEST['event']]['object'],
            $watches[$_REQUEST['event']]['type'],
            $watches[$_REQUEST['event']]['label'],
            $watches[$_REQUEST['event']]['url'],
            $email ?? null
        );
        if (! $result) {
            Feedback::error(tra('The user has no email set. No notifications will be sent.'));
        } else {
            Feedback::success(tr('Mail notification event added'));
        }
    }
}

if (isset($_REQUEST["removeevent"]) && isset($_REQUEST['removetype']) && $access->checkCsrf(true)) {
    if ($_REQUEST['removetype'] == 'user') {
        $result = $tikilib->remove_user_watch_by_id($_REQUEST["removeevent"]);
    } else {
        $result = $tikilib->remove_group_watch_by_id($_REQUEST["removeevent"]);
    }
    if ($result && $result->numRows()) {
        Feedback::success(tr('Mail notification event deleted'));
    } else {
        Feedback::error('Mail notification event not deleted');
    }
}
if (
    isset($_REQUEST['action'])
    && $_REQUEST['action'] == 'delete'
    && isset($_REQUEST['checked'])
    && $access->checkCsrf(true)
) {
    $i = 0;
    foreach ($_REQUEST['checked'] as $id) {
        if (str_starts_with($id, 'user')) {
            $result = $tikilib->remove_user_watch_by_id(substr($id, 4));
        } else {
            $result = $tikilib->remove_group_watch_by_id(substr($id, 5));
        }
        if ($result && $result->numRows()) {
            $i++;
        }
    }
    $checkedCount = count($_REQUEST['checked']);
    if ($checkedCount == $i) {
        $msg = $i == 1 ? tr('One mail notification events deleted') : tr('%0 mail notifications events deleted', $i);
        Feedback::success(tr($msg));
    } elseif ($i < $checkedCount) {
        Feedback::error(tr('%0 of %1 selected mail notification events deleted', $i, $checkedCount));
    }
}
$sort_mode = SortModeValidator::validateSortModeOrFeedback(
    $_REQUEST['sort_mode'] ?? 'event_asc',
    ['tiki_user_watches', 'tiki_group_watches']
);
$offset = $_REQUEST["offset"] ?? 0;
$smarty->assign_by_ref('offset', $offset);
$find = $_REQUEST["find"] ?? '';
$smarty->assign_by_ref('find', $find);
if (! empty($_REQUEST['maxRecords'])) {
    $maxRecords = $_REQUEST['maxRecords'];
}
$smarty->assign_by_ref('watches', $watches);
$smarty->assign_by_ref('maxRecords', $maxRecords);
$smarty->assign_by_ref('sort_mode', $sort_mode);
$channels = $tikilib->list_watches($offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('count', $channels['count']);
$smarty->assign_by_ref('channels', $channels["data"]);
if ($prefs['feature_trackers'] == 'y') {
    $trklib = TikiLib::lib('trk');
    $trackers = $trklib->get_trackers_options(0, 'outboundemail', $find, 'empty');
    $smarty->assign_by_ref('trackers', $trackers);
}
if ($prefs['feature_forums'] == 'y') {
    $commentslib = TikiLib::lib('comments');
    $forums = $commentslib->get_outbound_emails();
    $smarty->assign_by_ref('forums', $forums);
}

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

// Display the template
$smarty->assign('mid', 'tiki-admin_notifications.tpl');
$smarty->display("tiki.tpl");
