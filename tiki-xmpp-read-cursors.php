<?php

require_once 'tiki-setup.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($user)) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$xmpplib = TikiLib::lib('xmpp');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    $requestHost = strtolower((string) parse_url('//' . $requestHost, PHP_URL_HOST));
    foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $header) {
        if (empty($_SERVER[$header])) {
            continue;
        }
        $headerHost = strtolower((string) parse_url($_SERVER[$header], PHP_URL_HOST));
        if ($requestHost !== '' && $headerHost !== '' && $headerHost !== $requestHost) {
            http_response_code(403);
            echo json_encode([]);
            exit;
        }
    }

    if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 16384) {
        http_response_code(413);
        echo json_encode([]);
        exit;
    }

    $body = json_decode(file_get_contents('php://input', false, null, 0, 16384), true);
    $cursors = is_array($body) ? $body : [];
    echo json_encode($xmpplib->saveReadCursors($user, $cursors));
    exit;
}

echo json_encode($xmpplib->getReadCursors($user));
