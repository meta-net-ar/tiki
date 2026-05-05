<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Api\OAuth;

use Tiki\Lib\Test\Api\ApiTestCase;

/**
 * Integration tests for OAuth /oauth/public-key endpoint
 *
 * @group api-integration-test
 * @group api-oauth
 */
class ApiOauthPublicKeyTest extends ApiTestCase
{
    public function testApiGetPublicKey()
    {
        $response = $this->makeApiRequest('GET', '/oauth/public-key');

        $this->assertResponseStatus(200, $response);

        $responseData = $this->getResponseBody($response);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('key', $responseData);

        $this->assertNotEmpty($responseData['key']);

        $this->assertStringStartsWith('-----BEGIN PUBLIC KEY-----', $responseData['key']);

        $this->assertStringContainsString('-----END PUBLIC KEY-----', $responseData['key']);
    }
}
