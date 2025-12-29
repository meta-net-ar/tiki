<?php

require_once 'tiki-setup.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($user)) {
    echo json_encode(['jid' => '', 'rooms' => []]);
    exit;
}

$xmpplib = TikiLib::lib('xmpp');

$jid = $xmpplib->getEffectiveJidForUser($user);

$rooms = $xmpplib->getXmppRoomsForUser($user);

$full = array_map(fn($r) => $xmpplib->buildRoomJid($r), $rooms);
echo json_encode([
    'jid' => $jid,
    'rooms' => array_values(array_unique(array_filter($full))),
]);
