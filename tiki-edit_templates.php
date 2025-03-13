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
            'save'             => 'bool',            //post
            'saveTheme'        => 'bool',            //post
            'delete'           => 'bool',            //post
            'mode'             => 'string',          //post
            'data'             => 'string',          //post
            'template'         => 'string',          //post
        ],
    ],
];

require_once('tiki-setup.php');

$access->check_feature('feature_view_tpl');
$globalperms = Perms::get();
// you have to have the perm view and edit to continue:
      // if view perm is set: continue
if (
    ($globalperms->view_templates != 'y') ||
      // if edit perm is set: continue, else quit if user tries save/delete
      ($globalperms->edit_templates != 'y' &&
        (isset($_REQUEST["save"]) ||
         isset($_REQUEST['saveTheme']) ||
         isset($_REQUEST['delete'])
        )
      )
) {
    Feedback::errorAndDie(tra("You don't have permission to use this feature"), \Laminas\Http\Response::STATUS_CODE_401);
}

if (! isset($_REQUEST["mode"])) {
    $mode = 'listing';
} else {
    $mode = $_REQUEST['mode'];
}

// Validate to prevent editing any file
if (isset($_REQUEST["template"])) {
    if (strstr($_REQUEST["template"], '..')) {
        Feedback::errorAndDie(tra("You do not have permission to do that"), \Laminas\Http\Response::STATUS_CODE_401);
    }
}

$relativeDirectories = ['', 'mail/', 'map/', 'modules/', isset($prefs['style']) && ! empty($prefs['style']) ? 'styles/' . str_replace('.css', '', $prefs['style']) . '/' : ''];

// do editing stuff only if you have the permission to:
if ($globalperms->edit_templates == 'y') {
    if ((isset($_REQUEST["save"]) || isset($_REQUEST['saveTheme'])) && ! empty($_REQUEST['template']) && $access->checkCsrf(true)) {
        $access->check_feature('feature_edit_templates');
        $access->checkCsrf();
        if (isset($_REQUEST['saveTheme'])) {
            $domainStyleTemplatesDirectory = $smarty->main_template_dir;
            if (! empty($tikidomain)) {
                $domainStyleTemplatesDirectory .= '/' . $tikidomain;
            }
            $domainStyleTemplatesDirectory .= '/styles/' . $style_base;
            if (! is_dir($domainStyleTemplatesDirectory)) {
                mkdir($domainStyleTemplatesDirectory);
            }
            $file = $domainStyleTemplatesDirectory . '/' . $_REQUEST['template'];
            $relativeDirectory = dirname($_REQUEST['template']);
            if ($relativeDirectory && ! is_dir($domainStyleTemplatesDirectory . '/' . $relativeDirectory)) {
                if (in_array($relativeDirectory . '/', $relativeDirectories)) {
                    mkdir($domainStyleTemplatesDirectory . '/' . $relativeDirectory);
                } else {
                    Feedback::errorAndDie(tr('Template directory %0 unknown', $relativeDirectory), \Laminas\Http\Response::STATUS_CODE_500);
                }
            }
        } else {
            $file = $smarty->get_filename($_REQUEST['template']);
        }
        @$fp = fopen($file, 'w');
        if (! $fp) {
            Feedback::errorAndDie(tra("You do not have permission to write the template:") . ' ' . $file, \Laminas\Http\Response::STATUS_CODE_401);
        }
        $_REQUEST["data"] = str_replace("\r\n", "\n", $_REQUEST["data"]);
        fwrite($fp, $_REQUEST["data"]);
        fclose($fp);
    }

    if (isset($_REQUEST['delete']) && ! empty($_REQUEST['template'])) {
        $access->checkCsrf();
        $file = $smarty->get_filename($_REQUEST['template']);
        unlink($file);
        unset($_REQUEST['template']);
    }
}

if (isset($_REQUEST["template"])) {
    $mode = 'editing';
    $file = $smarty->get_filename($_REQUEST["template"]);
    if (strstr($file, '/styles/')) {
        $style_local = 'y';
    } else {
        $style_local = 'n';
    }
    $fp = fopen($file, 'r');
    if (! $fp) {
        Feedback::errorAndDie(tra("You do not have permission to read the template"), \Laminas\Http\Response::STATUS_CODE_401);
    }
    $data = fread($fp, filesize($file));
    fclose($fp);
    $smarty->assign('data', $data);
    $smarty->assign('template', $_REQUEST["template"]);
    $smarty->assign('style_local', $style_local);
}

if ($mode == 'listing') {
    // Get templates from the templates directory
    $files = [];
    chdir($smarty->main_template_dir);
    foreach ($relativeDirectories as $relativeDirectory) {
        $files = array_merge($files, glob($relativeDirectory . '*.tpl'));
    }
    chdir($tikipath);
    $smarty->assign('files', $files);
}
$smarty->assign('mode', $mode);


// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

// Get templates from the templates/modules directory
$smarty->assign('mid', 'tiki-edit_templates.tpl');
$smarty->display("tiki.tpl");
