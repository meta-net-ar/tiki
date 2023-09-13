<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

require_once('tiki-setup.php');

$prefslib = TikiLib::lib('prefs');
$tikilib = TikiLib::lib('tiki');
$orphanPrefs = $prefslib->getOrphanPrefs();

if (isset($_REQUEST['clear']) && ! empty($orphanPrefs)) {
    $clear = $_REQUEST['clear'];
    $msg = "";

    if ($clear == "all") {
        foreach ($orphanPrefs as $p) {
            $tikilib->delete_preference($p['name']);
        }
        $msg = tr("All orphaned preference data has been successfully deleted!");
    } else {
        $tikilib->delete_preference($clear);
        $msg = tr("Preference <b>$clear's</b> data has been successfully deleted!");
    }

    if ($msg != '') {
        Feedback::success($msg);
    }
    $orphanPrefs = $prefslib->getOrphanPrefs();
}

// Prepare Fancytable
$ts = new Table_Plugin();
$ts->setSettings(
    'wpfancytable' . 'OrphanPrefs',
    'n',
    'type:reset',
    '[0,0],[1,0]',
    null,
    'type:text;|type:text;|type:nofilter',
    'type:reset',
    'max:50',
    null,
    null,
    null,
    null,
    null,
    null
);
if (is_array($ts->settings)) {
    $ts->settings['resizable'] = true;
    Table_Factory::build('plugin', $ts->settings);
}
$msg = "";
if ($prefs['feature_jquery_tablesorter'] === 'n') {
    $msg = tra('The jQuery Sortable Tables feature must be activated for the sort feature to work.');
} elseif ($prefs['javascript_enabled'] !== 'y') {
    $msg = tra('JavaScript must be enabled for the sort feature to work.');
}
$smarty->assign('msg', $msg);
$smarty->assign('orphanPrefs', $orphanPrefs);
