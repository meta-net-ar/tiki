<?php

// Maintenance: wipes every TC-prefixed test encryption key and tracker from the DB.
// Use it to recover after a killed run in existing-instance mode.
// Run by hand: php tests/e2e/fixtures/bootstrap.php clean_baseline.php
// Output: JSON {"keys_deleted":N,"trackers_deleted":N}

return function (array $input): array {
    $enclib = TikiLib::lib('encryption');
    $keys   = array_filter($enclib->get_keys(), fn($k) => str_starts_with((string) $k['name'], 'TC'));
    array_walk($keys, fn($k) => $enclib->delete_key($k['keyId']));

    $tikilib    = TikiLib::lib('tiki');
    $trackerIds = array_column(
        $tikilib->fetchAll("SELECT trackerId FROM tiki_trackers WHERE name LIKE 'TC%'"),
        'trackerId'
    );
    array_walk($trackerIds, fn($id) => delete_tracker_by_id((int) $id));

    return ['keys_deleted' => count($keys), 'trackers_deleted' => count($trackerIds)];
};
