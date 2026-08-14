<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Search;

/**
 * Tests for Search API GET endpoint
 *
 * @group api-integration-test
 * @group api-search
 */
class ApiSearchGetTest extends ApiBaseSearchTest
{
    public function testApiSearchLookup()
    {
        $data = [
            'filter' => ['content' => 'unique content'],
            'format' => '{object_id} {title}',
        ];
        $response = $this->makeApiRequest('GET', '/search/lookup', 'Admins', $data);

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidSearchResponse($body);

        $this->assertEquals("Lookup Result", $body['title'], 'Title should be "Lookup Result"');
        $this->assertEquals(1, $body['resultset']['count'], 'Should find 1 matching record');
        $this->assertEquals('wiki page', $body['resultset']['result'][0]['object_type'], 'object_type should be wiki page');
        $this->assertEquals("API_Test_Search_Page_1", $body['resultset']['result'][0]['object_id'], 'object_id should match test wiki page ID');
        $this->assertEquals("API_Test_Search_Page_1 API_Test_Search_Page_1", $body['resultset']['result'][0]['title'], 'Title result should match');
    }

    public function testApiSearchLookupByTitle()
    {
        $data = [
            'filter' => ['title' => 'API_Test_Findme']
        ];
        $response = $this->makeApiRequest('GET', '/search/lookup', 'Admins', $data);

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidSearchResponse($body);

        $this->assertEquals("Lookup Result", $body['title'], 'Title should be "Lookup Result"');
        $this->assertEquals(1, $body['resultset']['count'], 'Should find 1 matching record');
        $this->assertEquals('wiki page', $body['resultset']['result'][0]['object_type'], 'object_type should be wiki page');
        $this->assertEquals("API_Test_Findme", $body['resultset']['result'][0]['object_id'], 'object_id should match test wiki page ID');
    }

    public function testApiSearchLookupWithPagination()
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->createWikiPage("API_Test_Pagination_Page_$i", "Content for pagination test page $i");
        }
        // Rebuild search index to include new pages
        static::rebuildSearchIndex();

        $data = [
            'filter' => ['content' => 'Content for pagination test page'],
            'offset' => 0,
            'maxRecords' => 2
        ];
        $response = $this->makeApiRequest('GET', '/search/lookup', 'Admins', $data);

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidSearchResponse($body);
        $this->assertEquals(5, $body['resultset']['count'], 'Count should be 5 for pagination test');
        $this->assertCount(2, $body['resultset']['result'], 'Should return 2 records for maxRecords=2');
        $this->assertEquals(2, $body['resultset']['maxRecords'], 'maxRecords should be 2');
        $this->assertEquals("API_Test_Pagination_Page_1", $body['resultset']['result'][0]['object_id'], 'First result should be Page 1');
        $this->assertEquals("API_Test_Pagination_Page_2", $body['resultset']['result'][1]['object_id'], 'Second result should be Page 2');
    }

    public function testApiSearchLookupWithSorting()
    {
        $titles = ['Delta', 'Alpha', 'Echo', 'Charlie', 'Bravo'];
        foreach ($titles as $title) {
            $this->createWikiPage("{$title}_API_Test_Sort", "Content for pagination test page with title $title");
        }
        // Rebuild search index to include new pages
        static::rebuildSearchIndex();

        $data = [
            'filter' => ['content' => 'Content for pagination test page'],
            'sort_order' => 'title_asc',
            'maxRecords' => 4
        ];
        $response = $this->makeApiRequest('GET', '/search/lookup', 'Admins', $data);

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidSearchResponse($body);

        // Verify results are sorted
        if (count($body['resultset']['result']) > 1) {
            $titles = array_column($body['resultset']['result'], 'title');
            $sortedTitles = $titles;
            sort($sortedTitles, SORT_NATURAL | SORT_FLAG_CASE);
            $this->assertEquals($sortedTitles, $titles, 'Results should be sorted by title ascending');
        }
    }

    public function testApiSearchLookupNonExistentTitle()
    {
        $data = [
            'filter' => ['title' => 'NonExistentPageThatDoesNotExist12345']
        ];
        $response = $this->makeApiRequest('GET', '/search/lookup', 'Admins', $data);

        $this->assertResponseStatus(200, $response, 'GET /search/lookup with no results should return 200');

        $body = $this->getResponseBody($response);
        $this->assertValidSearchNoResultsResponse($body);

        $this->assertEquals(0, $body['resultset']['count'], 'Should find 0 matching records');
        $this->assertEmpty($body['resultset']['result'], 'Result set should be empty');
        $this->assertEquals("Lookup Result", $body['title'], 'Title should be "Lookup Result"');
    }

    public function testApiSearchLookupWithoutFilters()
    {
        $response = $this->makeApiRequest('GET', '/search/lookup', 'Admins', ['maxRecords' => 5]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidSearchResponse($body);

        $this->assertEquals("Lookup Result", $body['title'], 'Title should be "Lookup Result"');
        $this->assertGreaterThanOrEqual(1, $body['resultset']['count'], 'Should find at least 1 matching record');
        $this->assertNotEmpty($body['resultset']['result'], 'Result set should not be empty');
    }
}
