<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Wiki;

use TikiLib;

/**
 * Tests for Wiki API POST and PATCH endpoints
 *
 * Covers:
 *   POST /wiki                      — create a new wiki page
 *   POST /wiki/page/{page}          — update an existing wiki page
 *   POST /wiki/delete               — delete one or more wiki pages (all versions)
 *   POST /wiki/lock                 — lock one or more wiki pages
 *   POST /wiki/unlock               — unlock one or more wiki pages
 *   POST /wiki/page/{page}/delete   — delete specific versions of a wiki page
 *   PATCH /wiki/page/{page}         — partially update a wiki page (SEO fields, categories, tags)
 *
 * @group api-integration-test
 * @group api-wiki
 */
class ApiWikiPostTest extends ApiBaseWikiTest
{
    public function testApiCreatePage()
    {
        $uid      = uniqid();
        $pageName = 'ApiCreate_' . $uid;
        static::$testPages[] = $pageName;

        $response = $this->makeApiRequest('POST', '/wiki', 'Admins', [
            'pageName' => $pageName,
            'data'     => 'Content created by API integration test.',
        ], 'application/x-www-form-urlencoded');

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidCreateUpdatePageResponse($body);
        $this->assertEquals($pageName, $body['info']['pageName'], 'Created page name should match');

        // Verify the page was actually created in the database
        $tikilib = TikiLib::lib('tiki');
        $this->assertTrue((bool) $tikilib->page_exists($pageName), 'Created page should exist in the database');
    }

    public function testApiCreatePageWithoutPermission()
    {
        $uid      = uniqid();
        $pageName = 'ApiCreate_NoPerm_' . $uid;

        $response = $this->makeApiRequest('POST', '/wiki', null, [
            'pageName' => $pageName,
            'data'     => 'This page should not be created.',
        ], 'application/x-www-form-urlencoded');

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');

        // Verify the page was NOT created
        $tikilib = TikiLib::lib('tiki');
        $this->assertFalse((bool) $tikilib->page_exists($pageName), 'Page should not have been created without permission');
    }

    public function testApiCreatePageMissingName()
    {
        $response = $this->makeApiRequest('POST', '/wiki', 'Admins', [
            'data' => 'Content without a page name.',
        ], 'application/x-www-form-urlencoded');

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, null, 'Page name is required.');
    }

    public function testApiUpdatePage()
    {
        $pageName = static::$defaultTestPage;
        $this->assertNotNull($pageName, 'Default test page must exist');

        $updatedContent = 'Content updated by API integration test at ' . time();

        $response = $this->makeApiRequest('POST', "/wiki/page/{$pageName}", 'Admins', [
            'data'    => $updatedContent,
            'comment' => 'Updated via API test',
        ], 'application/x-www-form-urlencoded');

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidCreateUpdatePageResponse($body);
        $this->assertEquals($pageName, $body['info']['pageName'], 'Updated page name should match');

        // Verify the content was actually updated in the database.
        // Pass skipCache=true: create_page() populates the in-process cache during setUpBeforeClass,
        // and without bypassing it get_page_info() would return the stale original content.
        $tikilib = TikiLib::lib('tiki');
        $info = $tikilib->get_page_info($pageName, true, true);
        $this->assertEquals($updatedContent, $info['data'], 'Page content should reflect the API update');
    }

    public function testApiUpdatePageNonExistent()
    {
        $pageName = 'NonExistentPage_' . uniqid();

        $response = $this->makeApiRequest('POST', "/wiki/page/{$pageName}", 'Admins', [
            'data' => 'This update should fail.',
        ], 'application/x-www-form-urlencoded');

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiUpdatePageWithoutPermission()
    {
        $pageName = static::$defaultTestPage;
        $this->assertNotNull($pageName, 'Default test page must exist');

        $response = $this->makeApiRequest('POST', "/wiki/page/{$pageName}", null, [
            'data' => 'Anonymous should not be able to update this page.',
        ], 'application/x-www-form-urlencoded');

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiDeletePage()
    {
        $uid      = uniqid();
        $pageName = 'ApiDelete_' . $uid;
        static::createWikiPage($pageName, 'Page to be deleted by API test');

        $response = $this->makeApiRequest('POST', '/wiki/delete', 'Admins', [
            'items' => [$pageName],
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidRefreshFeedbackResponse($body);

        $feedbackMes = $body['feedback']['action'][0]['mes'] ?? '';
        $this->assertContains('All versions of the following page have been deleted:', $feedbackMes, 'Feedback should indicate deletion');

        $feedbackItems = $body['feedback']['action'][0]['items'] ?? [];
        $this->assertContains($pageName, $feedbackItems, 'Deleted page name should appear in feedback items');

        $feedbackTitle = $body['feedback']['action'][0]['title'] ?? '';
        $this->assertStringContainsString('Success', $feedbackTitle, 'Feedback title should indicate success');

        // Verify the page was actually deleted from the database.
        // Clear the in-process page cache first so page_exists() reads fresh from DB.
        $tikilib = TikiLib::lib('tiki');
        $tikilib->cache_page_info = [];
        $this->assertFalse((bool) $tikilib->page_exists($pageName), 'Page should no longer exist after deletion');
    }

    public function testApiDeleteMultiplePages()
    {
        $uid   = uniqid();
        $page1 = 'ApiDeleteMulti1_' . $uid;
        $page2 = 'ApiDeleteMulti2_' . $uid;
        static::createWikiPage($page1, 'First page for multi-delete test');
        static::createWikiPage($page2, 'Second page for multi-delete test');

        $response = $this->makeApiRequest('POST', '/wiki/delete', 'Admins', [
            'items' => [$page1, $page2],
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidRefreshFeedbackResponse($body);

        $feedbackMes = $body['feedback']['action'][0]['mes'] ?? '';
        $this->assertContains('All versions of the following pages have been deleted:', $feedbackMes, 'Feedback should indicate deletion of multiple pages');

        $feedbackItems = $body['feedback']['action'][0]['items'] ?? [];
        $this->assertContains($page1, $feedbackItems, 'First page should appear in feedback items');
        $this->assertContains($page2, $feedbackItems, 'Second page should appear in feedback items');

        $feedbackTitle = $body['feedback']['action'][0]['title'] ?? '';
        $this->assertStringContainsString('Success', $feedbackTitle, 'Feedback title should indicate success');

        $tikilib = TikiLib::lib('tiki');
        $tikilib->cache_page_info = [];
        $this->assertFalse((bool) $tikilib->page_exists($page1), 'First page should be deleted');
        $this->assertFalse((bool) $tikilib->page_exists($page2), 'Second page should be deleted');
    }

    public function testApiLockPage()
    {
        $uid      = uniqid();
        $pageName = 'ApiLock_' . $uid;
        static::createWikiPage($pageName, 'Page for lock test');

        $response = $this->makeApiRequest('POST', '/wiki/lock', 'Admins', [
            'items' => [$pageName],
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidRefreshFeedbackResponse($body);

        $feedbackMes = $body['feedback']['action'][0]['mes'] ?? '';
        $this->assertContains('The following page has been locked:', $feedbackMes, 'Feedback should indicate locking');

        $feedbackItems = $body['feedback']['action'][0]['items'] ?? [];
        $this->assertContains($pageName, $feedbackItems, 'Locked page name should appear in feedback items');

        $feedbackTitle = $body['feedback']['action'][0]['title'] ?? '';
        $this->assertStringContainsString('Success', $feedbackTitle, 'Feedback title should indicate success');

        // Verify the page is now locked in the database
        $wikilib = TikiLib::lib('wiki');
        $this->assertTrue((bool) $wikilib->is_locked($pageName), 'Page should be locked after lock operation');
    }

    public function testApiUnlockPage()
    {
        $uid      = uniqid();
        $pageName = 'ApiUnlock_' . $uid;
        static::createWikiPage($pageName, 'Page for unlock test');

        // Pre-lock the page directly
        $wikilib = TikiLib::lib('wiki');
        $wikilib->lock_page($pageName);
        $this->assertTrue((bool) $wikilib->is_locked($pageName), 'Page should be locked before the unlock test');

        $response = $this->makeApiRequest('POST', '/wiki/unlock', 'Admins', [
            'items' => [$pageName],
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidRefreshFeedbackResponse($body);

        $feedbackMes = $body['feedback']['action'][0]['mes'] ?? '';
        $this->assertContains('The following page has been unlocked:', $feedbackMes, 'Feedback should indicate unlocking');

        $feedbackItems = $body['feedback']['action'][0]['items'] ?? [];
        $this->assertContains($pageName, $feedbackItems, 'Unlocked page name should appear in feedback items');

        $feedbackTitle = $body['feedback']['action'][0]['title'] ?? '';
        $this->assertStringContainsString('Success', $feedbackTitle, 'Feedback title should indicate success');

        // Verify the page is now unlocked in the database
        $this->assertFalse((bool) $wikilib->is_locked($pageName), 'Page should be unlocked after unlock operation');
    }

    public function testApiLockPageWithFeatureDisabled()
    {
        // Temporarily disable feature_wiki_usrlock
        static::setTestPreferences('feature_wiki_usrlock', 'n');

        try {
            $pageName = static::$defaultTestPage;
            $this->assertNotNull($pageName, 'Default test page must exist');

            $response = $this->makeApiRequest('POST', '/wiki/lock', 'Admins', [
                'items' => [$pageName],
            ]);

            $body = $this->getResponseBody($response);
            $this->assertValidErrorResponse($body, 403, 'Feature disabled: feature_wiki_usrlock');
        } finally {
            // Re-enable for subsequent tests
            static::setTestPreferences('feature_wiki_usrlock', 'y');
        }
    }

    public function testApiDeletePageVersions()
    {
        $uid      = uniqid();
        $pageName = 'ApiDeleteVer_' . $uid;
        static::createWikiPage($pageName, 'Initial content for version delete test');

        // Create a second version by updating the page
        $tikilib = TikiLib::lib('tiki');
        $tikilib->update_page(
            $pageName,
            'Updated content for version delete test',
            'API test update',
            'admin',
            '127.0.0.1'
        );

        // Retrieve the history to find the older version number
        $histlib = TikiLib::lib('hist');
        $history = $histlib->get_page_history($pageName, false, 0, 10);
        $this->assertNotEmpty($history, 'Page should have version history');

        // Delete the oldest version
        $oldestVersion = end($history);
        $versionNum    = $oldestVersion['version'];

        $response = $this->makeApiRequest('POST', "/wiki/page/{$pageName}/delete", 'Admins', [
            'items' => [(string) $versionNum],
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidRefreshFeedbackResponse($body);

        // Verify the specific version is no longer in history
        $updatedHistory = $histlib->get_page_history($pageName, false, 0, 10);
        $versions       = array_column($updatedHistory, 'version');
        $this->assertNotContains((string) $versionNum, $versions, 'Deleted version should no longer be in history');
    }

    public function testApiPatchPageSeoTitle()
    {
        $pageName = static::$defaultTestPage;
        $this->assertNotNull($pageName, 'Default test page must exist');

        $seoTitle = 'SEO Title set by API test ' . uniqid();

        $response = $this->makeApiRequest('PATCH', "/wiki/page/{$pageName}", 'Admins', [
            'seo_title' => $seoTitle,
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidPatchPageResponse($body);
        $this->assertEquals($pageName, $body['page'], 'Patch response should reflect the target page');
        $this->assertContains('seo_title', $body['updated_fields'], 'updated_fields should list seo_title');
    }

    public function testApiPatchPageSeoDescription()
    {
        $pageName = static::$defaultTestPage;
        $this->assertNotNull($pageName, 'Default test page must exist');

        $seoDesc = 'SEO description set by API test ' . uniqid();

        $response = $this->makeApiRequest('PATCH', "/wiki/page/{$pageName}", 'Admins', [
            'seo_description' => $seoDesc,
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidPatchPageResponse($body);
        $this->assertContains('seo_description', $body['updated_fields'], 'updated_fields should list seo_description');
    }

    public function testApiPatchPageNonExistent()
    {
        $pageName = 'NonExistentPage_' . uniqid();
        $response = $this->makeApiRequest('PATCH', "/wiki/page/{$pageName}", 'Admins', [
            'data' => 'Description for non-existent page',
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, "Page \"{$pageName}\" not found (404)");
    }

    public function testApiPatchPageWithoutPermission()
    {
        $pageName = static::$defaultTestPage;
        $this->assertNotNull($pageName, 'Default test page must exist');

        $response = $this->makeApiRequest('PATCH', "/wiki/page/{$pageName}", null, [
            'data' => 'Should not be set by anonymous',
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }
}
