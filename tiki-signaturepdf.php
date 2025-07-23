<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once("tiki-setup.php");

$serverEndpoint = $_SERVER['HTTP_HOST'] . str_replace('/tiki-signaturepdf.php', '/tiki-signaturepdf_handler.php', $_SERVER['REQUEST_URI']);
$ch = curl_init($serverEndpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

if ($response === false) {
    Feedback::error("Error fetching the page: " . curl_error($ch));
}


$out = preg_replace('#</?body[^>]*>|</?html[^>]*>|#', '', $response);

$smarty->assign('output_data', '<div class="inline-signaturepdf">' . $out . "</div>");

$smarty->assign('mid', 'tiki-signaturepdf.tpl');

$smarty->display('tiki.tpl');
