<?php

// Sets the admin password on a freshly provisioned e2e test instance.
// Also stamps pass_confirm: a clean install leaves it NULL, which forces an
// interactive password change at first login and would break automated login
// (same approach as upsert_test_user() in helpers.php).
// Called by bin/e2e-provision.sh.
// Usage: php tests/e2e/fixtures/provision_admin.php {password}
// Output: JSON {"admin":"updated"}

// Fatals must stay visible on stderr (stdout is reserved for the JSON contract).
error_reporting(E_ERROR);
ini_set('display_errors', 'stderr');

if (PHP_SAPI !== 'cli') {
    die;
}
require_once __DIR__ . '/helpers.php';

$password = $argv[1] ?? '';
if ($password === '') {
    echo json_encode(['success' => false, 'error' => 'missing password arg']);
    exit(1);
}

$pdo = get_pdo();
$pdo->prepare('UPDATE users_users SET hash = ?, pass_confirm = ? WHERE login = ?')
    ->execute([password_hash($password, PASSWORD_BCRYPT), time(), 'admin']);

echo json_encode(['admin' => 'updated']) . "\n";
