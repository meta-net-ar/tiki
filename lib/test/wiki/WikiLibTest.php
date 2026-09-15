<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 *  Test wiki page rendering options
 */

namespace test\Tiki\Lib\wiki;

use Exception;
use PHPUnit\Framework\TestCase;
use TikiLib;

class WikiLibTest extends TestCase
{
    private $pageName = 'WikiLib Test Page';

    protected function setUp(): void
    {
        global $testhelpers;

        $testhelpers = new \TestHelpers();
        $testhelpers->simulateTikiScriptContext();

        require_once(__DIR__ . '/../../../lib/wiki/renderlib.php');
    }

    protected function tearDown(): void
    {
        global $testhelpers;

        $testhelpers->removeAllVersions($this->pageName);

        $testhelpers->stopSimulatingTikiScriptContext();
    }

    /**
     * Test per wiki page autotoc settings
     *
     * @throws Exception
     */
    public function testProcessPageDisplayOptions(): void
    {
        global $prefs, $testhelpers;
        $wikilib = TikiLib::lib('wiki');

        // testing autotoc per page settings
        $prefs['wiki_auto_toc'] = 'y';
        $prefs['feature_page_title'] = 'n';
        $pageContent = '! Heading H1
!! Heading H2
Some text
!!! Heading H3
Some text
!! Second Heading H2
Some more text
';

        $testhelpers->createPage($this->pageName, 0, $pageContent);

        // processPageDisplayOptions needs this
        $_REQUEST['page'] = $this->pageName;
        $headerlibVirginCopy = clone TikiLib::lib('header');

        $headerlib = clone $headerlibVirginCopy;
        $prefs['wiki_toc_default'] = 'on';
        $wikilib->set_page_auto_toc($this->pageName, 0);
        $wikilib->processPageDisplayOptions($headerlib);
        $tags = $headerlib->output_js_files();
        $expected = 'lib/jquery_tiki/autoToc.js';
        $this->assertStringContainsString($expected, $tags, 'Autotoc on, page set to default');

        $headerlib = clone $headerlibVirginCopy;
        $wikilib->set_page_auto_toc($this->pageName, -1);
        $wikilib->processPageDisplayOptions($headerlib);
        $tags = $headerlib->output_js_files();
        $this->assertStringNotContainsString($expected, $tags, 'Autotoc on, page set to off');

        $headerlib = clone $headerlibVirginCopy;
        $wikilib->set_page_auto_toc($this->pageName, 1);
        $wikilib->processPageDisplayOptions($headerlib);
        $tags = $headerlib->output_js_files();
        $this->assertStringContainsString($expected, $tags, 'Autotoc on, page set to on');

        $prefs['wiki_toc_default'] = 'off';
        $headerlib = clone $headerlibVirginCopy;
        $wikilib->set_page_auto_toc($this->pageName, 0);
        $wikilib->processPageDisplayOptions($headerlib);
        $tags = $headerlib->output_js_files();
        $this->assertStringNotContainsString($expected, $tags, 'Autotoc off, page set to default');

        $tags = $headerlib->output_js_files();
        $wikilib->set_page_auto_toc($this->pageName, -1);
        $wikilib->processPageDisplayOptions($headerlib);
        $this->assertStringNotContainsString($expected, $tags, 'Autotoc off, page set to off');

        $headerlib = clone $headerlibVirginCopy;
        $wikilib->set_page_auto_toc($this->pageName, 1);
        $wikilib->processPageDisplayOptions($headerlib);
        $tags = $headerlib->output_js_files();
        $this->assertStringContainsString($expected, $tags, 'Autotoc off, page set to on');
    }

    public function testGetParseSkipCacheDoesNotReadOrUpdatePageCache(): void
    {
        global $prefs, $testhelpers, $user;

        $wikilib = TikiLib::lib('wiki');
        $originalPrefs = $this->capturePrefs(['wiki_cache', 'feature_wiki_icache']);
        $originalRequest = $_REQUEST;
        $originalMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $originalUser = $user;

        try {
            $prefs['wiki_cache'] = 3600;
            $prefs['feature_wiki_icache'] = 'n';
            $user = '';

            $testhelpers->createPage($this->pageName, 0, 'Fresh wiki render body');
            $wikilib->update_cache($this->pageName, 'Stale cached render body');

            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_REQUEST = [];

            $canBeRefreshed = false;
            $parsed = $wikilib->get_parse($this->pageName, $canBeRefreshed, false, true);

            $this->assertStringContainsString('Fresh wiki render body', $parsed);
            $this->assertStringNotContainsString('Stale cached render body', $parsed);
            $this->assertSame('Stale cached render body', $wikilib->get_cache_info($this->pageName)['cache']);
        } finally {
            $this->restorePrefs($originalPrefs, ['wiki_cache', 'feature_wiki_icache']);
            $_REQUEST = $originalRequest;
            $user = $originalUser;
            if ($originalMethod === null) {
                unset($_SERVER['REQUEST_METHOD']);
            } else {
                $_SERVER['REQUEST_METHOD'] = $originalMethod;
            }
        }
    }

    private function capturePrefs(array $names): array
    {
        global $prefs;

        $captured = [];
        foreach ($names as $name) {
            if (array_key_exists($name, $prefs)) {
                $captured[$name] = $prefs[$name];
            }
        }

        return $captured;
    }

    private function restorePrefs(array $captured, array $names): void
    {
        global $prefs;

        foreach ($names as $name) {
            if (array_key_exists($name, $captured)) {
                $prefs[$name] = $captured[$name];
            } else {
                unset($prefs[$name]);
            }
        }
    }
}
