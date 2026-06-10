<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Api;

/**
 * Integration tests for /version endpoint
 *
 * @group api-integration-test
 * @group api-version
 */
class ApiTikiVersionTest extends ApiTestCase
{
    private $lib;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lib = \TikiLib::lib('version');
    }

    public function testApiGetTikiVersion()
    {
        $response = $this->makeApiRequest('GET', '/version');
        $responseData = $this->getResponseBody($response);
        $expectedVersion = $this->lib->getVersion();

        $this->assertResponseStatus(200, $response);
        $this->assertArrayHasKey('version', $responseData);
        $this->assertEquals($expectedVersion, $responseData['version']);
    }

    public function testApiGetTikiVersionResponseFormat()
    {
        $response = $this->makeApiRequest('GET', '/version');
        $responseData = $this->getResponseBody($response);

        $this->assertResponseStatus(200, $response);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('version', $responseData);
        $this->assertIsString($responseData['version']);

        // Version should follow semantic versioning pattern or be a recognizable format
        $this->assertMatchesRegularExpression('/^[\d\.]+/', $responseData['version'], 'Version should start with numbers');
    }

    public function testApiGetTikiVersionHttpHeaders()
    {
        $response = $this->makeApiRequest('GET', '/version');

        $this->assertResponseStatus(200, $response);

        // Content-Type should be set for JSON response
        if (isset($response['headers']['Content-Type'])) {
            $this->assertStringContainsString('application/json', $response['headers']['Content-Type']);
        }
    }
}
