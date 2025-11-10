<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Base class for Categories API integration tests
 * Provides shared functionality for category CRUD operations testing
 * 
 * @group api-integration-test
 */
abstract class ApiBaseCategoriesTest extends ApiTestCase
{
    protected static $testCategories = [];
    protected static $defaultCategories = [];

    /**
     * Set up categories feature and default test data
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::cleanupTestCategories();

        // Enable categories feature
        $tikilib = TikiLib::lib('tiki');
        $tikilib->set_preference('feature_categories', 'y');

        static::createDefaultCategories();
    }

    /**
     * Clean up test categories
     */
    public static function tearDownAfterClass(): void
    {
        static::cleanupTestCategories();
        parent::tearDownAfterClass();
    }

    /**
     * Create default test categories that can be used across tests
     */
    protected static function createDefaultCategories()
    {
        $categlib = TikiLib::lib('categ');

        try {
            // Create a root category
            $rootCategId = $categlib->add_category(0, 'Test Root Category', 'Root category for integration tests');
            static::$defaultCategories['root'] = $rootCategId;
            static::$testCategories[] = $rootCategId;

            // Create child categories under root
            $childCategId = $categlib->add_category($rootCategId, 'Test Child Category', 'Child category for integration tests');
            static::$defaultCategories['child'] = $childCategId;
            static::$testCategories[] = $childCategId;

            // Create another root category
            $anotherRootCategId = $categlib->add_category(0, 'Another Test Root', 'Another root category for testing');
            static::$defaultCategories['another_root'] = $anotherRootCategId;
            static::$testCategories[] = $anotherRootCategId;
        } catch (Exception $e) {
            // If we can't create default categories, tests may be skipped
        }
    }

    /**
     * Clean up all test categories
     */
    protected static function cleanupTestCategories()
    {
        $categlib = TikiLib::lib('categ');
        foreach (static::$testCategories as $categId) {
            try {
                if ($category = $categlib->get_category($categId)) {
                    $categlib->remove_category($categId);
                }
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
        }
        static::$testCategories = [];
        static::$defaultCategories = [];
    }

    /**
     * Clean up specific test categories by IDs
     */
    protected static function cleanupTestCategoriesById($categIds)
    {
        $categlib = TikiLib::lib('categ');
        foreach ($categIds as $categId) {
            try {
                if ($category = $categlib->get_category($categId)) {
                    $categlib->remove_category($categId);
                }
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
            // Also remove from tracked test categories
            $index = array_search($categId, static::$testCategories);
            if ($index !== false) {
                unset(static::$testCategories[$index]);
            }
        }
    }

    /**
     * Create a test category for use in tests
     * Returns the category ID if successful, or skips test if no permission
     */
    protected function createTestCategory($name, $parentId = 0)
    {
        $categlib = TikiLib::lib('categ');

        try {
            $categId = $categlib->add_category($parentId, $name, "Test category: {$name}");
            static::$testCategories[] = $categId;
            return $categId;
        } catch (Exception $e) {
            // If we can't create categories, skip tests that need them
            $this->markTestSkipped('Cannot create test categories: ' . $e->getMessage());
        }
    }

    /**
     * Get default category by key
     */
    protected function getDefaultCategory($key)
    {
        if (! isset(static::$defaultCategories[$key])) {
            $this->markTestSkipped("Default category '{$key}' not available");
        }
        return static::$defaultCategories[$key];
    }

    /**
     * Assert that response contains a valid category structure
     */
    protected function assertValidCategoryResponse($category, $message = 'Category response should have valid structure')
    {
        $this->assertIsArray($category, $message);

        // Required fields
        $this->assertArrayHasKey('categId', $category, 'Category should have categId');
        $this->assertArrayHasKey('name', $category, 'Category should have name');
        $this->assertArrayHasKey('description', $category, 'Category should have description');
        $this->assertArrayHasKey('parentId', $category, 'Category should have parentId');
        $this->assertArrayHasKey('rootId', $category, 'Category should have rootId');
        $this->assertArrayHasKey('hits', $category, 'Category should have hits');
        $this->assertArrayHasKey('tplGroupContainerId', $category, 'Category should have tplGroupContainerId');
        $this->assertArrayHasKey('tplGroupPattern', $category, 'Category should have tplGroupPattern');
        $this->assertArrayHasKey('num_roles', $category, 'Category should have num_roles');
        $this->assertArrayHasKey('objects', $category, 'Category should have objects');
        $this->assertArrayHasKey('children', $category, 'Category should have children');
        $this->assertArrayHasKey('descendants', $category, 'Category should have descendants');
        $this->assertArrayHasKey('tepath', $category, 'Category should have tepath');
        $this->assertArrayHasKey('categpath', $category, 'Category should have categpath');
        $this->assertArrayHasKey('relativePathString', $category, 'Category should have relativePathString');

        // Data type validation
        $this->assertIsInt($category['categId'], 'categId should be integer');
        $this->assertIsString($category['name'], 'name should be string');
        $this->assertIsString($category['description'], 'description should be string');
        $this->assertIsInt($category['parentId'], 'parentId should be integer');
        $this->assertIsInt($category['rootId'], 'rootId should be integer');
        $this->assertIsInt($category['hits'], 'hits should be integer');
        $this->assertIsInt($category['tplGroupContainerId'], 'tplGroupContainerId should be integer');
        $this->assertTrue(is_null($category['tplGroupPattern']) || is_string($category['tplGroupPattern']), 'tplGroupPattern should be null or string');
        $this->assertIsInt($category['num_roles'], 'num_roles should be integer');
        $this->assertIsInt($category['objects'], 'objects should be integer');
        $this->assertIsArray($category['children'], 'children should be array');
        $this->assertIsArray($category['descendants'], 'descendants should be array');
        $this->assertIsArray($category['tepath'], 'tepath should be array');
        $this->assertIsString($category['categpath'], 'categpath should be string');
        $this->assertIsString($category['relativePathString'], 'relativePathString should be string');

        $this->assertGreaterThan(0, $category['categId'], 'categId should be positive');
        $this->assertGreaterThanOrEqual(0, $category['parentId'], 'parentId should be non-negative');
        $this->assertNotEmpty($category['name'], 'name should not be empty');
    }

    /**
     * Assert that response contains a valid category list
     */
    protected function assertValidCategoryListResponse($body, $message = 'Category list response should be valid')
    {
        $this->assertIsArray($body, $message);

        foreach ($body as $category) {
            $this->assertValidCategoryResponse($category, 'Each category in list should be valid');
        }
    }

    /**
     * Assert that response contains a valid error structure
     */
    protected function assertValidErrorResponse($body, $expectedCode = null, $expectedMessage = null)
    {
        $this->assertIsArray($body, 'Error response should have valid structure');

        // Required error fields
        $this->assertArrayHasKey('code', $body, 'Error response should have code');
        $this->assertArrayHasKey('message', $body, 'Error response should have message');
        if ($expectedMessage !== null) {
            $this->assertStringContainsString($expectedMessage, $body['message'], 'Error message should be contains expected text');
        }

        // Data type validation
        $this->assertIsInt($body['code'], 'Error code should be integer');
        $this->assertIsString($body['message'], 'Error message should be string');
        $this->assertNotEmpty($body['message'], 'Error message should not be empty');

        // Optional specific code check
        if ($expectedCode !== null) {
            $this->assertEquals($expectedCode, $body['code'], "Error code should be {$expectedCode}");
        }

        if (isset($body['errortitle'])) {
            $this->assertIsString($body['errortitle'], 'Error title should be string');
        }
    }

    /**
     * Assert that response contains valid categorize/uncategorize response
     */
    protected function assertValidCategorizeResponse($body, $expectedCategId, $message = 'Categorize response should be valid')
    {
        $this->assertIsArray($body, $message);

        // Required fields
        $this->assertArrayHasKey('categId', $body, 'Response should have categId');
        $this->assertArrayHasKey('objects', $body, 'Response should have objects array');
        $this->assertArrayHasKey('count', $body, 'Response should have count');

        // Data validation
        $this->assertEquals($expectedCategId, $body['categId'], 'categId should match expected');
        $this->assertIsArray($body['objects'], 'objects should be array');

        // Validate each object in the response
        foreach ($body['objects'] as $object) {
            if ($body['count'] === 'unchanged') {
                $this->assertIsString($object, 'Each object should be a string when count is "unchanged"');
                $this->assertStringContainsString(':', $object, 'Object string should be in "type:id" format');

                list($type, $id) = array_map('trim', explode(':', $object, 2)); // Split into type and id
                $this->assertNotEmpty($type, 'Object type should not be empty');
                $this->assertNotEmpty($id, 'Object id should not be empty');
            } else {
                $this->assertArrayHasKey('type', $object, 'Each object should have type');
                $this->assertIsString($object['type'], 'Object type should be string');
                $this->assertArrayHasKey('id', $object, 'Each object should have id');
                $this->assertArrayHasKey('catObjectId', $object, 'Each object should have catObjectId');
            }
        }

        // Count can be integer (number of objects) or string 'unchanged'
        $this->assertTrue(
            is_int($body['count']) || $body['count'] === 'unchanged',
            'Count should be integer or "unchanged"'
        );
    }
}
