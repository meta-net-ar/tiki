<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class OauthPublicKeyTest extends TikiTestCase
{
    public function testGetPublicKey()
    {        
        $response = $this->makeRequest('GET', '/oauth/public-key');
        $responseData = json_decode($response->getBody(), true);
                
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertArrayHasKey('key', $responseData);
        
        $this->assertNotEmpty($responseData['key']);
        
        $this->assertStringStartsWith('-----BEGIN PUBLIC KEY-----', $responseData['key']);
 
        $this->assertStringEndsWith('-----END PUBLIC KEY-----', $responseData['key']);
    }
}
