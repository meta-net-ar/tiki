<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Wiki;

use Tiki\Lib\Test\Api\ApiTestCase;
use TikiLib;

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

        foreach (self::$preferences as $pref => $value) {
            static::setTestPreference($pref, $value);
        }

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
     */
    protected static function createWikiPage(string $name, string $data = 'API test content', string $lang = ''): bool
    {
        $tikilib = TikiLib::lib('tiki');

        if ($tikilib->page_exists($name)) {
            static::$testPages[] = $name;
            return true;
        }

        try {
            $result = $tikilib->create_page(
                $name,
                0,
                $data,
                $tikilib->now,
                '',
                'admin',
                '127.0.0.1',
                '',
                $lang
            );

            if ($result) {
                static::$testPages[] = $name;
                return true;
            }
        } catch (\Exception) {
            // Ignore creation errors — test will be skipped or fail gracefully
        }

        return false;
    }

    /**
     * Remove a wiki page (all versions).
     */
    protected static function removeWikiPage(string $name): void
    {
        try {
            $tikilib = TikiLib::lib('tiki');
            if ($tikilib->page_exists($name)) {
                $tikilib->remove_all_versions($name);
            }
        } catch (\Exception) {
            // Ignore cleanup errors
        }
    }

    protected static function cleanupTestData(): void
    {
        foreach (static::$testPages as $page) {
            static::removeWikiPage($page);
        }
        static::$testPages = [];
        static::$defaultTestPage = null;

        $tikilib = TikiLib::lib('tiki');
        foreach (array_keys(self::$preferences) as $pref) {
            $tikilib->delete_preference($pref);
        }
    }

    protected function getPageResponseSchema(): array
    {
        return [
            'page_id'  => 'int|string',
            'pageName' => 'string',
            'pageSlug' => 'string',
            'hits' => 'int',
            'data' => 'string',
            'description' => 'string',
            'lastModif' => 'int|null',
            'comment' => 'string',
            'version' => 'int',
            'version_minor' => 'int',
            'user' => 'string',
            'ip' => 'string',
            'flag' => 'string',
            'points' => 'int|null',
            'votes' => 'int|null',
            'cache' => 'string|null',
            'wiki_cache' => 'string|null',
            'cache_timestamp' => 'int|null',
            'pageRank' => 'int|null',
            'creator' => 'string',
            'lang' => 'string|null',
            'lockedby' => 'string',
            'is_html' => 'int',
            'created' => 'int',
            'wysiwyg' => 'string|null',
            'wiki_authors_style' => 'string',
            'comments_enabled' => 'string|null',
            'keywords' => 'string|null',
        ];
    }

    /**
     * Top-level schema for GET /wiki (action_pages).
     */
    private function getPageListSchema(): array
    {
        $extraListFields = [
            'perms' => 'array',
            'len' => 'int',
            'versions' => 'int',
        ];
        $pageInfoListSchema = array_merge($this->getPageResponseSchema(), $extraListFields);

        return [
            'data'  => [$pageInfoListSchema],
            'count' => 'int',
        ];
    }

    /**
     * Schema for GET /wiki/page/{page} (action_get_page).
     */
    private function getPageInfoSchema(): array
    {
        $extraFields = [
            'baseName' => 'string',
            'prettyName' => 'string',
            'namespace' => 'bool',
            'namespace_parts' => 'array',
        ];
        $infoResponse = array_merge($this->getPageResponseSchema(), $extraFields);

        return $infoResponse;
    }

    /**
     * Schema for POST /wiki and POST /wiki/page/{page} (action_create_update_page).
     */
    private function getCreateUpdatePageSchema(): array
    {
        return [
            'info' => 'array',
        ];
    }

    /**
     * Schema for endpoints that return Services_Utilities::refresh() in TIKI_API context.
     */
    private function getRefreshFeedbackSchema(): array
    {
        return [
            'feedback' => [
                'action' => [
                    [
                        'type' => 'string',
                        'mes'  => 'string',
                    ]
                ]
            ]
        ];
    }

    /**
     * Schema for PATCH /wiki/page/{page} (action_patch_page).
     */
    private function getPatchPageSchema(): array
    {
        return [
            'status'         => 'string',
            'page'           => 'string',
            'updated_fields' => 'array',
        ];
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
