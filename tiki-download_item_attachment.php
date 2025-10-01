<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$force_no_compression = true;
require_once('tiki-setup.php');

$trklib = TikiLib::lib('trk');

if (empty($_REQUEST['attId']) && ! empty($_REQUEST['itemId']) && ! empty($_REQUEST['fieldId'])) {
    $_REQUEST['attId'] = $trklib->get_item_value(0, $_REQUEST['itemId'], $_REQUEST['fieldId']);
}

if (empty($_REQUEST['attId'])) {
    Feedback::errorAndDie(tra('Incorrect param'), \Laminas\Http\Response::STATUS_CODE_409);
}

$info = $trklib->get_item_attachment($_REQUEST['attId']);
if (empty($info)) {
    Feedback::errorAndDie(tra('Incorrect param'), \Laminas\Http\Response::STATUS_CODE_409);
}
$itemInfo = $trklib->get_tracker_item($info["itemId"]);
$itemUsers = $trklib->get_item_creators($itemInfo['trackerId'], $itemInfo['itemId']);
$itemPerms = Perms::get(['type' => 'trackeritem', 'object' => $info['itemId']]);
$globalperms = Perms::get();

if (isset($info['user']) && $info['user'] == $user) {
} elseif (! empty($itemUsers) && in_array($user, $itemUsers)) {
} elseif (
    (isset($itemInfo['status']) and $itemInfo['status'] == 'p' && ! $itemPerms->view_trackers_pending)
    ||  (isset($itemInfo['status']) and $itemInfo['status'] == 'c' && ! $itemPerms->view_trackers_closed)
    ||  (! $globalperms->admin_trackers && ! $itemPerms->view_trackers)
    ||  (! $globalperms->admin_trackers && ! $itemPerms->tracker_view_attachments)
) {
    Feedback::errorAndDie(tra('Permission denied'), \Laminas\Http\Response::STATUS_CODE_401);
}

$trklib->add_item_attachment_hit($_REQUEST["attId"]);

if (empty($info['filetype']) || $info['filetype'] == 'application/x-octetstream' || $info['filetype'] == 'application/octet-stream') {
    $mimelib = TikiLib::lib('mime');
    $info['filetype'] = $mimelib->from_filename($info['filename']);
}
$type = $info["filetype"];
$file = $info["filename"];
$content = $info["data"];

session_write_close();
TikiLib::lib('header')->setXRobotsTag($robots);
header("Content-type: $type");
if (isset($_REQUEST["display"])) {
    header("Content-Disposition: inline; filename=\"" . urlencode($file) . "\"");
} else {
    header("Content-Disposition: attachment; filename=\"$file\"");
}
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Pragma: public");

if ($info["path"]) {
    if (! file_exists($prefs['t_use_dir'] . $info["path"])) {
        $str = sprintf(tra("Error : The file %s doesn't exist."), $_REQUEST["attId"]) . tra("Please contact the website administrator.");
         header("Content-Length: " . strlen($str));
        echo $str;
    } else {
        header("Content-Length: " . filesize($prefs['t_use_dir'] . $info["path"]));
        readfile($prefs['t_use_dir'] . $info["path"]);
    }
} else {
    header("Content-Length: " . $info[ "filesize" ]);
    echo "$content";
}
