<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Categories;

use Tiki\Lib\Test\Api\ApiSchemaLoader;
use Tiki\Lib\Test\Api\ApiTestCase;
use TikiLib;
use Exception;

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

    private static array $preferences = ['feature_categories' => 'y'];

    /**
     * Set up categories feature and default test data
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::cleanupTestCategories();
        static::setTestPreferences(self::$preferences);
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
        } catch (Exception) {
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
                if ($categlib->get_category($categId)) {
                    $categlib->remove_category($categId);
                }
            } catch (Exception) {
                // Ignore cleanup errors
            }
        }
        static::$testCategories = [];
        static::$defaultCategories = [];

        static::deleteTestPreferences(self::$preferences);
    }

    /**
     * Clean up specific test categories by IDs
     */
    protected static function cleanupTestCategoriesById($categIds)
    {
        $categlib = TikiLib::lib('categ');
        foreach ($categIds as $categId) {
            try {
                if ($categlib->get_category($categId)) {
                    $categlib->remove_category($categId);
                }
            } catch (Exception) {
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

    protected function getCategoryResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('Category.yaml');
    }

    protected function getCategorizeResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('CategorizeResponse.yaml');
    }

    protected function assertValidCategoryResponse($category)
    {
        $this->assertMatchesSchema($category, $this->getCategoryResponseSchema());
    }

    protected function assertValidCategoryListResponse($body)
    {
        foreach ($body as $category) {
            $this->assertMatchesSchema($category, $this->getCategoryResponseSchema());
        }
    }

    protected function assertValidCategorizeResponse($body)
    {
        // Count can be integer (number of objects) or string 'unchanged'
        $this->assertTrue(
            is_int($body['count']) || $body['count'] === 'unchanged',
            'Count should be integer or "unchanged"'
        );

        if (is_int($body['count'])) {
            $this->assertMatchesSchema($body, $this->getCategorizeResponseSchema());
        } elseif ($body['count'] === 'unchanged') {
            $this->assertArrayHasKey('categId', $body, 'Response should have categId');
            $this->assertIsInt($body['categId'], 'categId should be integer');
            $this->assertArrayHasKey('objects', $body, 'Response should have objects');
            $this->assertIsArray($body['objects'], 'Objects should be an array');

            foreach ($body['objects'] as $object) {
                $type = $object['type'] ?? '';
                $id = $object['id'] ?? '';
                $this->assertNotEmpty($type, 'Object type should not be empty');
                $this->assertNotEmpty($id, 'Object id should not be empty');
            }
        }
    }
}
