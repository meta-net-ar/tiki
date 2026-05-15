<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Categories;

use TikiLib;

/**
 * Integration tests for Category POST API endpoints
 * Tests: POST /categories, POST /categories/{categId}, POST /categorize, POST /uncategorize
 *
 * @group api-integration-test
 * @group api-categories
 */
class ApiCategoriesPostTest extends ApiBaseCategoriesTest
{
    public function testApiCreateCategoryWithoutParent()
    {
        $uid  = uniqid();
        $data = [
            'name'        => 'POST Test Root Category ' . $uid,
            'description' => 'This is a test root category',
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/categories',
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);

        // Track categId for cleanup
        if (isset($body['categId'])) {
            static::$testCategories[] = $body['categId'];
        }

        $this->assertValidCategoryResponse($body);
        $this->assertEquals($data['name'], $body['name']);
        $this->assertEquals('This is a test root category', $body['description']);
        $this->assertEquals(0, $body['parentId'], 'Root category should have parentId = 0');
        $this->assertEquals(0, $body['objects'], 'New category should have 0 objects');
        $this->assertEmpty($body['children'], 'New category should have no children');
        $this->assertEmpty($body['descendants'], 'New category should have no descendants');
    }

    public function testApiCreateChildCategory()
    {
        $parentId = $this->getDefaultCategory('root'); // Use default root category as parent
        $childData = [
            'parentId' => $parentId,
            'name' => 'POST Child Category',
            'description' => 'Child category description',
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/categories',
            'Admins',
            $childData,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);

        // Track for cleanup
        if (isset($body['categId'])) {
            static::$testCategories[] = $body['categId'];
        }

        $this->assertValidCategoryResponse($body);
        $this->assertEquals('POST Child Category', $body['name']);
        $this->assertEquals('Child category description', $body['description']);
        $this->assertEquals($parentId, $body['parentId']);
        $this->assertEquals($parentId, $body['rootId'], 'Child category rootId should match parent categId');
        $this->assertArrayHasKey($parentId, $body['tepath'], 'tepath should include parent category');
        $this->assertStringContainsString('Test Root Category', $body['categpath'], 'categpath should include parent name');
    }

    public function testApiCreateCategoryWithoutName()
    {
        $data = [
            'description' => 'Category without name',
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/categories',
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(409, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 409, '<!--field[name]-->Field Required');
    }

    public function testApiCreateCategoryWithoutPermission()
    {
        $data = [
            'name' => 'Unauthorized Category',
            'description' => 'Should not be created',
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/categories',
            null, // Anonymous user
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(403, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiUpdateCategory()
    {
        $categId = $this->getDefaultCategory('child'); // Use default test category
        $updateData = [
            'name' => 'Updated Test Child Category',
            'description' => 'Updated description',
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/categories/' . $categId,
            'Admins',
            $updateData,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);

        $this->assertValidCategoryResponse($body);
        $this->assertEquals($categId, $body['categId']);
        $this->assertEquals('Updated Test Child Category', $body['name']);
        $this->assertEquals('Updated description', $body['description']);
    }

    public function testApiUpdateCategoryParent()
    {
        $parent2Id = $this->getDefaultCategory('another_root');
        $childId = $this->getDefaultCategory('child');
        $updateData = [
            'parentId' => $parent2Id,
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/categories/' . $childId,
            'Admins',
            $updateData,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);

        $this->assertValidCategoryResponse($body);
        $this->assertEquals($childId, $body['categId']);
        $this->assertEquals($parent2Id, $body['parentId']);
        $this->assertEquals($parent2Id, $body['rootId'], 'rootId should update to new parent');
        $this->assertStringContainsString('Another Test Root', $body['categpath'], 'categpath should reflect new parent');
    }

    public function testApiUpdateNonExistentCategory()
    {
        $updateData = [
            'name' => 'Should fail',
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/categories/99999',
            'Admins',
            $updateData,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(404, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiCategorizeWikiPage()
    {
        $categId = $this->getDefaultCategory('child'); // Use default test category
        $tikilib = TikiLib::lib('tiki');
        $tikilib->create_page('TestWikiPage', 0, 'Test content for categorization', time(), 'API Test');
        $categorizeData = [
            'categId' => $categId,
            'objects' => ['wiki page:TestWikiPage'], // Format: "Type:ID"
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/categorize',
            'Admins',
            $categorizeData,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidCategorizeResponse($body);
        $this->assertCount(1, $body['objects'], 'Should have exactly 1 categorized object');
        $this->assertEquals($categId, $body['categId'], 'categId should match expected');
        $this->assertEquals('wiki page', $body['objects'][0]['type']);
        $this->assertEquals('TestWikiPage', $body['objects'][0]['id']);

        // cleanup created page
        TikiLib::lib('tiki')->remove_all_versions('TestWikiPage');
    }

    public function testApiCategorizeMultipleObjects()
    {
        $categId = $this->getDefaultCategory('child');

        // Create multiple wiki pages
        $pages = ['Page1', 'Page2', 'Page3'];
        $tikilib = TikiLib::lib('tiki');
        foreach ($pages as $pageName) {
            $tikilib->create_page($pageName, 0, 'Test content for categorization', time(), 'API Test');
        }

        // Categorize all pages using "Type:ID" format
        $objectStrings = array_map(function ($page) {
            return "wiki page:$page"; // Format: "Type:ID"
        }, $pages);

        $categorizeData = [
            'categId' => $categId,
            'objects' => $objectStrings,
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/categorize',
            'Admins',
            $categorizeData,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidCategorizeResponse($body);
        $this->assertCount(3, $body['objects'], 'Should have exactly 3 categorized objects');
        $this->assertIsInt($body['count'], 'Count should be integer for successful categorization');

        // cleanup created pages
        foreach ($pages as $pageName) {
            TikiLib::lib('tiki')->remove_all_versions($pageName);
        }
    }

    public function testApiCategorizeAlreadyCategorizedObject()
    {
        $categId = $this->getDefaultCategory('child'); // Use default test category
        $tikilib = TikiLib::lib('tiki');
        $tikilib->create_page('DuplicatePage', 0, 'Test content', time(), 'API Test');

        // Categorize the page first time
        $categorizeData = [
            'categId' => $categId,
            'objects' => ['wiki page:DuplicatePage'],
        ];

        $firstResponse = $this->makeApiRequest(
            'POST',
            '/categorize',
            'Admins',
            $categorizeData,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $firstResponse);

        // Try to categorize the same page again
        $secondResponse = $this->makeApiRequest(
            'POST',
            '/categorize',
            'Admins',
            $categorizeData,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $secondResponse);

        $body = $this->getResponseBody($secondResponse);
        $this->assertValidCategorizeResponse($body);
        $this->assertEquals('unchanged', $body['count'], 'Count should be "unchanged" for already categorized object');

        // cleanup created page
        TikiLib::lib('tiki')->remove_all_versions('DuplicatePage');
    }

    public function testApiCategorizeWithoutPermission()
    {
        $categId = $this->getDefaultCategory('child'); // Use default test category
        $categorizeData = [
            'categId' => $categId,
            'objects' => ['wiki page:SomePage'],
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/categorize',
            null, // Anonymous user
            $categorizeData,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(403, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiCategorizeWithInvalidObjectFormat()
    {
        $categId = $this->getDefaultCategory('child'); // Use default test category

        // Valid format should be "Type:ID" like "wiki page:HomePage" or "trackeritem:1"
        $categorizeData = [
            'categId' => $categId,
            'objects' => ['invalidformat'], // Missing colon separator
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/categorize',
            'Admins',
            $categorizeData,
            'application/x-www-form-urlencoded'
        );

        // Should return success but with no objects processed
        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertArrayHasKey('categId', $body, 'Response should contain objects key');
        $this->assertArrayHasKey('objects', $body, 'Response should contain objects key');
        $this->assertEquals($categId, $body['categId'], 'Objects should contain the original invalid string');
        $this->assertEmpty($body['objects'], 'No objects should be processed for invalid format');
        $this->assertEquals("unchanged", $body['count'], 'Count should be "unchanged" for invalid object format');
    }

    public function testApiUncategorizeWikiPage()
    {
        $categId = $this->getDefaultCategory('child'); // Use default test category
        $tikilib = TikiLib::lib('tiki');
        $tikilib->create_page('UncategorizePage', 0, 'Test content', time(), 'API Test');

        $this->makeApiRequest(
            'POST',
            '/categorize',
            'Admins',
            [
                'categId' => $categId,
                'objects' => ['wiki page:UncategorizePage'], // Format: "Type:ID"
            ],
            'application/x-www-form-urlencoded'
        );

        // Now uncategorize the page using same "Type:ID" format
        $uncategorizeData = [
            'categId' => $categId,
            'objects' => ['wiki page:UncategorizePage'], // Format: "Type:ID"
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/uncategorize',
            'Admins',
            $uncategorizeData,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidCategorizeResponse($body);
        $this->assertCount(1, $body['objects'], 'Should have exactly 1 uncategorized object');
        $this->assertEquals('wiki page', $body['objects'][0]['type']);
        $this->assertEquals('UncategorizePage', $body['objects'][0]['id']);
        $this->assertIsInt($body['count'], 'Count should be integer for successful uncategorization');

        // cleanup created page
        TikiLib::lib('tiki')->remove_all_versions('UncategorizePage');
    }

    public function testApiUncategorizeMultipleObjects()
    {
        $categId = $this->getDefaultCategory('child'); // Use default test category

        // Create and categorize multiple wiki pages
        $pages = ['UnPage1', 'UnPage2', 'UnPage3'];
        $tikilib = TikiLib::lib('tiki');
        foreach ($pages as $pageName) {
            $tikilib->create_page($pageName, 0, "Content for $pageName", time(), 'API Test');
        }

        // Build object strings using "Type:ID" format
        $objectStrings = array_map(function ($page) {
            return "wiki page:$page"; // Format: "Type:ID"
        }, $pages);

        $this->makeApiRequest(
            'POST',
            '/categorize',
            'Admins',
            [
                'categId' => $categId,
                'objects' => $objectStrings,
            ],
            'application/x-www-form-urlencoded'
        );

        // Now uncategorize all pages using same "Type:ID" format
        $uncategorizeData = [
            'categId' => $categId,
            'objects' => $objectStrings, // Format: ["Type:ID", "Type:ID", ...]
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/uncategorize',
            'Admins',
            $uncategorizeData,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidCategorizeResponse($body);
        $this->assertCount(3, $body['objects'], 'Should have exactly 3 uncategorized objects');
        $this->assertIsInt($body['count'], 'Count should be integer for successful uncategorization');

        // cleanup created pages
        foreach ($pages as $pageName) {
            TikiLib::lib('tiki')->remove_all_versions($pageName);
        }
    }

    public function testApiUncategorizeNotCategorizedObject()
    {
        $categId = $this->getDefaultCategory('child'); // Use default test category

        // Create a wiki page but don't categorize it
        $tikilib = TikiLib::lib('tiki');
        $tikilib->create_page('NotCategorizedPage', 0, 'Test content', time(), 'API Test');

        // Try to uncategorize a page that's not in the category
        $uncategorizeData = [
            'categId' => $categId,
            'objects' => ['wiki page:NotCategorizedPage'],
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/uncategorize',
            'Admins',
            $uncategorizeData,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidCategorizeResponse($body);
        $this->assertEquals('unchanged', $body['count'], 'Count should be "unchanged" for non-categorized object');

        // cleanup created page
        TikiLib::lib('tiki')->remove_all_versions('NotCategorizedPage');
    }

    public function testApiUncategorizeWithoutPermission()
    {
        $categId = $this->getDefaultCategory('child'); // Use default test category

        // Try to uncategorize without permission
        $uncategorizeData = [
            'categId' => $categId,
            'objects' => ['wiki page:SomePage'],
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/uncategorize',
            null, // Anonymous user
            $uncategorizeData,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(403, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }
}
