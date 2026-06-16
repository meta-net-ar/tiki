<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Translations;

/**
 * Tests for Translations API POST endpoints
 *
 * Covers:
 *   POST /translations/{type}/{source}/attach
 *   POST /translations/{type}/{source}/detach
 *   POST /translate
 *
 * @group api-integration-test
 * @group api-translations
 */
class ApiTranslationsPostTest extends ApiBaseTranslationsTest
{
    public function testApiAttachTranslationsWithoutPermission()
    {
        $this->assertNotNull(static::$defaultSourcePage, 'Default source page must exist');

        $uid        = uniqid();
        $targetName = 'ApiTransAnonTgt_' . $uid;
        static::createWikiPage($targetName, 'fr');

        $type = 'wiki page';
        $source = static::$defaultSourcePage;
        $response = $this->makeApiRequest('POST', "/translations/{$type}/{$source}/attach", null, [
            'target' => $targetName,
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'You do not have permission to attach the selected translations');
    }

    public function testApiAttachTranslations()
    {
        $uid        = uniqid();
        $sourceName = 'ApiTransAttSrc_' . $uid;
        $targetName = 'ApiTransAttTgt_' . $uid;

        static::createWikiPage($sourceName, 'en');
        static::createWikiPage($targetName, 'fr');

        $type = 'wiki page';
        $response = $this->makeApiRequest('POST', "/translations/{$type}/{$sourceName}/attach", 'Admins', [
            'target' => $targetName,
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidForwardTranslationResponse($body);
        $this->assertEquals($sourceName, $body['FORWARD']['source'], 'FORWARD source should match the attached source page');
    }

    public function testApiAttachTranslationsUnsupportedType()
    {
        $type = 'unsupported_type';
        $source = 'SomePage';
        $response = $this->makeApiRequest('POST', "/translations/{$type}/{$source}/attach", 'Admins', [
            'target' => 'OtherPage',
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 400, 'Translation not supported for the specified object type');
    }

    public function testApiAttachTranslationsPageWithNoLanguage()
    {
        $this->assertNotNull(static::$noLangPage, 'No-language page must exist');

        $uid        = uniqid();
        $targetName = 'ApiTransNoLangTgt_' . $uid;
        static::createWikiPage($targetName, 'fr');

        $type = 'wiki page';
        $source = static::$noLangPage;
        $response = $this->makeApiRequest('POST', "/translations/{$type}/{$source}/attach", 'Admins', [
            'target' => $targetName,
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 400, 'The object has no language indicated and cannot be translated');
    }

    public function testApiAttachTranslationsAlreadyLinked()
    {
        $uid        = uniqid();
        $sourceName = 'ApiTransDupSrc_' . $uid;
        $targetName = 'ApiTransDupTgt_' . $uid;

        static::createWikiPage($sourceName, 'en');
        static::createWikiPage($targetName, 'fr');
        static::attachWikiPageTranslation($sourceName, $targetName);

        $type = 'wiki page';
        $source = $sourceName;
        $response = $this->makeApiRequest('POST', "/translations/{$type}/{$source}/attach", 'Admins', [
            'target' => $targetName,
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 409, 'Could not attach the translations');
    }

    public function testApiAttachTranslationsWithFeatureDisabled()
    {
        static::setTestPreferences('feature_multilingual', 'n');

        try {
            $uid        = uniqid();
            $sourceName = 'ApiTransDisSrc_' . $uid;
            static::createWikiPage($sourceName, 'en');

            $type = 'wiki page';
            $response = $this->makeApiRequest('POST', "/translations/{$type}/{$sourceName}/attach", 'Admins', [
                'target' => 'SomePage',
            ]);

            $body = $this->getResponseBody($response);
            $this->assertValidErrorResponse($body, 403, 'Feature Disabled');
        } finally {
            static::setTestPreferences('feature_multilingual', 'y');
        }
    }

    public function testApiDetachTranslationsWithoutPermission()
    {
        $uid        = uniqid();
        $sourceName = 'ApiTransDetAnonSrc_' . $uid;
        $targetName = 'ApiTransDetAnonTgt_' . $uid;

        static::createWikiPage($sourceName, 'en');
        static::createWikiPage($targetName, 'fr');
        static::attachWikiPageTranslation($sourceName, $targetName);

        $type = 'wiki page';
        $response = $this->makeApiRequest('POST', "/translations/{$type}/{$sourceName}/detach", null, [
            'target' => $targetName,
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'You do not have permission to detach the selected translations');
    }

    public function testApiDetachTranslations()
    {
        $uid        = uniqid();
        $sourceName = 'ApiTransDetSrc_' . $uid;
        $targetName = 'ApiTransDetTgt_' . $uid;

        static::createWikiPage($sourceName, 'en');
        static::createWikiPage($targetName, 'fr');
        static::attachWikiPageTranslation($sourceName, $targetName);

        $type = 'wiki page';
        $response = $this->makeApiRequest('POST', "/translations/{$type}/{$sourceName}/detach", 'Admins', [
            'target' => $targetName,
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidForwardTranslationResponse($body);
        $this->assertEquals($sourceName, $body['FORWARD']['source']);
    }

    public function testApiDetachTranslationsUnsupportedType()
    {
        $type = 'unsupported_type';
        $source = 'SomePage';
        $response = $this->makeApiRequest('POST', "/translations/{$type}/{$source}/detach", 'Admins', [
            'target' => 'OtherPage',
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 400, 'Translation not supported for the specified object type');
    }

    public function testApiTranslateWithFeatureDisabled()
    {
        $response = $this->makeApiRequest('POST', '/translate', 'Admins', [
            'content' => 'Hello world',
            'lang'    => 'fr',
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Feature disabled: feature_machine_translation');
    }
}
