<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace {
    if (! defined('TIKI_API')) {
        define('TIKI_API', false);
    }

    if (! function_exists('tra')) {
        function tra($content)
        {
            return $content;
        }
    }
}

namespace Tiki\Lib\Test\CookieConsent {

use PHPUnit\Framework\TestCase;
use Tiki\Lib\CookieConsent\CookieConsentLib;

class CookieConsentLibTest extends TestCase
{
    private array $cookieBackup = [];
    private array $sessionBackup = [];
    private mixed $featureNoCookieBackup = null;
    private bool $hadFeatureNoCookie = false;
    private mixed $jitCookieBackup = null;
    private bool $hadJitCookie = false;

    protected function setUp(): void
    {
        $this->cookieBackup = $_COOKIE;
        $this->sessionBackup = $_SESSION ?? [];
        $this->hadFeatureNoCookie = array_key_exists('feature_no_cookie', $GLOBALS);
        $this->featureNoCookieBackup = $GLOBALS['feature_no_cookie'] ?? null;
        $this->hadJitCookie = array_key_exists('jitCookie', $GLOBALS);
        $this->jitCookieBackup = $GLOBALS['jitCookie'] ?? null;

        $_COOKIE = [];
        $_SESSION['tiki_cookie_jar'] = [];
        $GLOBALS['feature_no_cookie'] = true;
        $GLOBALS['jitCookie'] = [];
    }

    protected function tearDown(): void
    {
        $_COOKIE = $this->cookieBackup;
        $_SESSION = $this->sessionBackup;

        if ($this->hadFeatureNoCookie) {
            $GLOBALS['feature_no_cookie'] = $this->featureNoCookieBackup;
        } else {
            unset($GLOBALS['feature_no_cookie']);
        }

        if ($this->hadJitCookie) {
            $GLOBALS['jitCookie'] = $this->jitCookieBackup;
        } else {
            unset($GLOBALS['jitCookie']);
        }
    }

    public function testCookieCategoriesContainBuiltInCategories(): void
    {
        $categories = CookieConsentLib::getCookieCategories();

        $this->assertArrayHasKey(CookieConsentLib::BUILTIN_COOKIE_CATEGORY_ESSENTIAL, $categories);
        $this->assertArrayHasKey(CookieConsentLib::BUILTIN_COOKIE_CATEGORY_ANALYTICS, $categories);
        $this->assertArrayHasKey(CookieConsentLib::BUILTIN_COOKIE_CATEGORY_MARKETING, $categories);
        $this->assertArrayHasKey(CookieConsentLib::BUILTIN_COOKIE_CATEGORY_FUNCTIONAL, $categories);
    }

    public function testSetCookieStoresValueInSessionCookieJarWhenCookiesAreDisabled(): void
    {
        $this->assertTrue(CookieConsentLib::setCookie('plain_cookie', 'plain-value'));

        $this->assertSame('plain-value', CookieConsentLib::getCookie('plain_cookie'));
        $this->assertSame('plain-value', $_SESSION['tiki_cookie_jar']['plain_cookie']);
    }

    public function testGetCookieReadsRegularCookieBeforeSessionCookieJar(): void
    {
        $_COOKIE['same_name'] = 'browser-value';
        $_SESSION['tiki_cookie_jar']['same_name'] = 'jar-value';

        $this->assertSame('browser-value', CookieConsentLib::getCookie('same_name'));
    }

    public function testGetCookieReadsJitCookieWhenRegularCookieIsAbsent(): void
    {
        $GLOBALS['jitCookie']['jit_only'] = 'jit-value';

        $this->assertSame('jit-value', CookieConsentLib::getCookie('jit_only'));
    }

    public function testSetCookieCanStoreAndUpdateSectionCookieValues(): void
    {
        $this->assertTrue(CookieConsentLib::setCookie('selected_tab', '1', 'tabs'));
        $this->assertSame('1', CookieConsentLib::getCookie('selected_tab', 'tabs'));

        $this->assertTrue(CookieConsentLib::setCookie('selected_tab', '2', 'tabs'));
        $this->assertSame('2', CookieConsentLib::getCookie('selected_tab', 'tabs'));
        $this->assertSame('@selected_tab:2', CookieConsentLib::getCookie('tabs'));
    }

    public function testSectionCookieCanStoreMultipleValues(): void
    {
        CookieConsentLib::setCookie('first', 'one', 'tabs');
        CookieConsentLib::setCookie('second', 'two', 'tabs');

        $this->assertSame('one', CookieConsentLib::getCookie('first', 'tabs'));
        $this->assertSame('two', CookieConsentLib::getCookie('second', 'tabs'));
    }

    public function testGetCookieReturnsDefaultForMissingCookie(): void
    {
        $this->assertSame('fallback', CookieConsentLib::getCookie('missing', null, 'fallback'));
        $this->assertSame('fallback', CookieConsentLib::getCookie('missing', 'tabs', 'fallback'));
    }
}
}
