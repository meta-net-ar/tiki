<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Search;

/**
 * Tests for Search API POST endpoints (rebuild, process_queue)
 *
 * @group api-integration-test
 * @group api-search
 */
class ApiSearchPostTest extends ApiBaseSearchTest
{
    public function testApiSearchRebuildWithoutPermission()
    {
        $response = $this->makeApiRequest('POST', '/search/rebuild', null, ['loggit' => 1]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiSearchRebuild()
    {
        $response = $this->makeApiRequest('POST', '/search/rebuild', 'Admins', ['loggit' => 1]);

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidRebuildResponse($body);

        $this->assertEquals("Rebuild Index", $body['title'], "Title should be 'Rebuild Index'");
    }

    public function testApiSearchProcessQueueWithoutPermission()
    {
        $response = $this->makeApiRequest('POST', '/search/process-queue', null, ['batch' => 10]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiSearchProcessQueue()
    {
        $response = $this->makeApiRequest('POST', '/search/process-queue', 'Admins', ['batch' => 10]);

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidProcessQueueResponse($body);

        $this->assertEquals("Process Update Queue", $body['title'], "Title should be 'Process Update Queue'");
    }
}
