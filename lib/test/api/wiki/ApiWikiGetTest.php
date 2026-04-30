<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Wiki;

/**
 * Tests for Wiki API GET endpoints
 *
 * Covers:
 *   GET /wiki              — list all accessible wiki pages
 *   GET /wiki/page/{page}  — retrieve a single wiki page
 *
 * @group api-integration-test
 * @group api-wiki
 */
class ApiWikiGetTest extends ApiBaseWikiTest
{
    public function testApiListPages()
    {
        $response = $this->makeApiRequest('GET', '/wiki', 'Admins');

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidPageListResponse($body);

        $pageNames = array_column($body['data'], 'pageName');
        $this->assertContains(static::$defaultTestPage, $pageNames, 'Default test page should appear in the page list');
    }

    public function testApiListPagesAsAnonymous()
    {
        $response = $this->makeApiRequest('GET', '/wiki', null);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        // Anonymous users can view wiki pages by default in Tiki
        $this->assertValidPageListResponse($body);
    }

    public function testApiListPagesWithSearchFilter()
    {
        $pageName = static::$defaultTestPage;
        $this->assertNotNull($pageName, 'Default test page must exist');

        $response = $this->makeApiRequest('GET', '/wiki', 'Admins', [
            'find'       => $pageName,
            'exactMatch' => 'true',
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidPageListResponse($body);
        $this->assertEquals(1, $body['count'], 'Exact match search should return exactly one page');
        $this->assertEquals($pageName, $body['data'][0]['pageName'], 'Returned page should match the search query');
    }

    public function testApiListPagesOnlyCount()
    {
        $response = $this->makeApiRequest('GET', '/wiki', 'Admins', [
            'onlyCount' => 'y',
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $this->assertArrayHasKey('count', $body, 'onlyCount response should have a count key');
        $this->assertIsInt($body['count'], 'count should be an integer');
        $this->assertGreaterThan(0, $body['count'], 'Page count should be positive');
        $this->assertArrayHasKey('data', $body, 'onlyCount response should still have a data key');
        $this->assertEmpty($body['data'], 'onlyCount response should have an empty data array');
    }

    public function testApiGetPage()
    {
        $pageName = static::$defaultTestPage;
        $this->assertNotNull($pageName, 'Default test page must exist');

        $response = $this->makeApiRequest('GET', "/wiki/page/{$pageName}", 'Admins');

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidPageInfoResponse($body);
        $this->assertEquals($pageName, $body['pageName'], 'Response should reflect the requested page name');
    }

    public function testApiGetPageAsAnonymous()
    {
        $pageName = static::$defaultTestPage;
        $this->assertNotNull($pageName, 'Default test page must exist');

        $response = $this->makeApiRequest('GET', "/wiki/page/{$pageName}", null);

        // Anonymous users can view publicly accessible wiki pages
        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidPageInfoResponse($body);
        $this->assertEquals($pageName, $body['pageName'], 'Response should reflect the requested page name');
    }

    public function testApiGetPageNonExistent()
    {
        $pageName = 'ThisPageDoesNotExist_' . uniqid();
        $response = $this->makeApiRequest('GET', "/wiki/page/{$pageName}", 'Admins');

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, "Page \"{$pageName}\" not found (404)");
    }
}
