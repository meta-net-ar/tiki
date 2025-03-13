<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once("tiki-setup.php");

if ($prefs['auth_webhooks'] !== 'y') {
    http_response_code(403);
    echo tr("Feature not enabled");
    exit;
}

$whlib = TikiLib::lib('webhook');
$webhooks = $whlib->getWebhooks();
$webhook = null;
foreach ($webhooks as $candidate) {
    if ($whlib->verify($candidate)) {
        $webhook = $candidate;
        break;
    }
}

if (! $webhook) {
    http_response_code(403);
    echo tr("Webhook not authorized.");
    exit;
}

$user = $webhook['user'];
$_SESSION[$user_cookie_site] = $user;
require('lib/setup/perms.php');
require('lib/setup/user_prefs.php');

TikiLib::events()->trigger('tiki.webhook.received', [
    'webhook' => $webhook
]);

echo "ok";
