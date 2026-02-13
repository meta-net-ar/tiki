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
        'noteId'               => 'int',        //get
        'remove'               => 'bool',        //post
        'wikify'               => 'bool',        //post
        'over'                 => 'bool',        //post
        'wiki_name'            => 'pagename',    //post
        'parse_mode'           => 'word',        //post
        ],
        'staticKeyFiltersForArrays' => [
            'note' => 'int',
        ],
    ],
];

require_once('tiki-setup.php');
include_once('lib/notepad/notepadlib.php');
use Tiki\Sections;
$section = Sections::SECTION_MY_TIKI;
Sections::setCurrentSection($section);
$access->check_feature('feature_notepad');
$access->check_user($user);
$access->check_permission('tiki_p_notepad');

if (! isset($_REQUEST["noteId"])) {
    Feedback::errorAndDie(tra("No note indicated"), \Laminas\Http\Response::STATUS_CODE_400);
}
if (isset($_REQUEST["remove"]) && $access->checkCsrf()) {
    $notepadlib->remove_note($user, $_REQUEST['noteId']);
    header('location: tiki-notepad_list.php');
    die;
}
$info = $notepadlib->get_note($user, $_REQUEST["noteId"]);
if (! $info) {
    Feedback::errorAndDie(tra("Note not found"), \Laminas\Http\Response::STATUS_CODE_404);
}

if (isset($_REQUEST['wikify']) || isset($_REQUEST['over'])) {
    $access->checkCsrf();
    if (empty($_REQUEST['wiki_name'])) {
        Feedback::errorAndDie(tra("No name indicated for wiki page"), \Laminas\Http\Response::STATUS_CODE_400);
    }
    $pageExists = $tikilib->page_exists($_REQUEST['wiki_name']);

    if ($pageExists && ! isset($_REQUEST['over'])) {
        // Page exists but user didn't request overwrite
        Feedback::errorAndDie(tra("Page already exists"), \Laminas\Http\Response::STATUS_CODE_409);
    } elseif ($pageExists) {
        // Page exists and user wants to overwrite
        $pageperms = $tikilib->get_perm_object($_REQUEST['wiki_name'], 'wiki page', '', false);
        if ($pageperms["tiki_p_edit"] != 'y') {
            Feedback::errorAndDie(tra("You do not have permission to edit this page."), \Laminas\Http\Response::STATUS_CODE_401);
        }
        $tikilib->update_page($_REQUEST['wiki_name'], $info['data'], tra('created from notepad'), $user, '127.0.1.1', $info['name']);
    } else {
        // Page doesn't exist, create new
        if ($tiki_p_edit != 'y') {
            Feedback::errorAndDie(tra("You do not have permission to edit this page."), \Laminas\Http\Response::STATUS_CODE_401);
        }
        $tikilib->create_page($_REQUEST['wiki_name'], 0, $info['data'], $tikilib->now, tra('created from notepad'), $user, '0.0.0.0', $info['name']);
    }
}

if ($tikilib->page_exists($info['name'])) {
    $smarty->assign("wiki_exists", "y");
} else {
    $smarty->assign("wiki_exists", "n");
}

if (isset($_REQUEST['parse_mode']) and $_REQUEST['parse_mode'] != $info['parse_mode']) {
    $notepadlib->set_note_parsing($user, $_REQUEST['noteId'], $_REQUEST['parse_mode']);
    $info['parse_mode'] = $_REQUEST['parse_mode'];
}

if ($info['parse_mode'] == 'raw') {
    $info['parsed'] = nl2br(htmlspecialchars($info['data']));
    $smarty->assign('wysiwyg', 'n');
} else {
    include 'lib/setup/editmode.php';
    $info['parsed'] = TikiLib::lib('parser')->parse_data($info['data'], ['is_html' => $is_html]);
}

$smarty->assign('noteId', $_REQUEST["noteId"]);
$smarty->assign('info', $info);
include_once('tiki-section_options.php');
include_once('tiki-mytiki_shared.php');
$smarty->assign('mid', 'tiki-notepad_read.tpl');
$smarty->display("tiki.tpl");
