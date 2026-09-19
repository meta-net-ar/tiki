<?php

// TC-01 setup: guarantees one key exists so the dashboard renders its table
// rather than its empty state.
// Output: JSON {keyId}

return function (array $input): array {
    delete_keys_by_name('TC01-EncKey');

    $key = create_encryption_key('TC01-EncKey', 'Test1');

    return ['keyId' => $key['keyId']];
};
