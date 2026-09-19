<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_Encryption_ControllerTest extends TikiTestCase
{
    protected $subject;

    protected function setUp(): void
    {
        global $prefs, $user;
        $user = '';
        $prefs['feature_user_encryption'] = 'y';

        $perms = new Perms();
        $perms->setResolverFactories(
            [
                new Perms_ResolverFactory_StaticFactory('root', new Perms_Resolver_Default(true)),
            ]
        );
        Perms::set($perms);

        $this->subject = new Services_Encryption_Controller();
        $this->subject->setUp();
    }

    protected function tearDown(): void
    {
        // Several tests here drive the POST paths of enter_key and the CSRF
        // ticket flow, which dirty request/session superglobals. Reset them so
        // stale state does not leak into later test classes — e.g. a left-over
        // REQUEST_METHOD/ticket broke WikiToolsTest's confirmed broker calls
        // ("No pages were selected") when this class ran before it.
        unset($_SERVER['REQUEST_METHOD'], $_POST['ticket'], $_SESSION['CSRF_TOKEN']);
        parent::tearDown();
    }

    public function testCreateSimpleKey()
    {
        $input = new JitFilter([
            'name' => 'test key',
            'shares' => 1,
            'algo' => 'aes-256-ctr',
        ]);
        $result = $this->subject->action_save_key($input);
        $this->assertGreaterThan(0, $result['keyId']);
        $this->assertEquals(1, count($result['shares']));
        $this->assertNotEmpty($result['shares'][0]);
        TikiLib::lib('tiki')->table('tiki_encryption_keys')->delete(['keyId' => $result['keyId']]);
    }

    public function testFailToCreateKeyWithoutShares()
    {
        $input = new JitFilter([
            'name' => 'test key',
            'shares' => 0,
            'algo' => 'aes-256-ctr',
        ]);
        $this->expectException(Services_Exception_Denied::class);
        $this->expectExceptionMessage('minimum');
        $this->subject->action_save_key($input);
    }

    public function testFailToCreateKeyWithDuplicateName()
    {
        $input = new JitFilter([
            'name' => 'duplicate key',
            'shares' => 1,
            'algo' => 'aes-256-ctr',
        ]);
        $result = $this->subject->action_save_key($input);
        $this->expectException(Services_Exception_DuplicateValue::class);
        try {
            $this->subject->action_save_key($input);
        } finally {
            if (! empty($result['keyId'])) {
                try {
                    TikiLib::lib('tiki')->table('tiki_encryption_keys')->delete(['keyId' => $result['keyId']]);
                } catch (\Throwable $e) {
                    // Swallow cleanup errors — don't mask the expected duplicate-name exception
                }
            }
        }
    }

    public function testCreateKeySharedWithTikiUsers()
    {
        TikiLib::lib('user')->add_user('user1', 'pass1234', 'test1@example.org');
        TikiLib::lib('user')->add_user('user2', 'pass1234', 'test2@example.org');
        $input = new JitFilter([
            'name' => 'test key',
            'users' => 'user1, user2',
            'algo' => 'aes-256-ctr',
        ]);
        $result = $this->subject->action_save_key($input);
        $prefs = TikiLib::lib('tiki')->table('tiki_user_preferences')->fetchAll(
            ['user', 'value'],
            ['prefName' => 'pe.sk.' . $result['keyId']],
            -1,
            -1,
            'user'
        );
        $this->assertEquals(2, count($prefs));
        $this->assertEquals('user1', $prefs[0]['user']);
        $this->assertEquals($result['shares'][0], $prefs[0]['value']);
        $this->assertEquals('user2', $prefs[1]['user']);
        $this->assertEquals($result['shares'][1], $prefs[1]['value']);
        TikiLib::lib('tiki')->table('tiki_encryption_keys')->delete(['keyId' => $result['keyId']]);
        TikiLib::lib('user')->remove_user('user1');
        TikiLib::lib('user')->remove_user('user2');
    }

    public function testEncryptSharedKeyAfterUserLogin()
    {
        $this->shareKeyWithUser(function ($result) {
            $prefs = TikiLib::lib('tiki')->table('tiki_user_preferences')->fetchAll(
                ['user', 'value'],
                ['prefName' => TikiLib::lib('tiki')->table('tiki_user_preferences')->expr('$$ LIKE ?', ['d%.sk.' . $result['keyId']])]
            );
            $this->assertEquals(1, count($prefs));
            $this->assertEquals('user1', $prefs[0]['user']);
            $this->assertNotEquals($result['shares'][0], $prefs[0]['value']);
        });
    }

    public function testRetrieveSharedSecretStoredEncrypted()
    {
        $this->shareKeyWithUser(function ($result) {
            $share = $this->subject->action_get_share_for_key(new JitFilter(['keyId' => $result['keyId']]));
            $this->assertEquals($result['shares'][0], $share);
        });
    }

    public function testRetrieveActualKeyStoredEncrypted()
    {
        $this->shareKeyWithUser(function ($result) {
            // action_save_key no longer returns the raw binary key in its response
            // (it would break json_encode). Verify via round-trip decrypt instead.
            $key = $this->subject->action_decrypt_key(new JitFilter(['keyId' => $result['keyId']]));
            $this->assertNotEmpty($key);
        });
    }

    public function testFailDecryptionWhenMissingSharedKey()
    {
        $this->shareKeyWithUser(function ($result) {
            unset($_SESSION['cryptphrase']);
            $this->expectException(Services_Exception_Denied::class);
            $this->expectExceptionMessage('key not found');
            $this->subject->action_decrypt_key(new JitFilter(['keyId' => $result['keyId']]));
        });
    }

    public function testDecryptWithSuppliedSharedKey()
    {
        $this->shareKeyWithUser(function ($result) {
            // Decrypt once with the session to capture the canonical key value,
            // then verify a manual-share decrypt produces the same result.
            $keyViaSession = $this->subject->action_decrypt_key(new JitFilter(['keyId' => $result['keyId']]));
            unset($_SESSION['cryptphrase']);
            $key = $this->subject->action_decrypt_key(new JitFilter([
                'keyId' => $result['keyId'],
                'existing' => $result['shares'][0],
                'algo' => 'aes-256-ctr',
            ]));
            $this->assertEquals($keyViaSession, $key);
        });
    }

    public function testFailureWithMissingKey()
    {
        $this->expectException(Services_Exception_NotFound::class);
        $this->expectExceptionMessage('Key not found');
        $this->subject->action_decrypt_key(new JitFilter([]));
    }

    public function testGetUsersReturnsUserListForAdmin()
    {
        TikiLib::lib('user')->add_user('alpha_enc_test', 'pass1234', 'alpha_enc@example.org');
        try {
            $result = $this->subject->action_get_users(new JitFilter(['find' => '']));
            $this->assertIsArray($result);
            $this->assertContains('alpha_enc_test', $result);
        } finally {
            TikiLib::lib('user')->remove_user('alpha_enc_test');
        }
    }

    public function testGetUsersDeniedForNonAdmin()
    {
        $perms = new Perms();
        $perms->setResolverFactories([
            new Perms_ResolverFactory_StaticFactory('root', new Perms_Resolver_Default(false)),
        ]);
        Perms::set($perms);

        $this->expectException(Services_Exception_Denied::class);
        $this->subject->action_get_users(new JitFilter(['find' => '']));
    }

    public function testSaveKeyDeniedForNonAdmin()
    {
        $perms = new Perms();
        $perms->setResolverFactories([
            new Perms_ResolverFactory_StaticFactory('root', new Perms_Resolver_Default(false)),
        ]);
        Perms::set($perms);

        $this->expectException(Services_Exception_Denied::class);
        $this->subject->action_save_key(new JitFilter([
            'name' => 'unauthorized key',
            'shares' => 1,
            'algo' => 'aes-256-ctr',
        ]));
    }

    public function testGetKeysDeniedForNonAdmin()
    {
        $perms = new Perms();
        $perms->setResolverFactories([
            new Perms_ResolverFactory_StaticFactory('root', new Perms_Resolver_Default(false)),
        ]);
        Perms::set($perms);

        $this->expectException(Services_Exception_Denied::class);
        $this->subject->action_get_keys();
    }

    public function testDeleteKeyDeniedForNonAdmin()
    {
        $perms = new Perms();
        $perms->setResolverFactories([
            new Perms_ResolverFactory_StaticFactory('root', new Perms_Resolver_Default(false)),
        ]);
        Perms::set($perms);

        $this->expectException(Services_Exception_Denied::class);
        $this->subject->action_delete_key(new JitFilter(['keyId' => 1]));
    }

    public function testGetAlgosDeniedForNonAdmin()
    {
        $perms = new Perms();
        $perms->setResolverFactories([
            new Perms_ResolverFactory_StaticFactory('root', new Perms_Resolver_Default(false)),
        ]);
        Perms::set($perms);

        $this->expectException(Services_Exception_Denied::class);
        $this->subject->action_get_algos(new JitFilter([]));
    }

    /*
     * CSRF ticket enforcement on save_key/delete_key (added via enforceCsrfTicket)
     * is intentionally NOT unit-tested here. Exercising the rejection means calling
     * access->checkCsrf(), whose failure path runs csrfError() -> error_log(), which
     * this Tiki PHPUnit harness intercepts and reports as a test error before the
     * Services_Exception can surface (it also depends on getcookie()/absolute_urls
     * web-bootstrap state absent under CLI). That web-context coupling is why the
     * regression guard lives in the shared-secrets E2E suite (TC-08: anonymous and
     * bogus-ticket POSTs to save_key must not create a key). The unit layer covers
     * the pieces that ARE cleanly testable: the get_ticket endpoint below and, on the
     * front-end, that the ticket is sent (sss-admin Vitest suites).
     */

    public function testGetTicketReturnsTicketForAdmin()
    {
        $response = $this->subject->action_get_ticket();
        $this->assertArrayHasKey('ticket', $response);
        $this->assertNotEmpty($response['ticket']);
    }

    public function testGetTicketDeniedForNonAdmin()
    {
        $perms = new Perms();
        $perms->setResolverFactories([
            new Perms_ResolverFactory_StaticFactory('root', new Perms_Resolver_Default(false)),
        ]);
        Perms::set($perms);

        $this->expectException(Services_Exception_Denied::class);
        $this->subject->action_get_ticket();
    }

    public function testGetKeyResponseExcludesKeyMaterial()
    {
        $this->shareKeyWithUser(function ($result) {
            $response = $this->subject->action_get_key(new JitFilter(['keyId' => $result['keyId']]));
            $this->assertArrayHasKey('key', $response);
            $this->assertEquals($result['keyId'], $response['key']['keyId']);
            $this->assertArrayNotHasKey('secret', $response['key']);
            $this->assertArrayNotHasKey('verificationCanary', $response['key']);
        });
    }

    public function testGetKeyOmitsUserListForNonAdmin()
    {
        $this->shareKeyWithUser(function ($result) {
            // Admin callers (the share-management UI) still receive the
            // resolved share-holder list.
            $adminResponse = $this->subject->action_get_key(new JitFilter(['keyId' => $result['keyId']]));
            $this->assertArrayHasKey('users', $adminResponse['key']);
            $this->assertArrayHasKey('users_array', $adminResponse['key']);

            // Non-admin callers reach this action only to render an encrypted
            // field, and must not learn which users hold a share for the key.
            $perms = new Perms();
            $perms->setResolverFactories([
                new Perms_ResolverFactory_StaticFactory('root', new Perms_Resolver_Default(false)),
            ]);
            Perms::set($perms);

            $response = $this->subject->action_get_key(new JitFilter(['keyId' => $result['keyId']]));
            $this->assertArrayHasKey('key', $response);
            $this->assertEquals($result['keyId'], $response['key']['keyId']);
            $this->assertArrayNotHasKey('users', $response['key']);
            $this->assertArrayNotHasKey('users_array', $response['key']);
        });
    }

    public function testGetKeysResponseExcludesKeyMaterial()
    {
        $this->shareKeyWithUser(function ($result) {
            $keys = $this->subject->action_get_keys();
            $this->assertNotEmpty($keys);
            foreach ($keys as $key) {
                $this->assertArrayNotHasKey('secret', $key);
                $this->assertArrayNotHasKey('verificationCanary', $key);
            }
        });
    }

    public function testMetadataOnlyUpdatePreservesDescriptionAndShares()
    {
        $this->shareKeyWithUser(function ($result) {
            $keys = TikiLib::lib('tiki')->table('tiki_encryption_keys');
            $keys->update(['description' => 'important description'], ['keyId' => $result['keyId']]);

            // A partial update posting only keyId + name + users (no regenerate,
            // no description) must not wipe metadata or stored shares.
            $this->subject->action_save_key(new JitFilter([
                'keyId' => $result['keyId'],
                'name' => 'test key',
                'users' => 'user1',
            ]));

            $row = $keys->fetchFullRow(['keyId' => $result['keyId']]);
            $this->assertEquals('important description', $row['description']);
            $this->assertEquals('aes-256-ctr', $row['algo']);

            $share = $this->subject->action_get_share_for_key(new JitFilter(['keyId' => $result['keyId']]));
            $this->assertEquals($result['shares'][0], $share);
        });
    }

    public function testGetKeyReturnsNullForMissingKey()
    {
        $response = $this->subject->action_get_key(new JitFilter(['keyId' => 999999]));
        $this->assertNull($response['key']);
    }

    public function testGetDecryptedValueThrowsMissingValueWhenParamsAreZero()
    {
        $this->expectException(Services_Exception_MissingValue::class);
        $this->subject->action_get_decrypted_value(new JitFilter(['keyId' => 1, 'fieldId' => 0, 'itemId' => 1]));
    }

    public function testGetDecryptedValueThrowsNotFoundForMissingItem()
    {
        $this->shareKeyWithUser(function ($result) {
            $this->expectException(Services_Exception_NotFound::class);
            $this->subject->action_get_decrypted_value(new JitFilter([
                'keyId'   => $result['keyId'],
                'fieldId' => 1,
                'itemId'  => 999999,
            ]));
        });
    }

    public function testEnterKeyRejectsAWrongShareFromAUserWithNoStoredShare()
    {
        $this->shareKeyWithUser(function ($result) {
            global $user;
            // A holder of a stored share is verified against that stored share,
            // so the submitted value is never consulted. The session path this
            // action exists for is only exercised by a user who holds none.
            $user = 'admin';
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $this->expectException(Services_Exception_FieldError::class);
            $this->subject->action_enter_key(new JitFilter([
                'keyId'      => $result['keyId'],
                'shared_key' => 'not-a-valid-share',
            ]));
        });
    }

    public function testEnterKeyRefusesARequestCarryingNoShare()
    {
        $this->shareKeyWithUser(function ($result) {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $this->expectException(Services_Exception_MissingValue::class);
            $this->subject->action_enter_key(new JitFilter(['keyId' => $result['keyId']]));
        });
    }

    public function testEnterKeyRefusesAGetRequest()
    {
        $this->shareKeyWithUser(function ($result) {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $this->expectException(Services_Exception_MissingValue::class);
            $this->subject->action_enter_key(new JitFilter(['keyId' => $result['keyId']]));
        });
    }

    public function testRegenerationFromASuppliedOldShareReissuesEveryShare()
    {
        $this->shareKeyWithUser(function ($result) {
            global $user;
            // The sss-admin edit form posts `regenerate` without an `old_share`, so
            // it can only regenerate a key the acting user already holds. The
            // supplied-share path stays reachable through the service and is the
            // only way an admin who holds nothing can recover a key.
            $user = 'admin';
            $regenerated = $this->subject->action_save_key(new JitFilter([
                'keyId'      => $result['keyId'],
                'name'       => 'test key',
                'users'      => 'user1',
                'regenerate' => 1,
                'old_share'  => $result['shares'][0],
            ]));

            $this->assertNotEmpty($regenerated['shares']);
            $this->assertNotContains($result['shares'][0], $regenerated['shares']);
        });
    }

    public function testEnterKeySelfHealsStoredShareAfterPasswordChange()
    {
        $this->shareKeyWithUser(function ($result) {
            // Simulate a password change that bypassed the encryption rehash:
            // the session phrase no longer matches the phrase the stored share
            // was encrypted with, so the stored share becomes unreadable.
            $_SESSION['cryptphrase'] = md5('user1' . 'newpass');
            $orphaned = $this->subject->action_get_share_for_key(new JitFilter(['keyId' => $result['keyId']]));
            $this->assertEmpty($orphaned, 'Stored share should be unreadable under the new phrase');

            // Entering the correct share heals the stored copy under the current phrase.
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $this->subject->action_enter_key(new JitFilter([
                'keyId'      => $result['keyId'],
                'shared_key' => $result['shares'][0],
            ]));

            // The stored share is now readable again via the encrypted user store
            // (action_get_share_for_key only reads getUserData, not the session).
            $healed = $this->subject->action_get_share_for_key(new JitFilter(['keyId' => $result['keyId']]));
            $this->assertEquals($result['shares'][0], $healed, 'Stored share should be repaired after entry');

            // And key reconstruction works again without an explicitly supplied share.
            $key = $this->subject->action_decrypt_key(new JitFilter(['keyId' => $result['keyId']]));
            $this->assertNotEmpty($key);
        });
    }

    public function testGetDecryptedValueReturnsPlaintextForAuthorizedUser()
    {
        global $user;
        $plaintext = 'top-secret-value';
        $trklib = TikiLib::lib('trk');

        // A user with a key shared to them, logged in so their share is usable.
        TikiLib::lib('user')->add_user('user1', 'pass1234', 'test1@example.org');
        $keyResult = $this->subject->action_save_key(new JitFilter([
            'name'  => 'decrypted value test key',
            'users' => 'user1',
            'algo'  => 'aes-256-ctr',
        ]));
        $keyId = $keyResult['keyId'];
        $user = 'user1';
        TikiLib::lib('crypt')->onUserLogin('pass1234');

        // A tracker with a text field encrypted by that key.
        $trackerId = $trklib->replace_tracker(null, 'Encryption Test Tracker', '', [], 'n');
        $fieldId = $trklib->replace_tracker_field(
            $trackerId,
            0,
            'Secret',
            't',
            'y',
            'y',
            'y',
            'y',
            'n',
            'n',
            10,
            '',
            '',
            '',
            null,
            '',
            null,
            null,
            'n',
            '',
            '',
            '',
            'secretField',
            null,
            $keyId
        );

        try {
            // Save an item through the normal path; the encrypted field handler
            // stores the value encrypted at rest using the logged-in user's share.
            $definition = Tracker_Definition::get($trackerId);
            $fields = $definition->getFields();
            foreach ($fields as $idx => $f) {
                if ((int) $f['fieldId'] === (int) $fieldId) {
                    $fields[$idx]['value'] = $plaintext;
                }
            }
            $itemId = $trklib->replace_item($trackerId, 0, ['data' => $fields], 'o');

            // Sanity: the value must be encrypted at rest, not stored as plaintext.
            $stored = TikiLib::lib('tiki')->table('tiki_tracker_item_fields')
                ->fetchOne('value', ['itemId' => $itemId, 'fieldId' => $fieldId]);
            $this->assertNotSame($plaintext, $stored, 'Field value should be encrypted at rest');

            // The endpoint must load the item WITH field values (get_tracker_item),
            // not the metadata-only get_item_info, or it would decrypt an empty
            // string and report a decryption failure.
            $result = $this->subject->action_get_decrypted_value(new JitFilter([
                'keyId'   => $keyId,
                'fieldId' => $fieldId,
                'itemId'  => $itemId,
            ]));

            $this->assertArrayNotHasKey('error', $result);
            $this->assertSame($plaintext, $result['value']);
        } finally {
            $trklib->remove_tracker($trackerId);
            TikiLib::lib('tiki')->table('tiki_encryption_keys')->delete(['keyId' => $keyId]);
            TikiLib::lib('user')->remove_user('user1');
            $user = '';
        }
    }

    private function shareKeyWithUser($cb)
    {
        global $user;
        TikiLib::lib('user')->add_user('user1', 'pass1234', 'test1@example.org');
        $input = new JitFilter([
            'name' => 'test key',
            'users' => 'user1',
            'algo' => 'aes-256-ctr',
        ]);
        $result = $this->subject->action_save_key($input);
        $user = 'user1';
        $cryptlib = TikiLib::lib('crypt');
        $cryptlib->onUserLogin('pass1234');
        try {
            $cb($result);
        } finally {
            TikiLib::lib('tiki')->table('tiki_encryption_keys')->delete(['keyId' => $result['keyId']]);
            TikiLib::lib('user')->remove_user('user1');
            $user = '';
        }
    }
}
