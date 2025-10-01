<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('../tiki-setup.php');

$access->check_feature('feature_tikitests');

if ($tiki_p_admin_tikitests != 'y' and $tiki_p_play_tikitests != 'y') {
    Feedback::errorAndDie(tra('You do not have permission to do that'), \Laminas\Http\Response::STATUS_CODE_401);
}

$smarty->assign('tidy', extension_loaded("tidy"));
$smarty->assign('http', extension_loaded("http"));
$smarty->assign('curl', extension_loaded("curl"));

/**
 * @param $file
 * @return bool
 */
function delete_test($file)
{
    $access = TikiLib::lib('access');
    $access->checkCsrf(tra("You are about to delete a TikiTest file. Do you want to continue ?"));
    // Clean the filename
    $file = basename($file);
    if (file_exists(TIKI_TESTS_PATH . "/$file")) {
        return unlink(TIKI_TESTS_PATH . "/$file");
    }
    return false;
}

if (isset($_GET['offset']) and ($_GET['offset'] + 0) > 0) {
    $offset = $_GET['offset'];
} else {
    $offset = 0;
}

if (isset($_GET['files_per_page']) and ($_GET['files_per_page'] + 0) > 0) {
    $files_per_page = $_GET['filess_per_page'];
} else {
    $files_per_page = 20;
}

if (isset($_REQUEST['action'])) {
    if (strtolower($_REQUEST['action']) == strtolower(tra("Remove"))) {
        if (isset($_REQUEST['filename'])) {
            $ok = delete_test($_REQUEST['filename']);
            if (! $ok) {
                Feedback::errorAndDie(tra("There was an error deleting the file"), \Laminas\Http\Response::STATUS_CODE_500);
            }
        }
    }
}

chdir(TIKI_TESTS_PATH);
$files = glob('*.xml');
chdir('../..');
$files_number = count($files);
$files = array_slice($files, $offset, $files_per_page);

$smarty->assign_by_ref("files", $files);
$smarty->assign("offset", $offset);
$smarty->assign("files_number", $files_number);
$smarty->assign("files_per_page", $files_per_page);
$smarty->assign('title', tra("TikiTest List"));
$smarty->assign("mid", "tiki-tests_list.tpl");
$smarty->display("tiki.tpl");
