<?php

// Wipes TC-prefixed test encryption keys and trackers from the DB.
// Run before dump_baseline.php to ensure the baseline starts clean.
// Usage: ddev exec php /var/www/html/tests/e2e/fixtures/clean_baseline.php
// Outputs JSON: {"keys_deleted":N,"trackers_deleted":N}

error_reporting(0);

if (PHP_SAPI !== 'cli') {
    die;
}

require_once __DIR__ . '/../../../tiki-setup.php';
require_once __DIR__ . '/helpers.php';

bootstrap_admin();

$enclib = TikiLib::lib('encryption');
$keys   = array_filter($enclib->get_keys(), fn($k) => str_starts_with((string) $k['name'], 'TC'));
array_walk($keys, fn($k) => $enclib->delete_key($k['keyId']));
$keysDeleted = count($keys);

$tikilib    = TikiLib::lib('tiki');
$trackerIds = array_column(
    $tikilib->fetchAll("SELECT trackerId FROM tiki_trackers WHERE name LIKE 'TC%'"),
    'trackerId'
);
array_walk($trackerIds, fn($id) => delete_tracker_by_id((int) $id));
$trackersDeleted = count($trackerIds);

echo json_encode(['keys_deleted' => $keysDeleted, 'trackers_deleted' => $trackersDeleted]) . "\n";
