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
        'staticKeyFilters'                    => [
                'clean'                       => 'bool',             //post
                'zip'                         => 'none',             //post
                'zipPath'                     => 'alpha',            //post
        ],
    ],
];
require_once('tiki-setup.php');
$access->check_permission(['tiki_p_clean_cache']);
//get_strings tra('Tiki Cache/Sys Admin')
$done = '';
$output = '';
$buf = '';
$errors = false;

$cachelib = TikiLib::lib('cache');

$tmp_dir = sys_get_temp_dir();
if (! is_dir($tmp_dir)) {
    $new_tmp_dir = mkdir($tmp_dir, 0777, true);
    if (! $new_tmp_dir) {
        $errors = true;
    }
} else {
    if (! TikiSetup::is_writeable($tmp_dir)) {
        $errors = true;
    }
}
if ($errors) {
    Feedback::errorAndDie("Temporary folder is set to $tmp_dir, but it is not accessible by Tiki", \Laminas\Http\Response::STATUS_CODE_500);
}

if (isset($_GET['do'])) {
    $cachelib->empty_cache($_GET['do']);
    if ($_GET['do'] === 'all') {
        // Also rebuild admin index
        TikiLib::lib('prefs')->rebuildIndex();

        // also rebuild plugin prefdoc current version
        if (file_exists(STORAGE_PREFSDOC_PATH . '/state.json')) {
            unlink(STORAGE_PREFSDOC_PATH . '/state.json');
        }

        // seems combination of clearing prefs and public now messes up the page, so reload
        include_once('lib/setup/prefs.php');
        initialize_prefs();
        include('lib/setup/javascript.php');
        include('lib/setup/theme.php');
    }
    // codemirror modes are created in /temp/public -- need to restore them
    if ($_GET['do'] === 'temp_public') {
        include('lib/setup/javascript.php');
    }
}
if (isset($_GET['compiletemplates'])) {
    $ctempl = SMARTY_TEMPLATES_PATH;
    $cachelib->cache_templates($ctempl, $_GET['compiletemplates']);
    if ($tikidomain) {
        $ctempl .= "/$tikidomain";
    }
    $cachelib->cache_templates($ctempl, $_GET['compiletemplates']);
    $logslib->add_log('system', 'compiled templates');
}
if (! empty($_REQUEST['clean'])) {
    $userlib->remove_lost_groups();
}
$smarty->assign('lostGroups', $userlib->get_lost_groups());
$languages = [];
$langLib = TikiLib::lib('language');
$languages = $langLib->list_languages();
$templates_c = $cachelib->count_cache_files(SMARTY_COMPILED_TEMPLATES_PATH . '/' . $tikidomain);
$smarty->assign('templates_c', $templates_c);
$tempcache = $cachelib->count_cache_files(TEMP_CACHE_PATH . '/' . $tikidomain);
$smarty->assign('tempcache', $tempcache);
$temppublic = $cachelib->count_cache_files(TEMP_PUBLIC_PATH . '/' . $tikidomain);
$smarty->assign('temppublic', $temppublic);
$templates = [];
foreach ($languages as $clang) {
    if ($smarty->use_sub_dirs) { // was if (is_dir("templates_c/$tikidomain/")) ppl with tikidomains should test. redflo
        $templates[$clang["value"]] = $cachelib->count_cache_files(SMARTY_COMPILED_TEMPLATES_PATH . '/' . $tikidomain . '/' . $clang["value"] . '/');
    } else {
        $templates[$clang["value"]] = $cachelib->count_cache_files(SMARTY_COMPILED_TEMPLATES_PATH . '/', $tikidomain . $clang["value"]);
    }
}
$smarty->assign_by_ref('templates', $templates);
if ($prefs['feature_forums'] == 'y') {
    $commentslib = TikiLib::lib('comments');
    $dirs = $commentslib->list_directories_to_save();
} else {
    $dirs = [];
}
if ($prefs['feature_file_galleries'] == 'y' && ! empty($prefs['fgal_use_dir'])) {
    $dirs[] = $prefs['fgal_use_dir'];
}
if ($prefs['feature_trackers'] == 'y') {
    if (! empty($prefs['t_use_dir'])) {
        $dirs[] = $prefs['t_use_dir'];
    }
    $dirs[] = TRACKER_FIELD_IMAGE_STORAGE_PATH;
}
if ($prefs['feature_wiki'] == 'y') {
    if (! empty($prefs['w_use_dir'])) {
        $dirs[] = $prefs['w_use_dir'];
    }
    if ($prefs['feature_create_webhelp'] == 'y') {
        $dirs[] = WHELP_PATH;
    }
    $dirs[] = DEPRECATED_IMG_WIKI_PATH;
    $dirs[] = DEPRECATED_IMG_WIKI_UP_PATH;
}
$dirs = array_unique($dirs);
$dirsExist = [];
foreach ($dirs as $i => $d) {
    $dirsWritable[$i] = is_writable($d);
}
$smarty->assign_by_ref('dirs', $dirs);
$smarty->assign_by_ref('dirsWritable', $dirsWritable);
$smarty->assign('zipPath', '');
if (isset($_REQUEST['zip']) && isset($_REQUEST['zipPath']) && $tiki_p_admin == 'y') {
    if (empty($_REQUEST['zipPath'])) {
        Feedback::errorAndDie(tra('Error: Zip path cannot be empty'), \Laminas\Http\Response::STATUS_CODE_500);
    }
    if (! $archive = new PclZip($_REQUEST['zipPath'])) {
        Feedback::errorAndDie(tra('Error:') . $archive->errorInfo(true), \Laminas\Http\Response::STATUS_CODE_409);
    }
    foreach ($dirs as $d) {
        if (file_exists($d)) {
            $dirs2[] = $d;
        }
    }
    if (! $archive->add($dirs2)) {
        Feedback::errorAndDie(tra('Error:') . $archive->errorInfo(true), \Laminas\Http\Response::STATUS_CODE_409);
    }
    $smarty->assign('zipPath', $_REQUEST['zipPath']);
}
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('mid', 'tiki-admin_system.tpl');
$smarty->display("tiki.tpl");
