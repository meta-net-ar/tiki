<?php

// tiki-xmpp-auth.php — HTTP endpoint for Prosody mod_auth_http
//
// Compatible with Prosody Community Module mod_auth_http
// https://modules.prosody.im/mod_auth_http.html
//
// - Prosody calls /check_password and /user_exists
// - Responds "true"/"false" in text/plain
// - Also supports JSON POST for manual curl/debug

require_once __DIR__ . '/tiki-setup.php';

$prefslib = TikiLib::lib('prefs');
$userslib = TikiLib::lib('user');
$xmpplib  = TikiLib::lib('xmpp');

// --- Shared secret (file or preference) and HTTP auth handling ---
$file_secret = @file_exists('/etc/prosody/xmpp_http_secret') ? trim(@file_get_contents('/etc/prosody/xmpp_http_secret') ?: '') : '';
$pref_secret = trim((string) ($prefslib->getPreference('xmpp_shared_secret')['value'] ?? ''));
$trusted_secret = $file_secret !== '' ? $file_secret : $pref_secret;

$auth = $_SERVER['HTTP_AUTHORIZATION']
     ?? $_SERVER['Authorization']
     ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
     ?? '';

// Default: blocked
$okSecret = false;

// Accept Authorization: Basic prosody:<secret>
if (stripos($auth, 'Basic ') === 0) {
    $decoded = base64_decode(substr($auth, 6), true) ?: '';
    [$apiUser, $apiPass] = array_pad(explode(':', $decoded, 2), 2, '');
    if ($apiUser === 'prosody' && $trusted_secret !== '' && hash_equals($trusted_secret, $apiPass)) {
        $okSecret = true;
    }
}

// Or accept ?secret=... or POST secret
$req_secret = $_GET['secret'] ?? $_POST['secret'] ?? null;
if (! $okSecret && $trusted_secret !== '' && $req_secret !== null) {
    if (hash_equals($trusted_secret, (string)$req_secret)) {
        $okSecret = true;
    }
}

if (! $okSecret) {
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(403);
    echo "false";
    exit;
}

// --- Helper ---
$respond_bool = function (bool $ok) {
    header('Content-Type: text/plain; charset=utf-8');
    echo $ok ? 'true' : 'false';
    exit;
};

// --- Detect method ---
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$method  = basename($uriPath);

if ($method === 'user_exists') {
    $u = $_REQUEST['user'] ?? '';
    $exists = (bool) ($u && $userslib->get_user_info($u));
    $respond_bool($exists);
}

if ($method === 'check_password') {
    $u = $_REQUEST['user'] ?? '';
    $p = $_REQUEST['pass'] ?? null;

    if (! $u || $p === null) {
        $respond_bool(false);
    }

    // 1) Temporary token
    if ($xmpplib->checkXmppSessionToken($u, $p)) {
        $respond_bool(true);
    }

    // 2) Fallback: Tiki password
    $ok = false;
    try {
        $validation = $userslib->validate_user($u, $p);
        $ok = is_array($validation) ? $validation[0] : $validation;
    } catch (\Throwable $e) {
        // Error handling without logging sensitive data
    }

    $respond_bool($ok);
    exit;
}

// --- Fallback: JSON POST mode for debug/testing (admin use only) ---
header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '{}', true);

$username = $data['username'] ?? $_POST['username'] ?? $_GET['username'] ?? '';
$password = $data['password'] ?? $_POST['password'] ?? $_GET['password'] ?? '';

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['valid' => false, 'reason' => 'bad_request']);
    exit;
}

// Basic user checks
$userInfo = $userslib->get_user_info($username);
if (! $userInfo) {
    http_response_code(403);
    echo json_encode(['valid' => false, 'reason' => 'unknown_user']);
    exit;
}
if (! empty($userInfo['userBanned']) || ! empty($userInfo['waiting'])) {
    http_response_code(403);
    echo json_encode(['valid' => false, 'reason' => 'inactive_or_banned']);
    exit;
}

// 1) Token check
try {
    if ($xmpplib->checkXmppSessionToken($username, $password)) {
        http_response_code(200);
        echo json_encode(['valid' => true, 'method' => 'token']);
        exit;
    }
} catch (Throwable $e) {
    // Error handling without logging sensitive data
}

// 2) Fallback to Tiki password
try {
    $ok = (bool) $userslib->validate_user($username, $password);
} catch (Throwable $e) {
    $ok = false;
    // Error handling without logging sensitive data
}

if (! $ok) {
    http_response_code(403);
    echo json_encode(['valid' => false, 'reason' => 'auth_failed']);
    exit;
}

http_response_code(200);
echo json_encode(['valid' => true, 'method' => 'password']);
