<?php

// Post-install defaults for a freshly provisioned e2e test instance.
// Sets the preferences a scripted `database:install` leaves unset but that an
// interactive install (web installer + admin wizard) would normally handle,
// so the UI behaves like an established instance during tests.
// Idempotent — safe to run repeatedly. Called by bin/e2e-provision.sh.
// Output: JSON {"changed": ["pref", ...]}

// Preferences are written directly, so no admin permissions are needed.
return [
    'admin' => false,
    'run'   => function (array $input): array {
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

        return ['changed' => $changed];
    },
];
