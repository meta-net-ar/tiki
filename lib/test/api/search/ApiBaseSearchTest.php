<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Search;

use Tiki\Lib\Test\Api\ApiTestCase;
use TikiLib;
use Exception;
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
     * Preferences to enable required features
     * @var array
     */
    private static $preferences = [
        'feature_search' => 'y',
        'unified_engine' => 'mysql',
    ];

    /**
     * Setup before class - enable features and create default test content
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        foreach (self::$preferences as $pref => $value) {
            static::setTestPreference($pref, $value);
        }

        static::createDefaultTestContent();
        static::rebuildSearchIndex();
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
     * Create a wiki page for testing
     * @param string $pageName Page name
     * @param string $content Page content
     * @return string|null The page name or null on failure
     */
    protected static function createWikiPage($pageName, $content)
    {
        $tikilib = TikiLib::lib('tiki');

        // Check if page already exists
        if ($tikilib->page_exists($pageName)) {
            static::$testPages[] = $pageName;
            return $pageName;
        }

        // Create page
        $tikilib->create_page($pageName, 0, $content, time(), 'Created by API test', 'admin');

        static::$testPages[] = $pageName;
        return $pageName;
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
        $tikilib = TikiLib::lib('tiki');

        // Remove wiki pages
        foreach (static::$testPages as $pageName) {
            try {
                if ($tikilib->page_exists($pageName)) {
                    $tikilib->remove_all_versions($pageName);
                }
            } catch (Exception $e) {
                // Ignore errors during cleanup
            }
        }

        // Disable preferences set for tests
        foreach (self::$preferences as $pref => $value) {
            $tikilib->delete_preference($pref);
        }
    }

    private function getSearchRebuildResponseSchema()
    {
        return [
            'title' => 'string',
            'formattedStats' => 'string|null',
            'search_engine' => 'string',
            'search_version' => 'string',
            'search_index' => 'string',
            'fallback_search_engine' => 'string',
            'fallback_search_version' => 'string',
            'fallback_search_index' => 'string',
            'queue_count' => 'int',
            'log_file_browser' => 'string',
            'fallback_log_file_browser' => 'string',
            'log_file_console' => 'string',
            'fallback_log_file_console' => 'string',
            'lastLogItemWeb' => 'string',
            'lastLogItemConsole' => 'string',
            'isAjax' => 'bool',
            'showForm' => 'bool',
            'unified_last_rebuild' => 'int',
        ];
    }

    private function getSearchProcessQueueResponseSchema()
    {
        return [
            'title' => 'string',
            "stat" => 'string|null',
            'queue_count' => 'int',
            'batch' => 'int',
        ];
    }

    private function getSearchLookupResponseSchema()
    {
        return [
            'title' => 'string',
            'resultset' => [
                'count' => 'int',
                'offset' => 'int',
                'maxRecords' => 'int',
                'result' => [
                    [
                        'object_type' => 'string',
                        'object_id' => 'string|int',
                        'parent_id' => 'int|string|null',
                        'title' => 'string',
                    ]
                ],
            ],
        ];
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
}
