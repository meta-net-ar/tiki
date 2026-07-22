<?php

// Persists the "User Encryption" security feature to the DB so the Shared Secrets
// suite can reach the encryption admin tab without a manual admin toggle.
// Idempotent — safe to run on every test invocation (called from global-setup.ts).
// Usage: ddev exec php /var/www/html/tests/e2e/fixtures/enable_encryption.php
// Output: JSON {"feature_user_encryption":"y","changed":true|false}

// Fatals must stay visible on stderr (stdout is reserved for the JSON contract).
error_reporting(E_ERROR);
ini_set('display_errors', 'stderr');

if (PHP_SAPI !== 'cli') {
    die;
}

require_once __DIR__ . '/../../../tiki-setup.php';

// No bootstrap_admin() here: it seeds $prefs['feature_user_encryption']='y' in memory,
// which would mask the real DB value below. tiki-setup.php already loads $prefs from
// the DB, and set_preference() persists the pref without needing admin perms.
$tikilib = TikiLib::lib('tiki');
$before  = $tikilib->get_preference('feature_user_encryption', 'n');

if ($before !== 'y') {
    $tikilib->set_preference('feature_user_encryption', 'y');
}

echo json_encode([
    'feature_user_encryption' => 'y',
    'changed'                 => $before !== 'y',
]) . "\n";
