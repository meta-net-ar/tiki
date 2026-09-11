<?php

require_once __DIR__ . '/tiki-setup.php';

$prefslib = TikiLib::lib('prefs');
$db = TikiDb::get();

$file_secret = @file_exists('/etc/prosody/xmpp_http_secret')
    ? trim(@file_get_contents('/etc/prosody/xmpp_http_secret') ?: '')
    : '';
$pref_secret = trim((string) ($prefslib->getPreference('xmpp_shared_secret')['value'] ?? ''));
$trusted_secret = $file_secret !== '' ? $file_secret : $pref_secret;

$auth = $_SERVER['HTTP_AUTHORIZATION']
     ?? $_SERVER['Authorization']
     ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
     ?? '';

$okSecret = false;

if (stripos($auth, 'Basic ') === 0) {
    $decoded = base64_decode(substr($auth, 6), true) ?: '';
    [$apiUser, $apiPass] = array_pad(explode(':', $decoded, 2), 2, '');
    if ($apiUser === 'prosody' && $trusted_secret !== '' && hash_equals($trusted_secret, $apiPass)) {
        $okSecret = true;
    }
}

$header_secret = $_SERVER['HTTP_X_PROSODY_SECRET'] ?? '';
if (! $okSecret && $trusted_secret !== '' && $header_secret !== '') {
    if (hash_equals($trusted_secret, (string) $header_secret)) {
        $okSecret = true;
    }
}

// Accept the shared secret in the POST body only, never in the URL.
$req_secret = $_POST['secret'] ?? null;
if (! $okSecret && $trusted_secret !== '' && $req_secret !== null) {
    if (hash_equals($trusted_secret, (string) $req_secret)) {
        $okSecret = true;
    }
}

if (! $okSecret) {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    http_response_code(403);
    echo 'false';
    exit;
}

$respond_bool = function (bool $ok) {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    echo $ok ? 'true' : 'false';
    exit;
};

function tiki_xmpp_guest_valid_username(string $u): bool
{
    return (bool) preg_match('/^guest-[0-9a-f]{20}$/', $u);
}

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$method  = basename($uriPath);

if ($method === 'user_exists') {
    $u = $_REQUEST['user'] ?? '';
    $respond_bool(tiki_xmpp_guest_valid_username($u));
}

if ($method === 'check_password') {
    $u = $_REQUEST['user'] ?? '';
    $p = $_REQUEST['pass'] ?? null;

    if (! tiki_xmpp_guest_valid_username($u) || $p === null || $p === '') {
        $respond_bool(false);
    }

    $now = time();
    $db->query('DELETE FROM tiki_xmpp_guest_credentials WHERE last_seen < ?', [$now - 180 * 24 * 60 * 60]);
    $hash = $db->getOne('SELECT password_hash FROM tiki_xmpp_guest_credentials WHERE username = ?', [$u]);

    if (! $hash) {
        $db->query(
            'INSERT INTO tiki_xmpp_guest_credentials (username, password_hash, created, last_seen) VALUES (?, ?, ?, ?)',
            [$u, password_hash($p, PASSWORD_DEFAULT), $now, $now]
        );
        $respond_bool(true);
    }

    if (password_verify($p, $hash)) {
        $db->query('UPDATE tiki_xmpp_guest_credentials SET last_seen = ? WHERE username = ?', [$now, $u]);
        $respond_bool(true);
    }

    $respond_bool(false);
}

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
http_response_code(404);
echo 'false';
