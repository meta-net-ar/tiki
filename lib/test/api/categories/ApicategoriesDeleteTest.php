<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Categories;

/**
 * Integration tests for Category DELETE API endpoint
 * Tests: DELETE /categories/{categId}
 *
 * @group api-integration-test
 * @group api-categories
 */
class ApiCategoriesDeleteTest extends ApiBaseCategoriesTest
{
    public function testApiDeleteCategoryWithoutChildren()
    {
        $categId = $this->createTestCategory('New Category');
        $response = $this->makeApiRequest(
            'DELETE',
            '/categories/' . $categId,
            'Admins'
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidCategoryResponse($body);
        $this->assertGreaterThan(0, $body['categId'], 'categId should be positive');
        $this->assertGreaterThanOrEqual(0, $body['parentId'], 'parentId should be non-negative');
        $this->assertNotEmpty($body['name'], 'name should not be empty');

        // Verify the returned data matches what was deleted
        $this->assertEquals($categId, $body['categId']);
        $this->assertEquals('New Category', $body['name']);
        $this->assertEquals('Test category: New Category', $body['description']);

        // Verify the category is actually deleted and not in the list of categories anymore
        $fetchResponse = $this->makeApiRequest(
            'GET',
            '/categories',
            'Admins',
            ['type' => 'all']
        );

        $fetchBody = $this->getResponseBody($fetchResponse);
        $fetchedCategoryIds = array_map('intval', array_column($fetchBody, 'categId'));
        $this->assertNotContains((int) $categId, $fetchedCategoryIds, 'Deleted category should not be in the list of categories');

        // unset test category IDs from static::$testCategories
        $this->cleanupTestCategoriesById([$categId]);
    }

    public function testApiDeleteCategoryWithChildren()
    {
        $parentId = $this->createTestCategory('Parent1 Category');
        $childId = $this->createTestCategory('Child1 Category', $parentId);
        $grandchildId = $this->createTestCategory('Grandchild1 Category', $childId);

        // Delete the parent category (should delete all descendants)
        $response = $this->makeApiRequest(
            'DELETE',
            '/categories/' . $parentId,
            'Admins'
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidCategoryResponse($body);
        $this->assertEquals($parentId, $body['categId']);
        $this->assertEquals('Parent1 Category', $body['name']);
        $this->assertEquals('Test category: Parent1 Category', $body['description']);
        // here the descendants key should conatain an array of all descendants IDs [$childId, $grandchildId]
        $descendantIds = array_map('intval', $body['descendants']);

        // Must include both child and grandchild
        $this->assertContains((int) $childId, $descendantIds, 'Descendants should include the child');
        $this->assertContains((int) $grandchildId, $descendantIds, 'Descendants should include the grandchild');

        // Ensure it contains exactly those descendants (order independent)
        $expected = [(int) $childId, (int) $grandchildId];
        sort($descendantIds);
        sort($expected);
        $this->assertEquals($expected, $descendantIds, 'Descendants should exactly match created descendants');

        // Verify the category is actually deleted and not in the list of categories anymore
        $fetchResponse = $this->makeApiRequest(
            'GET',
            '/categories',
            'Admins',
            ['type' => 'all']
        );

        $fetchBody = $this->getResponseBody($fetchResponse);
        $fetchedCategoryIds = array_map('intval', array_column($fetchBody, 'categId'));
        $this->assertNotContains((int) $parentId, $fetchedCategoryIds, 'Deleted category should not be in the list of categories');

        $this->assertNotContains((int) $childId, $fetchedCategoryIds, 'Deleted category should not be in the list of categories');
        $this->assertNotContains((int) $grandchildId, $fetchedCategoryIds, 'Deleted category should not be in the list of categories');

        // unset test category IDs from static::$testCategories
        $this->cleanupTestCategoriesById([$parentId, $childId, $grandchildId]);
    }

    public function testApiDeleteNonExistentCategory()
    {
        $response = $this->makeApiRequest(
            'DELETE',
            '/categories/99999',
            'Admins'
        );

        $this->assertResponseStatus(404, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiDeleteCategoryWithoutPermission()
    {
        $categId = $this->createTestCategory('Permission Test');
        $response = $this->makeApiRequest(
            'DELETE',
            '/categories/' . $categId,
            null // Anonymous user
        );

        $this->assertResponseStatus(403, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');

        // Verify the category is actually not deleted and in the list of categories anymore
        $fetchResponse = $this->makeApiRequest(
            'GET',
            '/categories',
            'Admins',
            ['type' => 'all']
        );

        $fetchBody = $this->getResponseBody($fetchResponse);
        $fetchedCategoryIds = array_map('intval', array_column($fetchBody, 'categId'));
        $this->assertContains((int) $categId, $fetchedCategoryIds, 'Deleted category should be in the list of categories');

        // unset/delete test category ID
        $this->cleanupTestCategoriesById([$categId]);
    }
}
