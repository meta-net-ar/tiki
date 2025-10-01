<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}

$trklib = TikiLib::lib('trk');

$find = '';
$offset = 0;
$sort_mode = 'created_desc';

//*** begin state-changing actions
// TODO avoid altering $_POST variable directly
if (isset($_POST['trkset']) && $access->checkCsrf()) {
    $tikilib->set_preference('t_use_db', $_POST['t_use_db']);
    if (! str_ends_with($_POST['t_use_dir'], '\\') && ! str_ends_with($_POST['t_use_dir'], '/') && $_POST['t_use_dir'] != '') {
        $_POST['t_use_dir'] .= '/';
    }
    $tikilib->set_preference('t_use_dir', $_POST['t_use_dir']);
}

if (isset($_POST['action']) && isset($_POST['attId']) && $access->checkCsrf()) {
    $item = $trklib->get_item_attachment($_POST['attId']);
    if ($_POST['action'] == 'move2db') {
        $trklib->file_to_db($prefs['t_use_dir'] . $item['path'], $_POST['attId']);
    } elseif ($_POST['action'] == 'move2file') {
        $trklib->db_to_file($prefs['t_use_dir'] . md5($item['filename']), $_POST['attId']);
    }
}

if (isset($_POST['all2db']) && $access->checkCsrf()) {
    $attachments = $trklib->list_all_attachments();
    for ($i = 0; $i < $attachments['count']; $i++) {
        if ($attachments['data'][$i]['path']) {
            $trklib->file_to_db($prefs['t_use_dir'] . $attachments['data'][$i]['path'], $attachments['data'][$i]['attId']);
        }
    }
} elseif (isset($_POST['all2file']) && $access->checkCsrf()) {
    $attachments = $trklib->list_all_attachments();
    for ($i = 0; $i < $attachments['count']; $i++) {
        if (! $attachments['data'][$i]['path']) {
            $trklib->db_to_file($prefs['t_use_dir'] . md5($attachments['data'][$i]['filename']), $attachments['data'][$i]['attId']);
        }
    }
}

if (! empty($_POST['createRelationsTracker'])) {
    $creator = new Tiki\Relation\SystemTrackerCreator();
    if ($creator->createRelationshipTracker($_POST['relationshipTrackerType'])) {
        Feedback::success(tr('Relationship metadata system tracker created.'));
    } else {
        Feedback::error(tr('Relationship type cannot be empty.'));
    }
}
//*** end state-changing actions

if (! empty($_REQUEST['find'])) {
    $find = $_REQUEST['find'];
}
if (! empty($_REQUEST['offset'])) {
    $offset = $_REQUEST['offset'];
}
if (! empty($_REQUEST['sort_mode'])) {
    $sort_mode = $_REQUEST['sort_mode'];
}

$smarty->assign_by_ref('find', $find);
$smarty->assign_by_ref('offset', $offset);
$smarty->assign_by_ref('sort_mode', $sort_mode);

$attachments = $trklib->list_all_attachments($offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('pages_count', $attachments['count']);
$headerlib->add_cssfile('themes/base_files/feature_css/admin.css');
$smarty->assign_by_ref('attachments', $attachments['data']);

$fieldPreferences = [];

foreach (Tracker_Field_Factory::getFieldTypes() as $type) {
    $fieldPreferences[] = array_shift($type['prefs']);
}

$smarty->assign('fieldPreferences', $fieldPreferences);
$smarty->assign('relationshipBehaviourList', array_keys(Tiki\Relation\Semantics::BEHAVIOUR_LIST));
