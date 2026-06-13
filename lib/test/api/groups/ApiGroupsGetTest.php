<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Groups;

/**
 * Tests for Groups API GET endpoints
 *
 * @group api-integration-test
 * @group api-groups
 */
class ApiGroupsGetTest extends ApiBaseGroupsTest
{
    public function testApiGetGroupsWithoutPermission()
    {
        $response = $this->makeApiRequest('GET', '/groups');

        $this->assertResponseStatus(403, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiGetGroups()
    {
        $response = $this->makeApiRequest('GET', '/groups', 'Admins');

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidGroupListResponse($body);

        // Verify default test group exists in results
        $groupNames = array_column($body['data'], 'groupName');
        $this->assertContains('API_Test_Group_Default', $groupNames, 'Default test group should exist in results');
    }

    public function testApiGetGroupsWithPagination()
    {
        // Add more groups to ensure we have enough for pagination
        for ($i = 1; $i <= 5; $i++) {
            static::createGroup("API_Test_Group_Paginate_$i", "Test group for pagination $i");
        }

        $data = [
            'offset' => 0,
            'maxRecords' => 3
        ];
        $response = $this->makeApiRequest('GET', '/groups', 'Admins', $data);

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidGroupListResponse($body);
        $this->assertLessThanOrEqual(3, count($body['data']), 'Should return at most 3 groups');
    }

    public function testApiGetGroupsWithSearch()
    {
        // create groups to search for
        static::createGroup('API_Search_Group_One', 'First test search group');
        static::createGroup('API_Search_Group_Two', 'Second test search group');
        $response = $this->makeApiRequest('GET', '/groups', 'Admins', [
            'find' => 'API_Search'
        ]);

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidGroupListResponse($body);
        $this->assertLessThanOrEqual(2, count($body['data']), 'Should return at most 2 groups');

        // Verify all results contain search term
        foreach ($body['data'] as $group) {
            $this->assertStringContainsStringIgnoringCase('API_Search', $group['groupName'], 'All groups should contain search term');
        }
    }

    public function testApiGetGroupsWithInitial()
    {
        // create groups to filter by initial
        static::createGroup('Alpha_Group', 'Group starting with A');
        $response = $this->makeApiRequest('GET', '/groups', 'Admins', [
            'initial' => 'A'
        ]);

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidGroupListResponse($body);
        $this->assertGreaterThanOrEqual(1, count($body['data']), 'Should return at least 1 group');

        // Verify all results start with 'A'
        foreach ($body['data'] as $group) {
            $this->assertEquals('A', strtoupper(substr($group['groupName'], 0, 1)), 'All groups should start with A');
        }
    }
}
