<?php

// Shared functional helpers for e2e fixtures.
// Does NOT require tiki-setup.php — callers that need it must require it first.

if (PHP_SAPI !== 'cli') {
    die;
}

// ── Bootstrap ─────────────────────────────────────────────────────────────────

function bootstrap_admin(): void
{
    global $prefs, $user;
    $user = 'admin';
    $prefs['feature_user_encryption'] = 'y';

    $perms = new Perms();
    $perms->setResolverFactories([
        new Perms_ResolverFactory_StaticFactory('root', new Perms_Resolver_Default(true)),
    ]);
    Perms::set($perms);
}

// ── Key operations ────────────────────────────────────────────────────────────

function delete_keys_by_name(string $name): int
{
    $enclib  = TikiLib::lib('encryption');
    $matches = array_filter($enclib->get_keys(), fn($k) => $k['name'] === $name);
    array_walk($matches, fn($k) => $enclib->delete_key($k['keyId']));
    return count($matches);
}

function delete_keys_by_prefix(string $prefix): int
{
    $enclib  = TikiLib::lib('encryption');
    $matches = array_filter($enclib->get_keys(), fn($k) => str_starts_with((string) $k['name'], $prefix));
    array_walk($matches, fn($k) => $enclib->delete_key($k['keyId']));
    return count($matches);
}

function delete_key_by_id(int $keyId): void
{
    TikiLib::lib('encryption')->delete_key($keyId);
}

function create_encryption_key(string $name, string $user, string $algo = 'aes-256-ctr'): array
{
    $ctrl = new Services_Encryption_Controller();
    $ctrl->setUp();
    return $ctrl->action_save_key(new JitFilter([
        'name'  => $name,
        'users' => $user,
        'algo'  => $algo,
    ]));
}

// ── Tracker operations ────────────────────────────────────────────────────────

function delete_trackers_by_name(string $name): int
{
    $tikilib = TikiLib::lib('tiki');
    $rows    = $tikilib->fetchAll('SELECT trackerId FROM tiki_trackers WHERE name = ?', [$name]);
    array_walk($rows, fn($t) => delete_tracker_by_id((int) $t['trackerId']));
    return count($rows);
}

function delete_tracker_by_id(int $trackerId): void
{
    $trklib  = TikiLib::lib('trk');
    $tikilib = TikiLib::lib('tiki');
    $items   = $tikilib->fetchAll('SELECT itemId FROM tiki_tracker_items WHERE trackerId = ?', [$trackerId]);
    array_walk($items, fn($row) => $trklib->remove_tracker_item((int) $row['itemId']));
    $trklib->remove_tracker($trackerId);
}

function create_encrypted_tracker(string $name, string $description, int $keyId): array
{
    $trklib  = TikiLib::lib('trk');
    $userlib = TikiLib::lib('user');

    $trackerId = $trklib->replace_tracker(0, $name, $description, '', 'n');

    $perms = ['tiki_p_view_trackers', 'tiki_p_create_tracker_items'];
    array_walk(
        $perms,
        fn($perm) => $userlib->assign_object_permission('Registered', (string) $trackerId, 'tracker', $perm)
    );

    $fieldId = $trklib->replace_tracker_field(
        trackerId:       $trackerId,
        fieldId:         0,
        name:            'SecretData',
        type:            't',
        isMain:          'y',
        isSearchable:    'y',
        isTblVisible:    'y',
        isPublic:        'y',
        isHidden:        'n',
        isMandatory:     'n',
        position:        10,
        options:         '',
        encryptionKeyId: $keyId,
    );

    return ['trackerId' => $trackerId, 'fieldId' => $fieldId];
}

// ── PDO + user helpers (no Tiki bootstrap needed) ─────────────────────────────

function get_pdo(): PDO
{
    $localPhp = __DIR__ . '/../../../db/local.php';
    if (! file_exists($localPhp)) {
        exit(1);
    }
    require_once $localPhp;

    return new PDO(
        "mysql:host={$host_tiki};dbname={$dbs_tiki};charset=utf8mb4",
        $user_tiki,
        $pass_tiki,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

function upsert_test_user(PDO $pdo, string $login, string $pass): string
{
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $now  = time();

    $stmt = $pdo->prepare('SELECT userId FROM users_users WHERE BINARY login = ?');
    $stmt->execute([$login]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $uid = (int) $row['userId'];
        $pdo->prepare('UPDATE users_users SET hash = ?, pass_confirm = ? WHERE userId = ?')
            ->execute([$hash, $now, $uid]);
        // Ensure Registered membership even for a pre-existing user (insert path guarantees it too).
        $pdo->prepare('INSERT IGNORE INTO users_usergroups (userId, groupName, created) VALUES (?, ?, ?)')
            ->execute([$uid, 'Registered', $now]);
        return 'updated';
    }

    $pdo->prepare(
        'INSERT INTO users_users (login, hash, pass_confirm, email_confirm, registrationDate, created) VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([$login, $hash, $now, $now, $now, $now]);

    $uid = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT IGNORE INTO users_usergroups (userId, groupName, created) VALUES (?, ?, ?)')
        ->execute([$uid, 'Registered', $now]);

    return 'created';
}
