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
        'staticKeyFilters'                  => [
        'repID'                             => 'int',              //get
        'file'                              => 'string',           //get
        'clear_cache'                       => 'bool',             //get
        ],
    ],
];
require_once('tiki-setup.php');
require_once('lib/integrator/integrator.php');

$access->check_feature('feature_integrator');
$access->check_permission(['tiki_p_view_integrator']);

$repID = (isset($_REQUEST["repID"]) && strlen($_REQUEST["repID"]) > 0) ? $_REQUEST["repID"] : 0;

if (! isset($_REQUEST["repID"]) && ($repID <= 0)) {
    Feedback::errorAndDie(tra("Repository not found"), \Laminas\Http\Response::STATUS_CODE_409);
}
// Create instance of integrator
$integrator = new TikiIntegrator();
// Get repository configuration data
$rep = $integrator->get_repository($repID);

if (! $rep) {
    Feedback::errorAndDie(tra("Repository not found"), \Laminas\Http\Response::STATUS_CODE_404);
}


// Check if given file present at configured location
$file = $integrator->get_rep_file($rep, $_REQUEST["file"] ?? '');
if (
    (! str_starts_with($file, 'http://'))
    && (! str_starts_with($file, 'https://'))
    && ! file_exists($file)
) {
    if ($tiki_p_admin == 'y') {
        $msg = tra("File not found ") . $file;
    } else {
        $msg = tra("File not found ") . $_REQUEST["file"];
    }
    Feedback::errorAndDie($msg, \Laminas\Http\Response::STATUS_CODE_500);
}
// Needs to clear cached version of this file...
if (isset($_REQUEST["clear_cache"]) && $rep["cacheable"]) {
    $integrator->clear_cached_file($repID, ($_REQUEST["file"] ?? ''));
}
//
$url2cache = $tikilib->httpPrefix() . $_SERVER["SCRIPT_NAME"] . "?repID=" . $repID . (isset($_REQUEST["file"]) ? "&file=" . $_REQUEST["file"] : '');
$data = $integrator->get_file($repID, $file, $rep["cacheable"], $url2cache);
$smarty->assign_by_ref('data', $data);
$smarty->assign('repID', $repID);
$smarty->assign('cached', $rep["cacheable"]);

if (isset($_REQUEST["file"])) {
    $smarty->assign('file', $_REQUEST["file"]);
}

// Display the template
$smarty->assign('mid', 'tiki-integrator.tpl');
$smarty->display("tiki.tpl");
