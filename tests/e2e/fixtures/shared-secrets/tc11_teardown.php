<?php

// TC-11 teardown: removes the key created by tc11_setup.php.
// Input: that setup's JSON output, handed back by common/fixtures.ts.

return function (array $input): array {
    $keyId = (int) ($input['keyId'] ?? 0);

    if (! $keyId) {
        throw new RuntimeException('missing keyId in setup output');
    }

    delete_key_by_id($keyId);

    return ['success' => true, 'keyId' => $keyId];
};
