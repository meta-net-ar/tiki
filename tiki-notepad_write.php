<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'mytiki';
$inputConfiguration = [
    [
        'staticKeyFilters'     => [
        'noteId'               => 'int',        //get
        'remove'               => 'int',        //get
        'save'                 => 'bool',       //post
        'name'                 => 'string',     //post
        'data'                 => 'none',       //post
        'parse_mode'           => 'string',     //post
        ],
    ],
];
require_once('tiki-setup.php');
include_once('lib/notepad/notepadlib.php');
$access->check_feature('feature_notepad');
$access->check_user($user);
$access->check_permission('tiki_p_notepad');
if (isset($_REQUEST["remove"])) {
    $access->checkCsrf();
    $notepadlib->remove_note($user, $_REQUEST['remove']);
}
include 'lib/setup/editmode.php';
if (isset($_REQUEST["noteId"])) {
    $note_id = $_REQUEST["noteId"];
    $smarty->assign('noteId', $note_id);
    $info = $notepadlib->get_note($user, $note_id);
    if ($info['parse_mode'] == 'raw') {
        $info['parsed'] = nl2br(htmlspecialchars($info['data']));
        $smarty->assign('wysiwyg', 'n');
    } else {
        $info['parsed'] = TikiLib::lib('parser')->parse_data($info['data'], ['is_html' => $is_html]);
    }
} else {
    $info = [];
    $info['name'] = '';
    $info['data'] = '';
    $info['parse_mode'] = 'wiki';
}
if (isset($_REQUEST['save'])) {
    $access->checkCsrf();
    $noteId = $notepadlib->replace_note($user, $noteId ?? null, $_REQUEST["name"], $_REQUEST["data"], $_REQUEST["parse_mode"]);
    header('location: tiki-notepad_read.php?noteId=' . $noteId);
    die;
}

$smarty->assign('info', $info);
include_once('tiki-section_options.php');
include_once('tiki-mytiki_shared.php');
$smarty->assign('mid', 'tiki-notepad_write.tpl');
$smarty->display("tiki.tpl");
