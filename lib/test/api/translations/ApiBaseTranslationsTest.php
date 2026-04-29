<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Translations;

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

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::setTestPreference('feature_multilingual', 'y');
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
     * Returns true on success.
     */
    protected static function createWikiPage(string $name, string $lang): bool
    {
        try {
            $tikilib = TikiLib::lib('tiki');
            if ($tikilib->page_exists($name)) {
                $tikilib->remove_all_versions($name);
            }
            $result = $tikilib->create_page(
                $name,
                0,
                'Integration test page for Translations API tests.',
                time(),
                '',
                'admin',
                '127.0.0.1',
                '',
                $lang
            );
            if ($result !== false) {
                static::$testPages[] = $name;
                return true;
            }
        } catch (\Exception $e) {
            // Ignore — callers should handle missing pages
        }
        return false;
    }

    /**
     * Remove a single wiki page by name (safe to call if page does not exist).
     */
    protected static function removeWikiPage(string $name): void
    {
        try {
            TikiLib::lib('tiki')->remove_all_versions($name);
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }
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

        \TikiDb::get()->query(
            'DELETE FROM `tiki_preferences` WHERE `name` = ?',
            ['feature_multilingual']
        );
    }

    /**
     * Schema for GET /translations/{type}/{source} (action_manage).
     *
     * - filters.language is a quoted OR-string of remaining language codes, or null
     *   when no other language is available.
     * - translations is an array of translation objects (empty when no links exist).
     */
    private function getManageTranslationsSchema(): array
    {
        return [
            'title'        => 'string',
            'type'         => 'string',
            'source'       => 'string',
            'filters'      => [
                'type'     => 'string',
                'language' => 'string|null',
            ],
            'translations' => 'array',
            'canAttach'    => 'bool',
            'canDetach'    => 'bool',
        ];
    }

    private function getManageTranslationsWithAttachedTranslationSchema(): array
    {
        $schema = $this->getManageTranslationsSchema();
        $schema['translations'] = [
            '*' => [
                'traId'   => 'int',
                'objId' => 'int|string',
                'lang' => 'string',
                'language' => 'string',
            ],
        ];
        return $schema;
    }

    private function getForwardTranslationSchema(): array
    {
        return [
            'FORWARD' => [
                'controller' => 'string',
                'action'     => 'string',
                'type'       => 'string',
                'source'     => 'string',
            ],
        ];
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
