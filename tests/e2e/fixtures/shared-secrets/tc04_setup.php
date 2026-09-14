<?php

// TC-04 setup: creates encryption key + tracker + encrypted field.
// Output: JSON {trackerId, fieldId, keyId}
// Run by hand: php tests/e2e/fixtures/bootstrap.php shared-secrets/tc04_setup.php

return function (array $input): array {
    delete_keys_by_name('TC04-EncKey');
    delete_trackers_by_name('TC04-Tracker');

    $key     = create_encryption_key('TC04-EncKey', 'Test1');
    $tracker = create_encrypted_tracker('TC04-Tracker', 'TC04 E2E test — safe to delete', $key['keyId']);

    return [
        'trackerId' => $tracker['trackerId'],
        'fieldId'   => $tracker['fieldId'],
        'keyId'     => $key['keyId'],
    ];
};
