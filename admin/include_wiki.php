<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}
require_once('tiki-setup.php');

global $tikidomain;
$path = $tikidomain ? EXPORT_DUMP_PATH . "/$tikidomain/dump_wiki.tar" : EXPORT_DUMP_PATH . '/dump_wiki.tar';

//*** begin state-changing actions
if (! empty($_POST['w_use_dir']) && $access->checkCsrf()) {
    if (substr($_POST['w_use_dir'], -1) != '\\' && substr($_POST['w_use_dir'], -1) != '/') {
        //TODO don't change $_POST values directly
        $_POST['w_use_dir'] .= '/';
    }
    simple_set_value('w_use_dir');
}

if (! empty($_POST['moveWikiUp']) && $access->checkCsrf()) {
    $filegallib = TikiLib::lib('filegal');
    $errorsWikiUp = [];
    $info = $filegallib->get_file_gallery_info($prefs['home_file_gallery']);
    if (empty($info)) {
        Feedback::error(tr('You must set a home file gallery'));
    } else {
        $filegallib->moveAllWikiUpToFgal($prefs['home_file_gallery']);
    }
}

// Included for the forum dropdown
if (isset($_POST['createtag']) && $access->checkCsrf()) {
    // Check existence
    if ($adminlib->tag_exists($_POST['newtagname'])) {
        Feedback::error(tra('Tag already exists'));
    }
    $adminlib->create_tag($_POST['newtagname']);
    Feedback::success(tr('Tag %0 created.', '<em>' . $_POST['newtagname'] . '</em>'));
}

if (isset($_POST['restoretag']) && $access->checkCsrf()) {
    // Check existance
    if (! $adminlib->tag_exists($_POST['tagname'])) {
        Feedback::error(tr('Tag %0 not found', '<em>' . $_POST['tagname'] . '</em>'));
    }
    $result = $adminlib->restore_tag($_POST['tagname']);
    if ($result) {
        Feedback::success(tr('Tag %0 restored.', '<em>' . $_POST['tagname'] . '</em>'));
    } else {
        Feedback::error(tr('Tag %0 not restored.', '<em>' . $_POST['tagname'] . '</em>'));
    }
}

if (isset($_POST['removetag']) && $access->checkCsrf()) {
    $result = $adminlib->remove_tag($_POST['tagname']);
    if ($result) {
        Feedback::success(tr('Tag %0 removed.', '<em>' . $_POST['tagname'] . '</em>'));
    } else {
        Feedback::error(tr('Tag %0 not removed.', '<em>' . $_POST['tagname'] . '</em>'));
    }
}

if (isset($_POST['rmvunusedpic']) && $access->checkCsrf()) {
    $adminlib->remove_unused_pictures();
    Feedback::success(tr('Process to remove pictures has completed.'));
}
//*** end state-changing actions

if ($prefs['feature_wiki_attachments'] === 'y') {
    if ($prefs['feature_use_fgal_for_wiki_attachments'] === 'y') {
        // check for legacy attachments
        $count = TikiLib::lib('wiki')->attachmentsCount();
        $smarty->assign('legacy_attachments_count', $count);
    } else {
        // check for fgal attachments
        $count = TikiLib::lib('filegal')->fileGalleryAttachmentsCount();
        $smarty->assign('fgal_attachments_count', $count);
    }
}

$smarty->assign('isDump', is_file($path));
$smarty->assign('dumpPath', $path);
$wiki_tags = $adminlib->get_tags();
$smarty->assign_by_ref('wiki_tags', $wiki_tags);
