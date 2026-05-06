<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Services\Webauthn\AuthenticatorMapper;

require_once('tiki-setup.php');

$access = TikiLib::lib('access');
$user = $_SESSION['u_info']['login'];
$offset = $_REQUEST['offset'] ?? 0;
$maxRecords = $prefs['maxRecords'];
$credsTable = TikiDb::get()->table('tiki_webauthn_credentials');

if (empty($user)) {
    $smarty->assign('msg', tr('You must be logged in to access this page'));
    $smarty->display('error.tpl');
    die;
}

if ($prefs['auth_webauthn_enabled'] === 'y') {
    TikiLib::lib('header')->add_jsfile('lib/jquery_tiki/tiki-webauthn.js');
}

if (! empty($_REQUEST['authenticator_id']) && $_REQUEST['action'] === 'remove' && $access->checkCsrf(true)) {
    $authenticatorId = (int) $_REQUEST['authenticator_id'];
    $isDeleted = $credsTable->delete(['id' => $authenticatorId])->numRows();
    if ($isDeleted) {
        $logslib = TikiLib::lib('logs');
        $logslib->add_action('Removed', $_REQUEST['authenticator_id'], 'webauthn_authenticator');
        Feedback::success(tr('Authenticator deleted from this site.'));
        Feedback::warning(tr('The passkey copy stored on your device was not removed automatically. Servers cannot delete device-side credentials. You may want to remove it from your device or browser passkey settings. <a href="https://doc.tiki.org/Passkeys#Removing-a-passkey-from-your-device" target="_blank" rel="noopener">Learn how to remove it on your device</a>.'));
    }
}

$listCreds = $credsTable->fetchAll([], ['user' => $user], $maxRecords, $offset, ['id' => 'DESC']) ?? [];
$devices = array_map(function ($row) {
    $aaguid = $row['authenticator_id'];
    $getAuthenticator = AuthenticatorMapper::getAuthenticatorMetadata($aaguid);
    $row['authenticator_name'] = $getAuthenticator['name'];
    return $row;
}, $listCreds);
$count = $credsTable->fetchCount(['user' => $user]);

$smarty->assign_by_ref('devices', $devices);
$smarty->assign('offset', $offset);
$smarty->assign('maxRecords', $maxRecords);
$smarty->assign('count', $count);
$smarty->assign('user', $user);
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('mid', 'tiki-webauthn.tpl');
$smarty->display('tiki.tpl');
