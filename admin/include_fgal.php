<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// This script may only be included - so its better to die if called directly.
if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}

$filegallib = TikiLib::lib('filegal');

if (isset($_REQUEST['fgal_use_dir'])) {
    // Check for last character being a / or a \
    if (! str_ends_with($_REQUEST["fgal_use_dir"], "\\") && ! str_ends_with($_REQUEST["fgal_use_dir"], "/") && $_REQUEST["fgal_use_dir"] != "") {
        $_REQUEST["fgal_use_dir"] .= "/";
    }
    $filegallib->setupDirectory($_REQUEST["fgal_use_dir"]);
}
// Check for last character being a / or a \
if (
    isset($_REQUEST["fgal_podcast_dir"]) &&
    ! str_ends_with($_REQUEST["fgal_podcast_dir"], "\\") &&
    ! str_ends_with($_REQUEST["fgal_podcast_dir"], "/") &&
    $_REQUEST["fgal_podcast_dir"] != ""
) {
    $_REQUEST["fgal_podcast_dir"] .= "/";
}
if (
    isset($_REQUEST["fgal_batch_dir"]) &&
    ! str_ends_with($_REQUEST["fgal_batch_dir"], "\\") &&
    ! str_ends_with($_REQUEST["fgal_batch_dir"], "/") &&
    $_REQUEST["fgal_batch_dir"] != ""
) {
    $_REQUEST["fgal_batch_dir"] .= "/";
}
simple_set_value("fgal_use_dir");
simple_set_value("fgal_podcast_dir");
simple_set_value("fgal_batch_dir");
if (! empty($_REQUEST['fgal_quota']) && ! empty($_REQUEST['fgal_quota_default']) && $_REQUEST['fgal_quota_default'] > $_REQUEST['fgal_quota']) {
    $_REQUEST['fgal_quota_default'] = $_REQUEST['fgal_quota'];
}
simple_set_value('fgal_quota_default');
if (! empty($_REQUEST['updateMime'])) {
    $files = $filegallib->table('tiki_files');
    $rows = $files->fetchAll(['fileId', 'filename', 'filetype'], ['archiveId' => 0, 'filetype' => 'application/octet-stream']);
    foreach ($rows as $row) {
        $t = $filegallib->fixMime($row['filetype'], $row['filename']);
        if ($t != 'application/octet-stream') {
            $files->update(['filetype' => $t], ['fileId' => $row['fileId']]);
        }
    }
}

if (! empty($_POST['move']) && $access->checkCsrf()) {
    if ($_POST['move'] == 'to_fs') {
        if (empty($prefs['fgal_use_dir'])) {
            $errors[] = tra('You must specify a directory');
        } else {
            $feedbacks = [];
            $errors = $filegallib->moveFiles($_POST['move'], $feedbacks);
        }
    } elseif ($_POST['move'] == 'to_db') {
        $feedbacks = [];
        $errors = $filegallib->moveFiles($_REQUEST['move'], $feedbacks);
    }
    if (! empty($errors)) {
        Feedback::error(['mes' => $errors]);
    }
    if (! empty($feedbacks)) {
        Feedback::note(['mes' => $feedbacks]);
    }
}

if (! empty($_POST['mimes']) && $access->checkCsrf()) {
    $mimes = $_POST['mimes'];
    foreach ($mimes as $mime => $cmd) {
        $mime = trim($mime);
        if (empty($cmd)) {
            $filegallib->delete_file_handler($mime);
        } elseif (! $filegallib->change_file_handler($mime, $cmd)) {
            Feedback::error(tr('Invalid file handler command for MIME type "%0". Only known extractor binaries are allowed, and shell metacharacters are not permitted.', $mime));
        }
    }
}

if (! empty($_POST['newMime']) && ! empty($_POST['newCmd']) && $access->checkCsrf()) {
    if (! $filegallib->change_file_handler($_POST['newMime'], $_POST['newCmd'])) {
        Feedback::error(tr('Invalid file handler command for MIME type "%0". Only known extractor binaries are allowed, and shell metacharacters are not permitted.', $_POST['newMime']));
    }
}

if (isset($_REQUEST["filegalfixvndmsfiles"]) && $access->checkCsrf()) {
    $filegallib->fix_vnd_ms_files();
}

//*** end state-changing actions

if (isset($_REQUEST["filegalredosearch"]) && $access->checkCsrf()) {
    $searchTextReindexedFilesAmount = $filegallib->reindex_all_files_for_search_text();
    Feedback::success(tr("The search text was reindexed for a total of %0 files.", $searchTextReindexedFilesAmount));
}

$ocr = TikiLib::lib('ocr');
if (isset($_POST["ocrstalledreset"])) {
    $ocrCount = $ocr->releaseAllStalled();
    if ($ocrCount) {
        Feedback::success($ocrCount . tra(' stalled files will attempt to OCR again.'));
    } else {
        Feedback::error(tra("Was not able to change status of OCR files from stalled to pending."));
    }
}

// set the number of stalled OCR files
$ocrStalled = $ocr->table('tiki_files')->fetchCount(['ocr_state' => $ocr::OCR_STATUS_STALLED]);
$smarty->assign('ocrStalled', $ocrStalled);

$usedSize = $filegallib->getUsedSize();
$smarty->assign_by_ref('usedSize', $usedSize);

$handlers = $filegallib->get_file_handlers();
ksort($handlers);
$smarty->assign("fgal_handlers", $handlers);
$usedTypes = $filegallib->getFiletype();
$missingHandlers = [];
$vnd_ms_files_exist = false;

// check if files can be indexed, or if they will be indexed via an OCR process
$ocr->setMimeTypes();
foreach ($usedTypes as $type) {
    if (! $filegallib->get_parse_app($type, true) && ! in_array($type, $ocr->ocrMime)) {
        $missingHandlers[] = $type;
        if (str_contains($type, '/vnd.ms-')) {
            $vnd_ms_files_exist = true;
        }
    }
}

$smarty->assign_by_ref('missingHandlers', $missingHandlers);
$smarty->assign('vnd_ms_files_exist', $vnd_ms_files_exist);
include_once('fgal_listing_conf.php');
