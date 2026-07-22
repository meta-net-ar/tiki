<?php

// TC-10 teardown: removes tracker + key
// Invoked by common/fixtures.ts (useSuiteFixtures) with the setup script's
// JSON output base64-encoded as the first argument.
// Usage: php tc10_teardown.php {base64(setup JSON)}

// Fatals must stay visible on stderr (stdout is reserved for the JSON contract).
error_reporting(E_ERROR);
ini_set('display_errors', 'stderr');

if (PHP_SAPI !== 'cli') {
    die;
}
require_once __DIR__ . '/../../../../tiki-setup.php';
require_once __DIR__ . '/../helpers.php';

bootstrap_admin();

$setupData = json_decode(base64_decode($argv[1] ?? ''), true) ?: [];
$trackerId = (int) ($setupData['trackerId'] ?? 0);
$keyId     = (int) ($setupData['keyId'] ?? 0);

if (! $trackerId || ! $keyId) {
    echo json_encode(['success' => false, 'error' => 'missing trackerId/keyId args']);
    exit(1);
}

delete_tracker_by_id($trackerId);
delete_key_by_id($keyId);

echo json_encode(['success' => true, 'trackerId' => $trackerId, 'keyId' => $keyId]);
