<?php

// TC-04 setup: creates encryption key + tracker + encrypted field
// Usage: ddev exec php /var/www/html/tests/e2e/fixtures/shared-secrets/tc04_setup.php
// Output: JSON {trackerId, fieldId, keyId}

// Fatals must stay visible on stderr (stdout is reserved for the JSON contract).
error_reporting(E_ERROR);
ini_set('display_errors', 'stderr');

if (PHP_SAPI !== 'cli') {
    die;
}
require_once __DIR__ . '/../../../../tiki-setup.php';
require_once __DIR__ . '/../helpers.php';

bootstrap_admin();

delete_keys_by_name('TC04-EncKey');
delete_trackers_by_name('TC04-Tracker');

$key     = create_encryption_key('TC04-EncKey', 'Test1');
$tracker = create_encrypted_tracker('TC04-Tracker', 'TC04 E2E test — safe to delete', $key['keyId']);

echo json_encode(['trackerId' => $tracker['trackerId'], 'fieldId' => $tracker['fieldId'], 'keyId' => $key['keyId']]);
