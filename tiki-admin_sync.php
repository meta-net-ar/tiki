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
        'staticKeyFilters'          => [
            'url'                   => 'url',      //post
            'submit'                => 'bool',      //post
        ],
    ],
];
require_once('tiki-setup.php');

$access->check_permission('tiki_p_admin');

if (! empty($_REQUEST['submit'])) {
    try {
        $url = $_REQUEST['url'];
        if (empty($_REQUEST['url'])) {
            Feedback::errorPage(['mes' => 'The Remote Server Address is missing. Please provide a valid address to continue.']);
        }
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            Feedback::errorPage(['mes' => 'The provided Remote Server Address is not valid.']);
        }

        $client = new Services_ApiClient($url);
        $response = $client->get($client->route('export-sync'));
        $remote_content = $response['data'];
        $export_controller = new Services_Export_Controller();
        $local_content = $export_controller->dumpContent();

        require_once('lib/diff/difflib.php');
        $diff = diff2($local_content, $remote_content, 'sidediff-full');
        if (empty($diff)) {
            $diff = '<tr><td colspan="4">The diff is empty.</td></tr>';
        }
        $smarty->assign('diff', $diff);
    } catch (Services_Exception $e) {
        Feedback::error($e->getMessage(), '');
    }
}

// Display the template
$smarty->assign('mid', 'tiki-admin_sync.tpl');
$smarty->display("tiki.tpl");
