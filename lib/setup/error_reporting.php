<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.

use Tiki\Errors;

require_once 'lib/tikilib.php';

global $prefs, $tiki_p_admin;
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) != false) {
    header('location: index.php');
    exit;
}

/* This file handles reporting PHP errors in the HTML user interface and glitchtip.  Note that errors thrown from smarty templates are handled differently. */

$errorReportingLevel = Errors::getErrorReportingLevel();

// Handle Smarty specific error reporting level
$smarty = TikiLib::lib('smarty');
if (! empty($prefs['smarty_notice_reporting']) and $prefs['smarty_notice_reporting'] === 'y' && ($prefs['error_reporting_adminonly'] != 'y' || $tiki_p_admin == 'y')) {
    $smartyErrorReportingLevel = $errorReportingLevel | E_NOTICE | E_USER_NOTICE ;
} else {
    $smartyErrorReportingLevel = $errorReportingLevel & ~E_NOTICE & ~E_USER_NOTICE;
}
$smarty->error_reporting = $smartyErrorReportingLevel;

if (php_sapi_name() != 'cli') { // This handler collects errors to display at the bottom of the general template, so don't use it in CLI, otherwise errors would be lost.
    ini_set('display_errors', 0);
    $previousErrorHandler = set_error_handler('tiki_error_handling', $errorReportingLevel);
    if ($previousErrorHandler === 'tiki_error_handling') {
        //This is not normal, but actually happens in tiki-installer, so we restore the handler and go on.
        restore_error_handler();
        //throw new Exception("Tried to  set_error_handler('tiki_error_handling') while it was already set to tiki_error_handling");
    } elseif ($previousErrorHandler) {
        $previousErrorHandler = Closure::fromCallable($previousErrorHandler);
        TikiLib::lib('errortracking')->setPreviousErrorHandler($previousErrorHandler);
    };
}

if (($prefs['log_sql'] ?? 'n') == 'y' && $api_tiki == 'adodb') {
    $dbTiki->LogSQL();
    global $ADODB_PERF_MIN;
    $ADODB_PERF_MIN = $prefs['log_sql_perf_min'];
}

// TODO: check this only once per session or only if a feature ask for it
TikiSetup::check($tikidomain);

if (! isset($phpErrors)) {
    $phpErrors = [];
}
$smarty->assign_by_ref('phpErrors', $phpErrors);
