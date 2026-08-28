<?php

// Ensures Test1 and Test2 exist with known passwords for the e2e suite.
// Output: JSON {"created":[...], "updated":[...]}
// Run by hand: php tests/e2e/fixtures/bootstrap.php create_test_users.php

// Talks to the database through PDO (db/local.php) and needs no Tiki bootstrap,
// so it also works on an instance that cannot be booted yet.
return [
    'tiki' => false,
    'run'  => function (array $input): array {
        $testUsers = ['Test1' => 'test1pass', 'Test2' => 'test2pass'];
        $pdo       = get_pdo();

        return array_reduce(
            array_keys($testUsers),
            function (array $acc, string $login) use ($pdo, $testUsers): array {
                $status         = upsert_test_user($pdo, $login, $testUsers[$login]);
                $acc[$status][] = $login;
                return $acc;
            },
            ['created' => [], 'updated' => []]
        );
    },
];
