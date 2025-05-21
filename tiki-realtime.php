<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('tiki-setup.php');

if (php_sapi_name() != 'cli') {
    die("This server can only be started from the command line.");
}

use Tiki\Realtime\Chat;
use Tiki\Realtime\Console;
use Tiki\Realtime\Ping;
use Tiki\Realtime\IotDashboardNotifier;

/*
Install/Deploy/Run:

Apache config (virtualmin or another server):

    ProxyPass /ws ws://localhost:8080/
    ProxyPassReverse /ws ws://localhost:8080/

Nginx config:

location /ws {
    proxy_pass ws://127.0.0.1:8080;
}

Systemd service via virtualmin: https://lab12.evoludata.com:10000/init/edit_systemd.cgi?new=1&xnavigation=1
Start WS server with the same user that Tiki web requests run as (to avoid permission issues) - e.g. sudo -u www-data php tiki-realtime.php
*/

// console-related setup
error_reporting(E_ALL);
ini_set('session.use_cookies', 0);

// Run the server application through the WebSocket protocol on specified port (default: 8080)
$opts = getopt("p::");
if (isset($opts['p'])) {
    $port = $opts['p'];
} elseif (! empty($prefs['realtime_port'])) {
    $port = $prefs['realtime_port'];
} else {
    $port = 8080;
}
$tikilib->set_preference('realtime_port', $port);
echo "Listening on port $port...\n";

$app = new Ratchet\App('localhost', $port);
$app->route('/console', new Console(), ['*']);
$app->route('/chat', new Chat(), ['*']);
$app->route('ping', new Ping(), ['*']);
$app->route('/iot-dashboard-notifier', new IotDashboardNotifier(), ['*']);
$app->run();
