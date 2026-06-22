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
        'staticKeyFilters' => [
            'trackerId' => 'int',
        ],
    ],
];
require_once 'tiki-setup.php';
require_once 'lib/trackers/TrackerConfigHistory.php';
require_once 'lib/trackers/TrackerConfigHistoryRenderer.php';
use Tiki\Sections;
use Tiki\Lib\Trackers\TrackerConfigHistory;
use Tiki\Lib\Trackers\TrackerConfigHistoryRenderer;

$section = Sections::SECTION_TRACKERS;
Sections::setCurrentSection($section);

$trklib = TikiLib::lib('trk');

$access->check_feature('feature_trackers');

if (empty($_REQUEST['trackerId'])) {
    Feedback::errorAndDie(tra('No tracker indicated'), \Laminas\Http\Response::STATUS_CODE_409);
}

$trackerId = (int) $_REQUEST['trackerId'];

$tracker_info = $trklib->get_tracker($trackerId);
if (! $tracker_info) {
    Feedback::errorAndDie(tra('Tracker not found'), \Laminas\Http\Response::STATUS_CODE_404);
}
if ($opts = $trklib->get_tracker_options($trackerId)) {
    $tracker_info = array_merge($tracker_info, $opts);
}

// Access check: requires admin_trackers either globally or for this specific tracker
$admin_perm = $tiki_p_admin_trackers;
if ($tiki_p_admin_trackers != 'y') {
    $perms = $tikilib->get_perm_object($trackerId, 'tracker', $tracker_info);
    $admin_perm = $perms['tiki_p_admin_trackers'];
}
if ($admin_perm != 'y') {
    Feedback::errorAndDie(tra("You don't have permission to use this feature"), \Laminas\Http\Response::STATUS_CODE_401);
}

// 1. Permission source: 'object' (individual), 'category', or 'global'
$tracker_perms_obj = Perms::get('tracker', $trackerId);
$perms_from = $tracker_perms_obj->from() ?: 'global';

// Object-level group permissions (only populated when individual perms are set)
$object_permissions = [];
if ($perms_from === 'object') {
    foreach ($userlib->get_object_permissions($trackerId, 'tracker') as $row) {
        $object_permissions[$row['permName']][] = $row['groupName'];
    }
}

// All tracker-relevant permissions with human-readable labels
$tracker_perm_definitions = [
    'tiki_p_view_trackers'                => tra('View tracker list'),
    'tiki_p_admin_trackers'               => tra('Administer tracker'),
    'tiki_p_create_tracker_items'         => tra('Create items'),
    'tiki_p_modify_tracker_items'         => tra('Edit open items'),
    'tiki_p_modify_tracker_items_pending' => tra('Edit pending items'),
    'tiki_p_modify_tracker_items_closed'  => tra('Edit closed items'),
    'tiki_p_remove_tracker_items'         => tra('Delete open items'),
    'tiki_p_remove_tracker_items_pending' => tra('Delete pending items'),
    'tiki_p_remove_tracker_items_closed'  => tra('Delete closed items'),
    'tiki_p_comment_tracker_items'        => tra('Post comments on items'),
    'tiki_p_tracker_view_comments'        => tra('View comments on items'),
    'tiki_p_tracker_view_history'         => tra('View item history'),
    'tiki_p_export_tracker'               => tra('Export tracker'),
];

// 2. Ownership / writer special rules from tracker options
$special_rules = [
    [
        'key'   => 'userCanSeeOwn',
        'label' => tra('Creators can see their own items'),
        'value' => $tracker_info['userCanSeeOwn'] ?? 'n',
    ],
    [
        'key'   => 'groupCanSeeOwn',
        'label' => tra('Creator\'s group members can see the item'),
        'value' => $tracker_info['groupCanSeeOwn'] ?? 'n',
    ],
    [
        'key'   => 'writerCanModify',
        'label' => tra('Creators can edit their own items'),
        'value' => $tracker_info['writerCanModify'] ?? 'n',
    ],
    [
        'key'   => 'writerCanRemove',
        'label' => tra('Creators can delete their own items'),
        'value' => $tracker_info['writerCanRemove'] ?? 'n',
    ],
    [
        'key'   => 'writerGroupCanModify',
        'label' => tra('Creator\'s group can edit the item'),
        'value' => $tracker_info['writerGroupCanModify'] ?? 'n',
    ],
    [
        'key'   => 'writerGroupCanRemove',
        'label' => tra('Creator\'s group can delete the item'),
        'value' => $tracker_info['writerGroupCanRemove'] ?? 'n',
    ],
    [
        'key'   => 'adminOnlyViewEditItem',
        'label' => tra('Items accessible only via wiki pages (direct access restricted to admins)'),
        'value' => $tracker_info['adminOnlyViewEditItem'] ?? 'n',
    ],
];

// 3. Fields with access restrictions and fields that trigger local notifications
$all_fields_result = $trklib->list_tracker_fields($trackerId, 0, -1);
$restricted_fields  = [];
$notification_fields = [];

foreach ($all_fields_result['data'] as $field) {
    if (! empty($field['isHidden']) || ! empty($field['visibleBy']) || ! empty($field['editableBy'])) {
        $restricted_fields[] = $field;
    }

    if ($field['type'] === 'u') {
        $notify = (int) ($field['options_map']['notify'] ?? 0);
        if ($notify > 0) {
            $field['_notify_mode']    = $notify === 1 ? tra('Always (including self)') : tra('Always (excluding self)');
            $field['_notify_trigger'] = tra('Any item change');
            $notification_fields[] = $field;
        }
    } elseif ($field['type'] === 'm') {
        $triggers = [];
        if (! empty($field['options_map']['watchopen'])) {
            $triggers[] = tra('→ Open');
        }
        if (! empty($field['options_map']['watchpending'])) {
            $triggers[] = tra('→ Pending');
        }
        if (! empty($field['options_map']['watchclosed'])) {
            $triggers[] = tra('→ Closed');
        }
        if ($triggers) {
            $field['_notify_mode']    = tra('Status change');
            $field['_notify_trigger'] = implode(', ', $triggers);
            $notification_fields[] = $field;
        }
    }
}

// Comments section: pre-build the "additional rules" lists to avoid punctuation outside {tr} in template
$view_comment_additional_parts = [];
if (! empty($tracker_info['userCanSeeOwn']) && $tracker_info['userCanSeeOwn'] === 'y') {
    $view_comment_additional_parts[] = tra('item creator');
}
if (! empty($tracker_info['groupCanSeeOwn']) && $tracker_info['groupCanSeeOwn'] === 'y') {
    $view_comment_additional_parts[] = tra("creator's group member");
}
$view_comment_additional = implode(', ', $view_comment_additional_parts);

$post_comment_additional_parts = [];
if (! empty($tracker_info['userCanSeeOwn']) && $tracker_info['userCanSeeOwn'] === 'y') {
    $post_comment_additional_parts[] = tra('item creator');
}
if (! empty($tracker_info['writerCanModify']) && $tracker_info['writerCanModify'] === 'y') {
    $post_comment_additional_parts[] = tra('creator can modify');
}
if (! empty($tracker_info['writerGroupCanModify']) && $tracker_info['writerGroupCanModify'] === 'y') {
    $post_comment_additional_parts[] = tra("creator's group can modify");
}
$post_comment_additional = implode(', ', $post_comment_additional_parts);

// 4. Notification watchers: user-level and group-level watches on this tracker
$user_watches = $tikilib->fetchAll(
    "SELECT `watchId`, `user`, `email`
     FROM `tiki_user_watches`
     WHERE `event` = 'tracker_modified' AND `object` = ?",
    [$trackerId]
);

$group_watches = $tikilib->fetchAll(
    "SELECT `watchId`, `group`
     FROM `tiki_group_watches`
     WHERE `event` = 'tracker_modified' AND `object` = ?",
    [$trackerId]
);

$outbound_emails = [];
if (! empty($tracker_info['outboundEmail'])) {
    $outbound_emails = array_values(array_filter(array_map('trim', explode(',', $tracker_info['outboundEmail']))));
}

// 5. Configuration history — property-level diffs via TrackerConfigHistory (item-161718)
$currentData = TrackerConfigHistory::extractTrackerState($tracker_info, $trackerId);
$raw_history  = TrackerConfigHistory::getHistory('tracker', $trackerId, $currentData, 5);
$config_history       = TrackerConfigHistoryRenderer::renderRows($raw_history);
$config_history_total = TrackerConfigHistory::countHistory('tracker', $trackerId);
$config_history_full_url = 'index.php?controller=tracker&action=config_history&trackerId=' . $trackerId;

$smarty->assign('tracker_info', $tracker_info);
$smarty->assign('trackerId', $trackerId);
$smarty->assign('perms_from', $perms_from);
$smarty->assign('object_permissions', $object_permissions);
$smarty->assign('tracker_perm_definitions', $tracker_perm_definitions);
$smarty->assign('special_rules', $special_rules);
$smarty->assign('restricted_fields', $restricted_fields);
$smarty->assign('notification_fields', $notification_fields);
$smarty->assign('view_comment_additional', $view_comment_additional);
$smarty->assign('post_comment_additional', $post_comment_additional);
$smarty->assign('user_watches', $user_watches);
$smarty->assign('group_watches', $group_watches);
$smarty->assign('outbound_emails', $outbound_emails);
$smarty->assign('config_history', $config_history);
$smarty->assign('config_history_total', $config_history_total);
$smarty->assign('config_history_full_url', $config_history_full_url);

$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->display('tiki-tracker_overview.tpl');
