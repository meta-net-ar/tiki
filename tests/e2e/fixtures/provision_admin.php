<?php

// Sets the admin password on a freshly provisioned e2e test instance.
// Also stamps pass_confirm: a clean install leaves it NULL, which forces an
// interactive password change at first login and would break automated login
// (same approach as upsert_test_user() in helpers.php).
// Called by bin/e2e-provision.sh with {"password": "..."} as input.
// Output: JSON {"admin":"updated"}

// PDO only: a freshly installed instance is not worth booting for one UPDATE.
return [
    'tiki' => false,
    'run'  => function (array $input): array {
        $password = (string) ($input['password'] ?? '');

        if ($password === '') {
            throw new RuntimeException('missing password in input');
        }

        get_pdo()
            ->prepare('UPDATE users_users SET hash = ?, pass_confirm = ? WHERE login = ?')
            ->execute([password_hash($password, PASSWORD_BCRYPT), time(), 'admin']);

        return ['admin' => 'updated'];
    },
];
