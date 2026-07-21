<?php

// TC-11 setup: creates encryption key assigned to Test1
// Returns JSON {keyId, share} — share is Test1's share string needed for regeneration
// Usage: ddev exec php /var/www/html/tests/e2e/fixtures/shared-secrets/tc11_setup.php

error_reporting(0);

if (PHP_SAPI !== 'cli') {
    die;
}
require_once __DIR__ . '/../../../../tiki-setup.php';
require_once __DIR__ . '/../helpers.php';

bootstrap_admin();

delete_keys_by_name('TC11-EncKey');

$result = create_encryption_key('TC11-EncKey', 'Test1');

echo json_encode(['keyId' => $result['keyId'], 'share' => $result['shares'][0]]);
