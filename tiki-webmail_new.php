<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once("tiki-setup.php");

$access->check_feature('feature_webmail');
$access->check_permission_either(['tiki_p_use_webmail', 'tiki_p_use_group_webmail']);
$access->check_user($user);

require_once  'vendor_bundled/vendor/cypht-org/cypht/index.php';

$response = $dispatcher->output;

$response = '';

$out = preg_replace('#</?body[^>]*>|</?html[^>]*>|#', '', $response);

TikiLib::lib('header')->add_cssfile('vendor_bundled/vendor/cypht-org/cypht/site.css');

TikiLib::lib('header')->add_js_module('import "@jquery-tiki/cypht";');

$smarty->assign('output_data', '<div class="inline-webmail">' . $out . "</div>");

$smarty->assign('mid', 'tiki-webmail.tpl');

$smarty->display('tiki.tpl');