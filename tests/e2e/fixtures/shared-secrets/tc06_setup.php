<?php

// TC-06 setup: creates encryption key + tracker + encrypted field
// Returns JSON {trackerId, fieldId, keyId, share}
// share = Test1's share string (needed for unlock flow tests)

// Fatals must stay visible on stderr (stdout is reserved for the JSON contract).
error_reporting(E_ERROR);
ini_set('display_errors', 'stderr');

if (PHP_SAPI !== 'cli') {
    die;
}
require_once __DIR__ . '/../../../../tiki-setup.php';
require_once __DIR__ . '/../helpers.php';

bootstrap_admin();

delete_keys_by_name('TC06-EncKey');
delete_trackers_by_name('TC06-Tracker');

$key     = create_encryption_key('TC06-EncKey', 'Test1');
$tracker = create_encrypted_tracker('TC06-Tracker', 'TC06 unlock flow test — safe to delete', $key['keyId']);

echo json_encode([
    'trackerId' => $tracker['trackerId'],
    'fieldId'   => $tracker['fieldId'],
    'keyId'     => $key['keyId'],
    'share'     => $key['shares'][0],
]);
