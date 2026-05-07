<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}
global $prefs, $tiki_p_admin;

// Session-scoped Tidy reporting toggle.
// Only admins can change it, and only when the global preference is on.
// Passing ?tidy_html_reporting=n in a request disables reporting for this session
// without touching the global config — useful when editing pages with known markup issues.
if (($prefs['error_tracking_enabled_tidy'] ?? 'n') === 'y' && ($tiki_p_admin ?? 'n') === 'y') {
    if (isset($_REQUEST['tidy_html_reporting'])) {
        $value = $_REQUEST['tidy_html_reporting'];
        if (in_array($value, ['y', 'n'], true)) {
            TikiLib::lib('errortracking')->setTidySessionReporting($value === 'y');
        }
    } elseif (! isset($_SESSION['tidy_html_reporting'])) {
        $_SESSION['tidy_html_reporting'] = 'y';
    }
}
