<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace test\Tiki\Lib\wiki;

use Tiki\Cache\NoCache;
use PHPUnit\Framework\TestCase;
use TikiLib;

class WikiPluginWysiwygTest extends TestCase
{
    private $originalCacheImpl;
    private $originalIconset;
    private $testhelpers;

    protected function setUp(): void
    {
        global $iconset;

        $this->originalIconset = $iconset;

        $iconset = new class {
            public function getHtml($name, $params = [])
            {
                return "$name-icon-mock ";
            }
        };

        require_once(__DIR__ . '/../TestHelpers.php');

        $cachelib = TikiLib::lib('cache');
        $this->originalCacheImpl = $cachelib->replaceImplementation(new NoCache());

        $this->testhelpers = new \TestHelpers();
        $this->testhelpers->simulateTikiScriptContext();
        $this->testhelpers->createPage('foo', 0, '');
    }

    protected function tearDown(): void
    {
        global $iconset;

        $iconset = $this->originalIconset;

        TikiLib::lib('cache')->replaceImplementation($this->originalCacheImpl);

        $this->testhelpers->removeAllVersions('foo');
        $this->testhelpers->stopSimulatingTikiScriptContext();
    }

    public function testRendersNestedMouseover(): void
    {
        global $prefs;

        require_once('lib/setup/cookies.php');

        $prefs['feature_page_title'] = 'y';
        $prefs['feature_wiki_paragraph_formatting'] = 'n';
        $prefs['pass_chr_special'] = 'n';
        $prefs['wiki_heading_links'] = 'n';
        $prefs['feature_wiki_show_int_link_title'] = 'y';
        $prefs['wikiplugin_wysiwyg'] = 'y';
        $prefs['wikiplugin_mouseover'] = 'y';
        $prefs['wikiplugin_code'] = 'y';
        $prefs['wikiplugin_box'] = 'y';
        $prefs['wikiplugin_fade'] = 'y';
        $prefs['wikiplugin_tracker'] = 'y';

        $input = '{WYSIWYG()}'
            . '{MOUSEOVER(label="Hover me")}Hidden text{MOUSEOVER}'
            . '{CODE()}echo "hello";{CODE}'
            . '{BOX()}Box content{BOX}'
            . '{FADE(label="Show")}Hidden fade content{FADE}'
            . '{TRACKER(trackerId=1)}{TRACKER}'
            . '{WYSIWYG}';
        $output = TikiLib::lib('parser')->parse_data($input, ['page' => 'foo']);

        $this->assertStringNotContainsString('Plugin disabled', $output);
        $this->assertStringNotContainsString('MOUSEOVER(label=&quot;Hover me&quot;)', $output);
        $this->assertStringContainsString('plugin-mouseover-anchor', $output);
        $this->assertStringContainsString('Hidden text', $output);
        $this->assertStringNotContainsString('echo', $output);
        $this->assertStringNotContainsString('Box content', $output);
        $this->assertStringNotContainsString('Hidden fade content', $output);
        $this->assertStringNotContainsString('TRACKER(trackerId=1)', $output);
    }
}
