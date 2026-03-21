<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Test\Mcp\Auth;

use Tiki\Mcp\Auth\ApiTokenAuthProvider;
use Tiki\Mcp\Auth\AuthenticationException;

/**
 * Integration tests for ApiTokenAuthProvider.
 *
 * Tests against real tiki_api_tokens table.
 *
 * @group mcp
 */
class ApiTokenAuthProviderTest extends \TikiTestCase
{
    private static ?array $testToken = null;
    private static ?array $expiredToken = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/test';

        $apiTokenLib = \TikiLib::lib('api_token');

        // Create a valid test token
        self::$testToken = $apiTokenLib->createToken([
            'type' => 'manual',
            'user' => 'admin',
            'label' => 'MCP Auth Test',
        ]);

        // Create an expired token
        self::$expiredToken = $apiTokenLib->createToken([
            'type' => 'manual',
            'user' => 'admin',
            'label' => 'MCP Auth Test Expired',
            'expireAfter' => time() - 3600, // expired 1 hour ago
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        // Clean up test tokens
        if (self::$testToken) {
            $table = \TikiDb::get()->table('tiki_api_tokens');
            $table->delete(['tokenId' => self::$testToken['tokenId']]);
        }
        if (self::$expiredToken) {
            $table = \TikiDb::get()->table('tiki_api_tokens');
            $table->delete(['tokenId' => self::$expiredToken['tokenId']]);
        }

        parent::tearDownAfterClass();
    }

    public function testValidTokenReturnsUser(): void
    {
        $provider = new ApiTokenAuthProvider();
        $user = $provider->authenticate([
            'authorization' => 'Bearer ' . self::$testToken['token'],
        ]);

        $this->assertSame('admin', $user);
    }

    public function testNoAuthHeaderThrows(): void
    {
        $provider = new ApiTokenAuthProvider();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Authorization header is required');
        $provider->authenticate(['authorization' => '']);
    }

    public function testMissingAuthKeyThrows(): void
    {
        $provider = new ApiTokenAuthProvider();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Authorization header is required');
        $provider->authenticate([]);
    }

    public function testInvalidTokenThrows(): void
    {
        $provider = new ApiTokenAuthProvider();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid or expired');
        $provider->authenticate([
            'authorization' => 'Bearer invalidtoken12345',
        ]);
    }

    public function testExpiredTokenThrows(): void
    {
        $provider = new ApiTokenAuthProvider();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid or expired');
        $provider->authenticate([
            'authorization' => 'Bearer ' . self::$expiredToken['token'],
        ]);
    }

    public function testMalformedAuthHeaderThrows(): void
    {
        $provider = new ApiTokenAuthProvider();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid Authorization header');
        $provider->authenticate([
            'authorization' => 'Basic dXNlcjpwYXNz',
        ]);
    }

    public function testEmptyBearerTokenThrows(): void
    {
        $provider = new ApiTokenAuthProvider();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Empty Bearer token');
        $provider->authenticate([
            'authorization' => 'Bearer ',
        ]);
    }

    public function testTokenHitIsTracked(): void
    {
        $provider = new ApiTokenAuthProvider();

        // Get initial hits
        $tokenBefore = \TikiLib::lib('api_token')->validToken(self::$testToken['token']);
        $hitsBefore = (int) $tokenBefore['hits'];

        $provider->authenticate([
            'authorization' => 'Bearer ' . self::$testToken['token'],
        ]);

        // Verify hits incremented
        $tokenAfter = \TikiLib::lib('api_token')->validToken(self::$testToken['token']);
        $this->assertSame($hitsBefore + 1, (int) $tokenAfter['hits']);
    }
}
