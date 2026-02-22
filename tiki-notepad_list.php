<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'     => [
        'merge'                => 'bool',        //get
        'delete'               => 'bool',        //post
        'merge_name'           => 'text',        //post
        'sort_mode'            => 'alnumdash',   //post
        'offset'               => 'int',         //post
        'find'                 => 'text',        //post
        ],
        'staticKeyFiltersForArrays' => [
            'note' => 'int',
        ],
    ],
];

require_once('tiki-setup.php');
include_once('lib/notepad/notepadlib.php');
include_once('lib/userfiles/userfileslib.php');
use Tiki\Sections;
$section = Sections::SECTION_MY_TIKI;
Sections::setCurrentSection($section);
$access->check_feature('feature_notepad');
$access->check_user($user);
$access->check_permission('tiki_p_notepad');

// Process file upload
if (isset($_FILES['userfile1'])) {
    $uploadedFile = $_FILES['userfile1'];

    if (! is_uploaded_file($uploadedFile['tmp_name'])) {
        Feedback::error($tikilib->uploaded_file_error($uploadedFile['error']));
        // Continue to display the page with the error message
    } else {
        $access->checkCsrf();

        $filegallib = TikiLib::lib('filegal');
        try {
            $filegallib->assertUploadedFileIsSafe($uploadedFile['tmp_name'], $uploadedFile['name']);
        } catch (Exception $e) {
            Feedback::errorAndDie($e->getMessage(), \Laminas\Http\Response::STATUS_CODE_403);
        }

        $maxNoteSize = 1000000; // 1 MB
        $data = file_get_contents($uploadedFile['tmp_name']);
        if (strlen($data) > $maxNoteSize) {
            Feedback::errorAndDie(tra("The file is too large"), \Laminas\Http\Response::STATUS_CODE_409);
        }

        $notepadlib->replace_note($user, 0, $uploadedFile['name'], $data);
    }
}

if (isset($_REQUEST["merge"])) {
    $access->checkCsrf();
    $merge = '';
    $first = true;
    if (! isset($_REQUEST["note"])) {
        Feedback::errorAndDie(tra("No item indicated"), \Laminas\Http\Response::STATUS_CODE_400);
    }
    foreach (array_keys($_REQUEST["note"]) as $note) {
        $data_c = $notepadlib->get_note($user, $note);
        $data = $data_c['data'];
        if ($first) {
            $first = false;
            $merge .= "---------" . tra('merged note:') . $data_c['name'] . "----" . "\n";
        } else {
            $merge .= "\n---------" . tra('merged note:') . $data_c['name'] . "----" . "\n";
        }
        $merge .= $data;
    }
    // Now create the merged note
    $tikilib->replace_note($user, 0, $_REQUEST['merge_name'], $merge);
}

if (isset($_REQUEST["delete"]) && isset($_REQUEST["note"]) && $access->checkCsrf()) {
    foreach (array_keys($_REQUEST["note"]) as $note) {
        $notepadlib->remove_note($user, $note);
    }
}

$quota = $userfileslib->userfiles_quota($user);
$limit = $prefs['userfiles_quota'] * 1024 * 1000;
if ($limit == 0) {
    $limit = 999999999;
}

$percentage = ($quota / $limit) * 100;
$cellsize = round($percentage / 100 * 200);
if ($cellsize == 0) {
    $cellsize = 1;
}

$percentage = round($percentage);
$smarty->assign('cellsize', $cellsize);
$smarty->assign('percentage', $percentage);
$sort_mode = $_REQUEST["sort_mode"] ?? 'lastModif_desc';
$offset = $_REQUEST["offset"] ?? 0;
$smarty->assign_by_ref('offset', $offset);
$find = $_REQUEST["find"] ?? '';
$smarty->assign('find', $find);
$smarty->assign_by_ref('sort_mode', $sort_mode);
$pdate = $_SESSION['thedate'] ?? $tikilib->now;
$channels = $notepadlib->list_notes($user, $offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('pages_count', $channels["count"]);
$smarty->assign_by_ref('channels', $channels["data"]);
include_once('tiki-section_options.php');
include_once('tiki-mytiki_shared.php');
$smarty->assign('mid', 'tiki-notepad_list.tpl');
$smarty->display("tiki.tpl");
