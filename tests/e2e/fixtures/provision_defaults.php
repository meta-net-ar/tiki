<?php

// Post-install defaults for a freshly provisioned e2e test instance.
// Sets the preferences a scripted `database:install` leaves unset but that an
// interactive install (web installer + admin wizard) would normally handle,
// so the UI behaves like an established instance during tests.
// Idempotent — safe to run repeatedly. Called by bin/e2e-provision.sh.
// Usage: php tests/e2e/fixtures/provision_defaults.php
// Output: JSON {"changed": ["pref", ...]}

error_reporting(0);

if (PHP_SAPI !== 'cli') {
    die;
}

require_once __DIR__ . '/../../../tiki-setup.php';

$defaults = [
    // Keep the admin wizard from auto-opening on first admin login.
    'wizard_admin_hide_on_login' => 'y',
];

$tikilib = TikiLib::lib('tiki');
$changed = [];

foreach ($defaults as $pref => $value) {
    if ($tikilib->get_preference($pref) !== $value) {
        $tikilib->set_preference($pref, $value);
        $changed[] = $pref;
    }
}

echo json_encode(['changed' => $changed]) . "\n";
