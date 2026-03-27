<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once 'tiki-setup.php';
$access->check_feature('xmpp_feature');

if (empty($user)) {
    http_response_code(401);
    exit;
}

$xmpplib = TikiLib::lib('xmpp');
$tikilib = TikiLib::lib('tiki');

// Atomic check for pending sync; skip if nothing to do
$pending = $tikilib->get_user_preference($user, 'xmpp_sync_pending', '');
if ($pending !== 'y') {
    http_response_code(204);
    exit;
}

// Mark as running to avoid concurrent executions
$tikilib->set_user_preference($user, 'xmpp_sync_pending', 'running');
$nextState = '';

try {
    if (method_exists($xmpplib, 'syncUserGroupsToXmpp')) {
        $xmpplib->syncUserGroupsToXmpp($user);
    }
    $nextState = '';
    http_response_code(204);
} catch (\Throwable $e) {
    // Allow retry on next call
    $nextState = 'y';
    http_response_code(500);
} finally {
    // Always clear the running flag
    $tikilib->set_user_preference($user, 'xmpp_sync_pending', $nextState);
}
