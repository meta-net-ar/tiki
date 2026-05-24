<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Search;

use Tiki\Lib\Test\Api\ApiSchemaLoader;
use Tiki\Lib\Test\Api\ApiTestCase;
use Tiki\Search\SearchIndexRebuilder;

/**
 * Base class for Search API integration tests
 * Provides common setup, teardown, and helper methods
 *
 * @group api-integration-test
 */
abstract class ApiBaseSearchTest extends ApiTestCase
{
    /**
     * Array to track test wiki pages for cleanup
     * @var array
     */
    protected static $testPages = [];

    /**
     * Default test content created for testing
     * @var array
     */
    protected static $defaultContent = [];

    /**
     * Guard for lazy one-time test-data creation.
     */
    private static bool $testDataCreated = false;

    /**
     * Preferences to enable required features
     * @var array
     */
    private static $preferences = [
        'feature_search' => 'y',
        'unified_engine' => 'mysql',
    ];

    /**
     * Defers createDefaultTestContent() and rebuildSearchIndex() to setUp() because
     * SearchIndexRebuilder calls set_preference(), which loads fgal.php and executes
     * Table ORM queries that throw NoTestCaseObjectOnCallStackException in PHPUnit 10+.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::setTestPreferences(self::$preferences);

        // Sync to the in-process $prefs global so ORM calls in lazy setUp() do not
        // attempt to reach search services that are absent in the CI environment.
        global $prefs;
        foreach (self::$preferences as $name => $value) {
            $prefs[$name] = $value;
        }
        self::$testDataCreated = false;
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (! self::$testDataCreated) {
            static::createDefaultTestContent();
            static::rebuildSearchIndex();
            self::$testDataCreated = true;
        }
    }

    /**
     * Teardown after class - cleanup all test data
     */
    public static function tearDownAfterClass(): void
    {
        static::cleanupTestData();
        parent::tearDownAfterClass();
    }

    /**
     * Create default test content for all tests
     */
    protected static function createDefaultTestContent()
    {
        $page1 = static::createWikiPage('API_Test_Search_Page_1', 'This is test page one with unique content');
        if ($page1) {
            static::$defaultContent['page1'] = $page1;
        }

        $page2 = static::createWikiPage('API_Test_Search_Page_2', 'This is test page two with different content');
        if ($page2) {
            static::$defaultContent['page2'] = $page2;
        }

        $page3 = static::createWikiPage('API_Test_Findme', 'Special searchable content for testing');
        if ($page3) {
            static::$defaultContent['findme'] = $page3;
        }
    }

    /**
     * Create a wiki page for testing and register it for cleanup.
     * Returns the page name on success, null on failure.
     */
    protected static function createWikiPage(string $pageName, string $content = 'API test content'): ?string
    {
        $result = static::createTestWikiPage($pageName, $content);
        if ($result) {
            static::$testPages[] = $pageName;
            return $pageName;
        }
        return null;
    }

    /**
     * Rebuild the search index
     */
    protected static function rebuildSearchIndex()
    {
        $searchIndexRebuilder = new SearchIndexRebuilder();
        $searchIndexRebuilder->executeOrQueueIndexRebuild();
    }

    /**
     * Cleanup all test data
     */
    protected static function cleanupTestData()
    {
        foreach (static::$testPages as $pageName) {
            static::removeTestWikiPage($pageName);
        }
        static::$testPages = [];

        static::deleteTestPreferences(self::$preferences);
    }

    protected function getSearchRebuildResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('SearchRebuildResponse.yaml');
    }

    protected function getSearchProcessQueueResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('SearchProcessQueueResponse.yaml');
    }

    protected function getSearchLookupNoResultsResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('SearchLookupNoResultsResponse.yaml');
    }

    protected function getSearchLookupResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('SearchLookupResponse.yaml');
    }

    protected function assertValidRebuildResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getSearchRebuildResponseSchema());
    }

    protected function assertValidProcessQueueResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getSearchProcessQueueResponseSchema());
    }

    protected function assertValidSearchResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getSearchLookupResponseSchema());
    }

    protected function assertValidSearchNoResultsResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getSearchLookupNoResultsResponseSchema());
    }
}
