<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Translations;

use Tiki\Lib\Test\Api\ApiSchemaLoader;
use Tiki\Lib\Test\Api\ApiTestCase;
use TikiLib;

/**
 * Base class for Translations API integration tests
 * Provides common setup, teardown, and assertion helpers
 *
 * @group api-integration-test
 */
abstract class ApiBaseTranslationsTest extends ApiTestCase
{
    /**
     * Names of wiki pages created during the test run (for cleanup)
     * @var array
     */
    protected static $testPages = [];

    /**
     * A wiki page with language 'en' — used for basic GET tests
     * @var string|null
     */
    protected static $defaultSourcePage = null;

    /**
     * A wiki page with NO language — used for 400 error tests
     * @var string|null
     */
    protected static $noLangPage = null;

    private static array $preferences = ['feature_multilingual' => 'y'];

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

        $sourceName = 'ApiTransEN_' . $uid;
        static::createWikiPage($sourceName, 'en');
        static::$defaultSourcePage = $sourceName;

        $noLangName = 'ApiTransNoLang_' . $uid;
        static::createWikiPage($noLangName, '');
        static::$noLangPage = $noLangName;
    }

    /**
     * Create a wiki page with the given language and register it for cleanup.
     * Delegates page-creation I/O to ApiTestCase::createTestWikiPage().
     */
    protected static function createWikiPage(string $name, string $lang): bool
    {
        // Remove any pre-existing page with this name so the language is set correctly.
        static::removeTestWikiPage($name);

        $result = static::createTestWikiPage($name, 'Integration test page for Translations API tests.', $lang);
        if ($result) {
            static::$testPages[] = $name;
        }
        return $result;
    }

    /**
     * Remove a single wiki page by name (safe to call if page does not exist).
     */
    protected static function removeWikiPage(string $name): void
    {
        static::removeTestWikiPage($name);
    }

    /**
     * Directly link two wiki pages as translations via the multilingual lib.
     * Both pages must already have a language set.
     */
    protected static function attachWikiPageTranslation(string $source, string $target): void
    {
        $tikilib         = TikiLib::lib('tiki');
        $multilinguallib = TikiLib::lib('multilingual');

        $sourceInfo  = $tikilib->get_page_info($source);
        $targetInfo  = $tikilib->get_page_info($target);
        $sourceId    = $tikilib->get_page_id_from_name($source);
        $targetId    = $tikilib->get_page_id_from_name($target);
        $sourceLang  = $sourceInfo['lang'] ?? 'en';
        $targetLang  = $targetInfo['lang'] ?? 'fr';

        $multilinguallib->insertTranslation('wiki page', $sourceId, $sourceLang, $targetId, $targetLang);
    }

    protected static function cleanupTestData(): void
    {
        foreach (static::$testPages as $pageName) {
            static::removeWikiPage($pageName);
        }
        static::$testPages        = [];
        static::$defaultSourcePage = null;
        static::$noLangPage        = null;

        static::deleteTestPreferences(self::$preferences);
    }

    protected function getManageTranslationsSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TranslationsManageResponse.yaml');
    }

    protected function getManageTranslationsWithAttachedTranslationSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TranslationsManageWithTranslationsResponse.yaml');
    }

    protected function getForwardTranslationSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TranslationsForwardResponse.yaml');
    }

    protected function assertValidManageTranslationsResponse(array $body): void
    {
        $this->assertMatchesSchema($body, $this->getManageTranslationsSchema());
        $this->assertEquals('Manage translations', $body['title']);
    }

    protected function assertValidManageTranslationsWithAttachedTranslationResponse(array $body): void
    {
        $this->assertMatchesSchema($body, $this->getManageTranslationsWithAttachedTranslationSchema());
        $this->assertEquals('Manage translations', $body['title']);
        $this->assertNotEmpty($body['translations'], 'Response should contain at least one translation');
    }

    protected function assertValidForwardTranslationResponse(array $body): void
    {
        $this->assertMatchesSchema($body, $this->getForwardTranslationSchema());
        $this->assertEquals('translation', $body['FORWARD']['controller']);
        $this->assertEquals('manage', $body['FORWARD']['action']);
    }
}
