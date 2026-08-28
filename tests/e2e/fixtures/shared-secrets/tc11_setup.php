<?php

// TC-11 setup: creates an encryption key assigned to Test1.
// Output: JSON {keyId, share} — share is Test1's share string, needed for regeneration.

return function (array $input): array {
    delete_keys_by_name('TC11-EncKey');

    $key = create_encryption_key('TC11-EncKey', 'Test1');

    return ['keyId' => $key['keyId'], 'share' => $key['shares'][0]];
};
