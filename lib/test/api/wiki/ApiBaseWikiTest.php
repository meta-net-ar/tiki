<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Wiki;

use Tiki\Lib\Test\Api\ApiSchemaLoader;
use Tiki\Lib\Test\Api\ApiTestCase;

/**
 * Base class for Wiki API integration tests
 * Provides common setup, teardown, schema definitions, and assertion helpers
 *
 * @group api-integration-test
 */
abstract class ApiBaseWikiTest extends ApiTestCase
{
    /**
     * Page names created during the test run (for cleanup)
     * @var array
     */
    protected static $testPages = [];

    /**
     * Default wiki page available for read tests
     * @var string|null
     */
    protected static $defaultTestPage = null;

    /**
     * Preferences enabled during the test run
     */
    private static $preferences = [
        'feature_wiki'        => 'y',
        'feature_wiki_usrlock' => 'y',
        // Disable unified search: triggers index updates on page creation which fail in
        // the headless subprocess environment when no search index is configured.
        'unified_engine'      => 'n',
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::setTestPreferences(self::$preferences);

        static::createDefaultTestData();
    }

    public static function tearDownAfterClass(): void
    {
        static::cleanupTestData();
        parent::tearDownAfterClass();
    }

    private static function createDefaultTestData(): void
    {
        $uid = substr(md5(uniqid('', true)), 0, 8);
        $pageName = 'ApiTestWikiPage_' . $uid;

        if (static::createWikiPage($pageName, 'API integration test content for page ' . $pageName)) {
            static::$defaultTestPage = $pageName;
        }
    }

    /**
     * Create a wiki page and register it for cleanup.
     * Delegates page-creation I/O to ApiTestCase::createTestWikiPage().
     */
    protected static function createWikiPage(string $name, string $data = 'API test content', string $lang = ''): bool
    {
        $result = static::createTestWikiPage($name, $data, $lang);
        if ($result) {
            static::$testPages[] = $name;
        }
        return $result;
    }

    /**
     * Remove a wiki page (all versions).
     */
    protected static function removeWikiPage(string $name): void
    {
        static::removeTestWikiPage($name);
    }

    protected static function cleanupTestData(): void
    {
        foreach (static::$testPages as $page) {
            static::removeWikiPage($page);
        }
        static::$testPages = [];
        static::$defaultTestPage = null;

        static::deleteTestPreferences(self::$preferences);
    }

    protected function getPageListSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('WikiPageListResponse.yaml');
    }

    protected function getPageInfoSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('WikiPageInfoEntry.yaml');
    }

    protected function getCreateUpdatePageSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('WikiCreateUpdatePageResponse.yaml');
    }

    protected function getRefreshFeedbackSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('RefreshFeedbackResponse.yaml');
    }

    protected function getPatchPageSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('WikiPatchPageResponse.yaml');
    }

    protected function assertValidPageListResponse(array $body): void
    {
        $this->assertMatchesSchema($body, $this->getPageListSchema());
        $this->assertIsArray($body['data'], 'data should be an array of pages');
        $this->assertGreaterThan(0, $body['count'], 'Page list should have at least one page');
    }

    protected function assertValidPageInfoResponse(array $body): void
    {
        $this->assertMatchesSchema($body, $this->getPageInfoSchema());
    }

    protected function assertValidCreateUpdatePageResponse(array $body): void
    {
        $this->assertMatchesSchema($body, $this->getCreateUpdatePageSchema());
        $this->assertIsArray($body['info'], 'info should be an array with page information');
    }

    protected function assertValidRefreshFeedbackResponse(array $body): void
    {
        $this->assertMatchesSchema($body, $this->getRefreshFeedbackSchema());
    }

    protected function assertValidPatchPageResponse(array $body): void
    {
        $this->assertMatchesSchema($body, $this->getPatchPageSchema());
        $this->assertEquals('success', $body['status'], 'Patch should return success status');
    }
}
