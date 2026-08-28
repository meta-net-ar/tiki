<?php

// TC-06 teardown: removes the tracker and key created by tc06_setup.php.
// Input: that setup's JSON output, handed back by common/fixtures.ts.

return function (array $input): array {
    $trackerId = (int) ($input['trackerId'] ?? 0);
    $keyId     = (int) ($input['keyId'] ?? 0);

    if (! $trackerId || ! $keyId) {
        throw new RuntimeException('missing trackerId/keyId in setup output');
    }

    delete_tracker_by_id($trackerId);
    delete_key_by_id($keyId);

    return ['success' => true, 'trackerId' => $trackerId, 'keyId' => $keyId];
};
