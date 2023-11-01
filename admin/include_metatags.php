<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

global $prefs;
$access->check_permission('tiki_p_admin');

if (isset($_REQUEST['regenerate']) and $_REQUEST['regenerate'] == 1) {
    //$contents = file_get_contents('../tiki-robots.php');
    Feedback::success(tr('robots,txt regenerate with succes'));
    $access->redirect('tiki-admin.php?page=metatags');
}
elseif (isset($_REQUEST['regenerate']) and $_REQUEST['regenerate'] == 'deleteOld') {
    Feedback::success(tr('robots,txt regenerate with succes'));
    $access->redirect('tiki-admin.php?page=metatags');
}
