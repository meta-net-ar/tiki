<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * @group api-integration
 */
class VersionTikiTest extends ApiTestCase
{
    private $lib;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lib = TikiLib::lib('version');
    }

    public function testGetVersion()
    {
        $response = $this->makeHttpRequest('GET', '/version');
        $responseData = json_decode($response->getBody(), true);
        $version = $this->lib->getVersion();

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertNotEmpty($responseData);

        $this->assertNotEmpty($responseData['version']);

        $this->assertArrayHasKey('version', $responseData);

        $this->assertEquals($version, $responseData['version']);
    }
}
