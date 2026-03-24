<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Test\Mcp\Tools;

use Mcp\Exception\ToolCallException;
use Tiki\Mcp\Tools\WikiTools;

/**
 * Integration tests for WikiTools MCP tools.
 *
 * Runs against a real Tiki database — requires lib/test/local.php
 * with valid DB credentials.
 *
 * @group mcp
 */
class WikiToolsTest extends \TikiTestCase
{
    private static WikiTools $tools;
    private static string $testPagePrefix = 'McpTest_';

    /** Pages created during tests, cleaned up in tearDownAfterClass */
    private static array $createdPages = [];

    private static ?\Perms_Context $permContext = null;
    private static ?string $oldUser = null;
    private static ?string $oldFeatureSearch = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Define TIKI_API if not already set -- needed for broker calls
        // (CSRF bypass and isActionPost checks reference this constant)
        if (! defined('TIKI_API')) {
            define('TIKI_API', true);
        }

        // Set $_SERVER defaults that TikiLib methods and broker expect
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/test';
        $_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'POST';

        // Save original global state
        global $user, $prefs;
        self::$oldUser = $user;
        self::$oldFeatureSearch = $prefs['feature_search'] ?? null;

        // Set global user context (mirrors mcp.php bootstrap)
        $user = 'admin';
        self::$permContext = new \Perms_Context('admin');

        // Ensure wiki feature is enabled (WikiTools constructor checks this)
        $prefs['feature_wiki'] = 'y';

        // Disable unified search so wiki_search uses list_pages fallback.
        // Unified search index won't have just-created test pages.
        $prefs['feature_search'] = 'n';

        self::$tools = new WikiTools('admin');
    }

    public static function tearDownAfterClass(): void
    {
        // Clean up all test pages
        $tikilib = \TikiLib::lib('tiki');
        foreach (self::$createdPages as $page) {
            if ($tikilib->page_exists($page)) {
                $tikilib->remove_all_versions($page);
            }
        }

        // Restore global state
        global $user, $prefs;
        $user = self::$oldUser;
        if (self::$oldFeatureSearch !== null) {
            $prefs['feature_search'] = self::$oldFeatureSearch;
        } else {
            unset($prefs['feature_search']);
        }

        // Release permission context
        self::$permContext = null;

        parent::tearDownAfterClass();
    }

    private function uniquePageName(string $suffix = ''): string
    {
        $name = self::$testPagePrefix . uniqid() . ($suffix ? '_' . $suffix : '');
        self::$createdPages[] = $name;
        return $name;
    }

    // ---------------------------------------------------------------
    // wiki_create_page
    // ---------------------------------------------------------------

    public function testCreatePage(): void
    {
        $page = $this->uniquePageName('create');
        $result = self::$tools->createPage($page, '!Test Content', 'test create', 'A test page');

        $this->assertSame($page, $result['pageName']);
        $this->assertSame(1, $result['version']);
        $this->assertTrue($result['created']);
    }

    public function testCreatePageDuplicateFails(): void
    {
        $page = $this->uniquePageName('dup');
        self::$tools->createPage($page, 'First version');

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('already exists');
        self::$tools->createPage($page, 'Duplicate');
    }

    public function testCreatePageEmptyNameFails(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('cannot be empty');
        self::$tools->createPage('', 'content');
    }

    public function testCreatePageNameTooLongFails(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('158');
        self::$tools->createPage(str_repeat('A', 159), 'content');
    }

    // ---------------------------------------------------------------
    // wiki_get_page
    // ---------------------------------------------------------------

    public function testGetPage(): void
    {
        $page = $this->uniquePageName('get');
        self::$tools->createPage($page, '!Hello World', 'create comment', 'desc');

        $result = self::$tools->getPage($page);

        $this->assertSame($page, $result['pageName']);
        $this->assertSame('!Hello World', $result['data']);
        $this->assertSame('admin', $result['user']);
        $this->assertSame('admin', $result['creator']);
        $this->assertSame(1, $result['version']);
        $this->assertSame('create comment', $result['comment']);
        $this->assertFalse($result['is_html']);
        $this->assertIsInt($result['lastModif']);
        $this->assertGreaterThan(0, $result['lastModif']);
    }

    public function testGetPageNotFoundFails(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('not found');
        self::$tools->getPage('NonExistentPage_' . uniqid());
    }

    // ---------------------------------------------------------------
    // wiki_update_page
    // ---------------------------------------------------------------

    public function testUpdatePage(): void
    {
        $page = $this->uniquePageName('update');
        self::$tools->createPage($page, 'Original content');

        $result = self::$tools->updatePage($page, 'Updated content', 'updated it');

        $this->assertSame($page, $result['pageName']);
        $this->assertSame(2, $result['version']);
        $this->assertTrue($result['updated']);

        // Verify content changed
        $fetched = self::$tools->getPage($page);
        $this->assertSame('Updated content', $fetched['data']);
    }

    public function testUpdatePageNotFoundFails(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Not found');
        self::$tools->updatePage('NonExistentPage_' . uniqid(), 'content');
    }

    // ---------------------------------------------------------------
    // wiki_delete_page
    // ---------------------------------------------------------------

    public function testDeletePage(): void
    {
        $page = $this->uniquePageName('delete');
        self::$tools->createPage($page, 'To be deleted');

        $result = self::$tools->deletePage($page);

        $this->assertSame($page, $result['pageName']);
        $this->assertTrue($result['deleted']);

        // Verify page is gone
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('not found');
        self::$tools->getPage($page);
    }

    public function testDeletePageNotFoundFails(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('not found');
        self::$tools->deletePage('NonExistentPage_' . uniqid());
    }

    // ---------------------------------------------------------------
    // wiki_list_pages
    // ---------------------------------------------------------------

    public function testListPages(): void
    {
        $page = $this->uniquePageName('list');
        self::$tools->createPage($page, 'List test content');

        $result = self::$tools->listPages();

        $this->assertArrayHasKey('pages', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('offset', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertGreaterThan(0, $result['total']);

        // Our page should be in the list
        $pageNames = array_column($result['pages'], 'pageName');
        $this->assertContains($page, $pageNames);
    }

    public function testListPagesWithSearch(): void
    {
        $unique = uniqid('srch');
        $page = self::$testPagePrefix . $unique;
        self::$createdPages[] = $page;
        self::$tools->createPage($page, 'Searchable content');

        $result = self::$tools->listPages(search: $unique);

        $pageNames = array_column($result['pages'], 'pageName');
        $this->assertContains($page, $pageNames);
    }

    public function testListPagesPagination(): void
    {
        $result = self::$tools->listPages(offset: 0, limit: 2);

        $this->assertSame(0, $result['offset']);
        $this->assertSame(2, $result['limit']);
        $this->assertLessThanOrEqual(2, count($result['pages']));
    }

    public function testListPagesLimitClamped(): void
    {
        $result = self::$tools->listPages(limit: 999);
        $this->assertSame(100, $result['limit']);

        $result = self::$tools->listPages(limit: -5);
        $this->assertSame(1, $result['limit']);
    }

    // ---------------------------------------------------------------
    // wiki_get_page_history
    // ---------------------------------------------------------------

    public function testGetPageHistory(): void
    {
        $page = $this->uniquePageName('hist');
        self::$tools->createPage($page, 'Version 1', 'first');
        self::$tools->updatePage($page, 'Version 2', 'second');
        self::$tools->updatePage($page, 'Version 3', 'third');

        $result = self::$tools->getPageHistory($page);

        $this->assertArrayHasKey('versions', $result);
        $this->assertSame(3, $result['total']);
        $this->assertGreaterThanOrEqual(2, count($result['versions']));

        // All three versions should be present
        $versions = array_column($result['versions'], 'version');
        $this->assertContains(1, $versions);
        $this->assertContains(2, $versions);
        $this->assertContains(3, $versions);
    }

    public function testGetPageHistoryNotFoundFails(): void
    {
        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('not found');
        self::$tools->getPageHistory('NonExistentPage_' . uniqid());
    }

    // ---------------------------------------------------------------
    // wiki_search
    // ---------------------------------------------------------------

    public function testSearch(): void
    {
        $unique = uniqid('find');
        $page = self::$testPagePrefix . $unique;
        self::$createdPages[] = $page;
        self::$tools->createPage($page, "Content with $unique keyword");

        $result = self::$tools->search($unique);

        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('total', $result);

        $pageNames = array_column($result['results'], 'pageName');
        $this->assertContains($page, $pageNames);
    }

    public function testSearchLimitClamped(): void
    {
        $result = self::$tools->search('test', limit: 999);
        // Limit should be clamped to 50 internally; verify result structure
        $this->assertArrayHasKey('results', $result);
        $this->assertLessThanOrEqual(50, count($result['results']));
    }

    // ---------------------------------------------------------------
    // Full CRUD cycle
    // ---------------------------------------------------------------

    public function testFullCrudCycle(): void
    {
        $page = $this->uniquePageName('crud');

        // Create
        $create = self::$tools->createPage($page, '!Initial', 'created');
        $this->assertTrue($create['created']);
        $this->assertSame(1, $create['version']);

        // Read
        $read = self::$tools->getPage($page);
        $this->assertSame('!Initial', $read['data']);

        // Update
        $update = self::$tools->updatePage($page, '!Updated', 'modified');
        $this->assertTrue($update['updated']);
        $this->assertSame(2, $update['version']);

        // Verify update
        $read2 = self::$tools->getPage($page);
        $this->assertSame('!Updated', $read2['data']);

        // History shows both versions
        $hist = self::$tools->getPageHistory($page);
        $this->assertSame(2, $hist['total']);

        // List includes our page
        $list = self::$tools->listPages(search: $page);
        $pageNames = array_column($list['pages'], 'pageName');
        $this->assertContains($page, $pageNames);

        // Delete
        $del = self::$tools->deletePage($page);
        $this->assertTrue($del['deleted']);

        // Verify gone
        $this->expectException(ToolCallException::class);
        self::$tools->getPage($page);
    }
}
