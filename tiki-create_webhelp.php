<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('tiki-setup.php');
$structlib = TikiLib::lib('struct');

/**
 * @param $source
 * @param $dest
 * @return int
 */
function copys($source, $dest)
{
    if (! is_dir($source)) {
        return 0;
    }

    if (! is_dir($dest)) {
        mkdir($dest);
    }

    $h = @dir($source);
    while (@($entry = $h->read()) !== false) {
        if (($entry != '.') && ($entry != '..')) {
            if (is_dir("$source/$entry") && $dest !== "$source/$entry") {
                copys("$source/$entry", "$dest/$entry");
            } else {
                @copy("$source/$entry", "$dest/$entry");
            }
        }
    }
    $h->close();
    return 1;
}

/**
 * @param $dir
 */
function deldirfiles(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $fileInfo) {
        $path = $fileInfo->getPathname();
        if ($fileInfo->isDir() && ! $fileInfo->isLink()) {
            @rmdir($path);
        } else {
            @unlink($path);
        }
    }
}

$access->check_feature('feature_create_webhelp');
$access->check_permission('tiki_p_edit_structures');

$struct_info = $structlib->s_get_structure_info($_REQUEST['struct']);
$smarty->assign_by_ref('struct_info', $struct_info);

if (! $tikilib->user_has_perm_on_object($user, $struct_info['pageName'], 'wiki page', 'tiki_p_view')) {
    Feedback::errorAndDie(tra('You do not have permission to view this page.'), \Laminas\Http\Response::STATUS_CODE_401);
}

if (isset($_REQUEST['create'])) {
    $smarty->assign('generated', 'y');
    $name = $_REQUEST['name'];
    $dir = $_REQUEST['dir'];
    $smarty->assign('dir', $_REQUEST['dir']);
    $struct = $_REQUEST['struct'];
    $top = $_REQUEST['top'];
    $output = '';
    $output .= tra('TikiHelp WebHelp generation engine. Generating WebHelp using:');
    $output .= '<ul><li>' . tr("Index: <strong>%0</strong>", $name) . '</li>';
    $output .= '<li>' . tr("Directory: <strong>%0</strong>", $dir) . '</li></ul>';
    $base = WHELP_PATH . "/$dir";

    if (! is_dir(WHELP_PATH)) {
        if (! mkdir(WHELP_PATH)) {
            Feedback::errorAndDie(tra("Unable to create directory Run <code>sh setup.sh</code> from the command line to fix."), \Laminas\Http\Response::STATUS_CODE_500);
        }
    }
    if (! is_writeable(WHELP_PATH)) {
        Feedback::errorAndDie(tra("You need to change chmod 'whelp' manually to 777"), \Laminas\Http\Response::STATUS_CODE_500);
    }

    if (! is_dir(WHELP_PATH . "/$dir")) {
        $output .= '<p>' . tr("Creating directory structure in <strong>%0</strong>.", $base) . '</p>';
        mkdir(WHELP_PATH . "/$dir");
        mkdir("$base/js");
        mkdir("$base/css");
        mkdir("$base/icons");
        mkdir("$base/menu");
        mkdir("$base/pages");
        mkdir("$base/pages/img");
        mkdir("$base/pages/img/wiki_up");
    }
    $output .= '<p>' . tra('Eliminating previous files.') . '</p>';
    deldirfiles("$base/js");
    deldirfiles("$base/css");
    deldirfiles("$base/icons");
    deldirfiles("$base/menu");
    deldirfiles("$base/pages");
    deldirfiles("$base/pages/img/wiki_up");
    // Copy base files to the webhelp directory
    copys('lib/tikihelp', "$base/");
    copys(IMG_TIKIHELP_PATH, "$base/icons");

    $structlib->structure_to_webhelp($struct, $dir, $top);
    $smarty->assign('generated', 'y');
}

$smarty->assign('output', $output);


// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

// Display the template
$smarty->assign('mid', 'tiki-create_webhelp.tpl');
$smarty->display('tiki.tpl');
