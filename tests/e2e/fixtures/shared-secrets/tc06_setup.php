<?php

// TC-06 setup: creates encryption key + tracker + encrypted field.
// Output: JSON {trackerId, fieldId, keyId, share}
// share = Test1's share string, needed by the unlock flow tests.

return function (array $input): array {
    delete_keys_by_name('TC06-EncKey');
    delete_trackers_by_name('TC06-Tracker');

    $key     = create_encryption_key('TC06-EncKey', 'Test1');
    $tracker = create_encrypted_tracker('TC06-Tracker', 'TC06 unlock flow test — safe to delete', $key['keyId']);

    return [
        'trackerId' => $tracker['trackerId'],
        'fieldId'   => $tracker['fieldId'],
        'keyId'     => $key['keyId'],
        'share'     => $key['shares'][0],
    ];
};
