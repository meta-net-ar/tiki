<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Auth;

use TikiDatabaseTestCase;
use TikiDb;
use Tiki\Lib\Auth\ApiToken;
use Tiki\Lib\Auth\ApiTokenException;

/**
 * @group integration
 */
class ApiTokenTest extends TikiDatabaseTestCase
{
    private ApiToken $apiToken;
    private $table;

    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(__DIR__ . '/fixtures/api_tokens_dataset.xml');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiToken = new ApiToken();
        $this->table = TikiDb::get()->table('tiki_api_tokens');
    }

    public function testGeneratedTokenIsRandomAndStoredAsVerifier(): void
    {
        $first = $this->apiToken->createToken(['type' => 'manual', 'user' => 'admin']);
        $second = $this->apiToken->createToken(['type' => 'manual', 'user' => 'admin']);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $first['token']);
        $this->assertNotSame($first['token'], $second['token']);

        $stored = $this->table->fetchFullRow(['tokenId' => $first['tokenId']]);
        $this->assertSame('sha256:' . hash('sha256', $first['token']), $stored['token']);
        $this->assertNotSame($first['token'], $stored['token']);
    }

    public function testValidTokenReturnsMetadataWithoutVerifier(): void
    {
        $created = $this->apiToken->createToken(['type' => 'manual', 'user' => 'admin']);

        $valid = $this->apiToken->validToken($created['token']);

        $this->assertSame('admin', $valid['user']);
        $this->assertArrayNotHasKey('token', $valid);
    }

    public function testStoredVerifierCannotBeUsedAsBearer(): void
    {
        $created = $this->apiToken->createToken(['type' => 'manual', 'user' => 'admin']);
        $stored = $this->table->fetchFullRow(['tokenId' => $created['tokenId']]);

        $this->assertFalse($this->apiToken->validToken($stored['token']));
    }

    public function testLookupAuthenticatesByVerifierWithoutRewritingIt(): void
    {
        // A stored token is always a hashed verifier (created here, or converted
        // from a pre-upgrade plaintext token by the 20260621_hash_api_tokens
        // installer patch).
        $created = $this->apiToken->createToken([
            'type' => 'manual',
            'user' => 'admin',
            'token' => 'legacy-api-token',
        ]);
        $storedBefore = $this->table->fetchFullRow(['tokenId' => $created['tokenId']]);

        $valid = $this->apiToken->validToken('legacy-api-token');
        $this->assertSame('admin', $valid['user']);

        // Looking a token up must never rewrite the stored verifier.
        $storedAfter = $this->table->fetchFullRow(['tokenId' => $created['tokenId']]);
        $this->assertSame($storedBefore['token'], $storedAfter['token']);
    }

    public function testTokenListingsAndLookupDoNotExposeVerifier(): void
    {
        $created = $this->apiToken->createToken(['type' => 'manual', 'user' => 'admin']);

        foreach ($this->apiToken->getTokens() as $token) {
            $this->assertArrayNotHasKey('token', $token);
        }
        $this->assertArrayNotHasKey('token', $this->apiToken->getToken($created['tokenId']));
    }

    public function testExplicitTokenIsHashedAndCanBeRevokedByValue(): void
    {
        $created = $this->apiToken->createToken([
            'type' => 'oauth_access',
            'user' => 'admin',
            'token' => 'provided-oauth-token',
        ]);

        $this->assertSame('provided-oauth-token', $created['token']);
        $this->assertNotFalse($this->apiToken->validToken('provided-oauth-token'));

        $this->apiToken->deleteToken('provided-oauth-token');
        $this->assertFalse($this->apiToken->validToken('provided-oauth-token'));
    }

    public function testVerifierPrefixIsReserved(): void
    {
        $this->expectException(ApiTokenException::class);

        $this->apiToken->createToken([
            'type' => 'manual',
            'user' => 'admin',
            'token' => 'sha256:not-a-bearer',
        ]);
    }

    public function testDeleteAllTokens(): void
    {
        $this->apiToken->createToken(['type' => 'manual', 'user' => 'admin']);

        $this->apiToken->deleteAllTokens();

        $this->assertSame([], $this->apiToken->getTokens());
    }
}
