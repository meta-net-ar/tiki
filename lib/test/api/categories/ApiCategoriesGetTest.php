<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Categories;

use TikiLib;

/**
 * Integration tests for Categories API GET endpoints
 *
 * @group api-integration-test
 * @group api-categories
 */
class ApiCategoriesGetTest extends ApiBaseCategoriesTest
{
    public function testApiListCategoriesWithoutPermission()
    {
        $response = $this->makeApiRequest('GET', '/categories');

        $this->assertResponseStatus(403, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiListAllCategories()
    {
        $queryParams = ['type' => 'all'];
        $response = $this->makeApiRequest('GET', '/categories', 'Admins', $queryParams);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidCategoryListResponse($body);
    }

    public function testApiListRootCategories()
    {
        $queryParams = ['type' => 'roots'];
        $response = $this->makeApiRequest('GET', '/categories', 'Admins', $queryParams);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidCategoryListResponse($body);
        foreach ($body as $category) {
            $this->assertEquals(0, $category['parentId'], 'Root categories should have parentId = 0');
        }

        // Should include the default root categories
        $rootCategoryIds = array_map('intval', array_column($body, 'categId'));
        $this->assertContains((int) $this->getDefaultCategory('root'), $rootCategoryIds, 'Default root category should be in roots list');
        $this->assertContains((int) $this->getDefaultCategory('another_root'), $rootCategoryIds, 'Another root category should be in roots list');

        $this->assertNotContains((int) $this->getDefaultCategory('child'), $rootCategoryIds, 'Child category should not be in roots list');
    }

    public function testApiListCategoriesByParentId()
    {
        $parentCategId = $this->getDefaultCategory('root');

        $queryParams = ['parentId' => $parentCategId, 'type' => 'children'];
        $response = $this->makeApiRequest('GET', '/categories', 'Admins', $queryParams);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidCategoryListResponse($body);

        // All returned categories should have the specified parentId
        foreach ($body as $category) {
            $this->assertEquals($parentCategId, $category['parentId'], 'Child categories should have correct parentId');
        }

        // Should contain the default child category
        $childCategoryIds = array_map('intval', array_column($body, 'categId'));
        $this->assertContains((int) $this->getDefaultCategory('child'), $childCategoryIds, 'Default child category should be in children list');

        // Verify child category details
        foreach ($body as $category) {
            if ($category['categId'] == $this->getDefaultCategory('child')) {
                $this->assertEquals('Test Child Category', $category['name']);
                $this->assertEquals($parentCategId, $category['parentId']);
            }
        }
    }

    public function testApiListCategoriesByNonExistentParentId()
    {
        $nonExistentParentId = 999999999;

        $queryParams = ['parentId' => $nonExistentParentId, 'type' => 'children'];
        $response = $this->makeApiRequest('GET', '/categories', 'Admins', $queryParams);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $this->assertIsArray($body, 'Should return array even for non-existent parent');
        $this->assertEmpty($body, 'Should return empty array for non-existent parent');
    }

    public function testApiListCategoriesMissingParentIdForChildren()
    {
        $queryParams = ['type' => 'children']; // Missing parentId
        $response = $this->makeApiRequest('GET', '/categories', 'Admins', $queryParams);

        $this->assertResponseStatus(409, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidErrorResponse($body, 409, '<!--field[parentId]-->Field Required');
    }

    public function testApiListCategoriesWithInvalidParentId()
    {
        $queryParams = ['parentId' => 'not_a_number', 'type' => 'children'];
        $response = $this->makeApiRequest('GET', '/categories', 'Admins', $queryParams);

        $this->assertResponseStatus(409, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidErrorResponse($body, 409, '<!--field[parentId]-->Field Required');
    }

    public function testApiListCategoriesWhenFeatureDisabled()
    {
        $tikilib = TikiLib::lib('tiki');
        $originalValue = $tikilib->get_preference('feature_categories');
        $tikilib->set_preference('feature_categories', 'n');

        try {
            $response = $this->makeApiRequest('GET', '/categories', 'Admins');

            $this->assertResponseStatus(403, $response);
            $body = $this->getResponseBody($response);

            $this->assertValidErrorResponse($body, 403, 'Feature disabled: feature_categories');
        } finally {
            // Restore original value
            $tikilib->set_preference('feature_categories', $originalValue);
        }
    }
}
