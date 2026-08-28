<?php

// Persists the "User Encryption" security feature to the DB so the Shared Secrets
// suite can reach the encryption admin tab without a manual admin toggle.
// Idempotent — safe to run on every test invocation (called from global-setup.ts).
// Output: JSON {"feature_user_encryption":"y","changed":true|false}

// 'admin' => false on purpose: bootstrap_admin() seeds
// $prefs['feature_user_encryption'] = 'y' in memory, which would mask the real DB
// value read below. tiki-setup.php already loads $prefs from the DB, and
// set_preference() persists the pref without needing admin permissions.
return [
    'admin' => false,
    'run'   => function (array $input): array {
        $tikilib = TikiLib::lib('tiki');
        $before  = $tikilib->get_preference('feature_user_encryption', 'n');

        if ($before !== 'y') {
            $tikilib->set_preference('feature_user_encryption', 'y');
        }

        return [
            'feature_user_encryption' => 'y',
            'changed'                 => $before !== 'y',
        ];
    },
];
