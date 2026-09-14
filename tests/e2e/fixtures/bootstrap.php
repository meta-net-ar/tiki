<?php

// Bootstrap for the e2e PHP fixtures.
//
// Owns every step a fixture script would otherwise repeat: error routing, the CLI
// guard, the Tiki bootstrap, admin permissions, argument decoding and the JSON
// output contract. A fixture file therefore holds only its own domain logic.
//
// Usage. The script path is relative to this directory, so no caller needs to know
// where the Tiki root sits inside a container:
//
//   php tests/e2e/fixtures/bootstrap.php create_test_users.php
//   php tests/e2e/fixtures/bootstrap.php shared-secrets/tc04_setup.php
//   php tests/e2e/fixtures/bootstrap.php shared-secrets/tc04_teardown.php {base64(JSON)}
//
// Fixture contract: the script returns a callable and nothing else.
//
//   return function (array $input): array {
//       delete_keys_by_name('TC04-EncKey');
//       return ['keyId' => create_encryption_key('TC04-EncKey', 'Test1')['keyId']];
//   };
//
// The returned array is printed as JSON on stdout, the only thing ever written there.
// $input is the decoded second argument: for tcNN_teardown.php that is whatever
// tcNN_setup.php printed, handed back by common/fixtures.ts (base64-encoded so it
// survives ddev exec / docker / plain php quoting).
//
// A fixture that needs a different environment returns an options array instead:
//
//   return [
//       'tiki'  => false,   // skip tiki-setup.php, raw PDO access only
//       'admin' => false,   // skip bootstrap_admin(), defaults to the 'tiki' value
//       'run'   => function (array $input): array { ... },
//   ];
//
// Failures: throw. The message goes to stderr and the process exits non-zero, which
// is what common/fixtures.ts reports when it skips a suite.

// Fatals must stay visible on stderr (stdout is reserved for the JSON contract).
error_reporting(E_ERROR);
ini_set('display_errors', 'stderr');

if (PHP_SAPI !== 'cli') {
    die;
}

$relative = $argv[1] ?? '';

if ($relative === '') {
    fwrite(STDERR, "bootstrap: missing fixture script argument\n");
    exit(1);
}

$scriptPath = realpath(__DIR__ . '/' . $relative);

if ($scriptPath === false || ! is_file($scriptPath) || ! str_starts_with($scriptPath, __DIR__ . DIRECTORY_SEPARATOR)) {
    fwrite(STDERR, "bootstrap: no fixture script at fixtures/{$relative}\n");
    exit(1);
}

$decoded = json_decode((string) base64_decode($argv[2] ?? '', true), true);
$input   = is_array($decoded) ? $decoded : [];

// The fixture only defines a callable, so requiring it runs no domain code. That is
// what lets the options it returns decide how much of Tiki to load, below.
$fixture = require $scriptPath;
$options = is_array($fixture) ? $fixture : ['run' => $fixture];
$run     = $options['run'] ?? null;

if (! is_callable($run)) {
    fwrite(STDERR, "bootstrap: fixtures/{$relative} must return a callable, or an options array with a 'run' key\n");
    exit(1);
}

$withTiki = (bool) ($options['tiki'] ?? true);
// No Tiki bootstrap means no Perms to grant, so admin follows tiki unless overridden.
$withAdmin = (bool) ($options['admin'] ?? $withTiki);

if ($withTiki) {
    require_once __DIR__ . '/../../../tiki-setup.php';
}

require_once __DIR__ . '/helpers.php';

if ($withAdmin) {
    bootstrap_admin();
}

try {
    $result = $run($input);
} catch (Throwable $e) {
    fwrite(STDERR, "bootstrap: fixtures/{$relative} failed: " . $e->getMessage() . "\n");
    exit(1);
}

echo json_encode($result) . "\n";
