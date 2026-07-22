<?php

// Ensures Test1 and Test2 exist with known passwords for e2e tests.
// Usage: ddev exec php /var/www/html/tests/e2e/fixtures/create_test_users.php 2>/dev/null
// Outputs JSON: {"created":[], "updated":[]}

// Fatals must stay visible on stderr (stdout is reserved for the JSON contract).
error_reporting(E_ERROR);
ini_set('display_errors', 'stderr');

if (PHP_SAPI !== 'cli') {
    die;
}
require_once __DIR__ . '/helpers.php';

$testUsers = ['Test1' => 'test1pass', 'Test2' => 'test2pass'];
$pdo       = get_pdo();

$result = array_reduce(
    array_keys($testUsers),
    function (array $acc, string $login) use ($pdo, $testUsers): array {
        $status         = upsert_test_user($pdo, $login, $testUsers[$login]);
        $acc[$status][] = $login;
        return $acc;
    },
    ['created' => [], 'updated' => []]
);

echo json_encode($result) . "\n";
