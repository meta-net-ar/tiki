<?php

// Teardown: remove every key this spec's journey may have left behind.
// Input: that setup's JSON output, handed back by common/fixtures.ts.

return function (array $input): array {
    $prefix = (string) ($input['prefix'] ?? '');

    if ($prefix === '') {
        throw new RuntimeException('missing prefix in setup output');
    }

    return ['keys_deleted' => delete_keys_by_prefix($prefix)];
};
