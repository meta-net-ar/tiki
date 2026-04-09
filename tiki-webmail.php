<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once("tiki-setup.php");

$access->check_feature('feature_webmail');
$access->check_permission_either(['tiki_p_use_webmail', 'tiki_p_use_group_webmail']);
$access->check_user($user);

//handle message priting
if (isset($_POST['display']) && $_POST['display'] == 'pdf') {
    require_once 'lib/cypht/integration/print_pdf.php';
    printPDFFromMessage();
    exit;
}

require_once  'vendor_bundled/vendor/cypht-org/cypht/index.php';

$config->initSession();

$dispatcher = $config->triggerInit();

$response = $dispatcher->output;

if (! empty($_SESSION['cypht']['user_data']['debug_mode_setting'])) {
    $msgs = Hm_Debug::get();
    foreach ($msgs as $msg) {
        TikiLib::lib('logs')->add_log('cypht', $msg);
    }
}

if ($dispatcher->request->format == 'Hm_Format_JSON') {
    echo $response;
    exit;
}

$out = preg_replace('#</?body[^>]*>|</?html[^>]*>|#', '', $response);

TikiLib::lib('header')->add_js_module('import "@jquery-tiki/cypht";');

$smarty->assign('output_data', '<div class="inline-cypht"><div class="app-container"><input type="hidden" id="hm_page_key" value="' . Hm_Request_Key::generate() . '" />'
    . $out
    . "</div></div>");

$smarty->assign('mid', 'tiki-webmail.tpl');

$headerlib->add_cssfile(BOOTSTRAP_ICONS_FONT_PATH . '/bootstrap-icons.css');

$headerlib->add_js_module('import "@jquery-tiki/cypht";');

$smarty->display('tiki.tpl');
