<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Translations;

/**
 * Tests for Translations API GET endpoints
 *
 * Covers GET /translations/{type}/{source}
 *
 * @group api-integration-test
 * @group api-translations
 */
class ApiTranslationsGetTest extends ApiBaseTranslationsTest
{
    public function testApiListTranslationsForWikiPage()
    {
        $this->assertNotNull(static::$defaultSourcePage, 'Default source page must exist');

        $type = 'wiki page';
        $source = static::$defaultSourcePage;
        $response = $this->makeApiRequest('GET', "/translations/{$type}/{$source}", 'Admins');

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidManageTranslationsResponse($body);
        $this->assertEquals('wiki page', $body['type'], 'Response type should match the requested object type');
        $this->assertEquals(static::$defaultSourcePage, $body['source'], 'Response source should match the requested source page');
        $this->assertTrue($body['canAttach'], 'Admin user should be able to attach translations');
        $this->assertTrue($body['canDetach'], 'Admin user should be able to detach translations');
    }

    public function testApiListTranslationsAsAnonymous()
    {
        $this->assertNotNull(static::$defaultSourcePage, 'Default source page must exist');

        $type = 'wiki page';
        $source = static::$defaultSourcePage;
        $response = $this->makeApiRequest('GET', "/translations/{$type}/{$source}", null);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidManageTranslationsResponse($body);
        $this->assertEquals('wiki page', $body['type'], 'Response type should match the requested object type');
        $this->assertEquals(static::$defaultSourcePage, $body['source'], 'Response source should match the requested source page');
        $this->assertFalse($body['canAttach'], 'Anonymous user should not be able to attach translations');
        $this->assertFalse($body['canDetach'], 'Anonymous user should not be able to detach translations');
    }

    public function testApiListTranslationsUnsupportedType()
    {
        // Only 'wiki page', 'article', and 'trackeritem' are supported object types
        $type = 'unsupported_type';
        $source = 'SomePage';
        $response = $this->makeApiRequest('GET', "/translations/{$type}/{$source}", 'Admins');

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 400, 'Translation not supported for the specified object type');
    }

    public function testApiListTranslationsPageWithNoLanguage()
    {
        $this->assertNotNull(static::$noLangPage, 'No-language page must exist');

        $type = 'wiki page';
        $source = static::$noLangPage;
        $response = $this->makeApiRequest('GET', "/translations/{$type}/{$source}", 'Admins');

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 400, 'The object has no language indicated and cannot be translated');
    }

    public function testApiListTranslationsNonExistentPage()
    {
        $type = 'wiki page';
        $source = 'ThisPageDoesNotExist_' . uniqid();
        $response = $this->makeApiRequest('GET', "/translations/{$type}/{$source}", 'Admins');

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 400, 'The object has no language indicated and cannot be translated');
    }

    public function testApiListTranslationsContainsAttachedTranslation()
    {
        $uid        = uniqid();
        $sourceName = 'ApiTransGETSrc_' . $uid;
        $targetName = 'ApiTransGETTgt_' . $uid;

        static::createWikiPage($sourceName, 'en');
        static::createWikiPage($targetName, 'fr');
        static::attachWikiPageTranslation($sourceName, $targetName);

        $type = 'wiki page';
        $response = $this->makeApiRequest('GET', "/translations/{$type}/{$sourceName}", 'Admins');

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidManageTranslationsWithAttachedTranslationResponse($body);

        $translatedObjIds = array_column($body['translations'], 'objId');
        $this->assertContains($targetName, $translatedObjIds, 'Target page should appear in the translations list');
    }

    public function testApiListTranslationsFiltersExcludeAlreadyTranslatedLanguages()
    {
        // After linking an EN source to a FR target, the filters.language string
        // returned by action_manage should no longer offer FR as a candidate language
        $uid        = uniqid();
        $sourceName = 'ApiTransFiltSrc_' . $uid;
        $targetName = 'ApiTransFiltTgt_' . $uid;

        static::createWikiPage($sourceName, 'en');
        static::createWikiPage($targetName, 'fr');
        static::attachWikiPageTranslation($sourceName, $targetName);

        $type = 'wiki page';
        $response = $this->makeApiRequest('GET', "/translations/{$type}/{$sourceName}", 'Admins');

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidManageTranslationsWithAttachedTranslationResponse($body);

        $language = $body['filters']['language'];
        $this->assertStringNotContainsString('"fr"', $language, 'French should be excluded from candidate languages after linking');
    }
}
