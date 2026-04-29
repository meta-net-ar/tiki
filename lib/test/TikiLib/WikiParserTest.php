<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Cache\NoCache;

/**
 * @group integration
 *
 */

class TikiLib_WikiParserTest extends PHPUnit\Framework\TestCase
{
    private $originalCacheImpl;
    private $originalIconset;
    private TestHelpers $testhelpers;

    protected function setUp(): void
    {
        global $iconset;

        $this->originalIconset = $iconset;

        // Mock iconset
        $iconset = new class {
            public function getHtml($name, $params = [])
            {
                return "$name-icon-mock ";
            }
        };

        $cachelib = TikiLib::lib('cache');
        // Prevent caching of icons during tests to avoid stale values from previous tests interfering with assertions
        $this->originalCacheImpl = $cachelib->replaceImplementation(new NoCache());

        $this->testhelpers = new TestHelpers();
        $this->testhelpers->simulateTikiScriptContext();

        $this->testhelpers->createPage('foo', 0, ''); // Create 'foo' page to be used in link tests
    }

    protected function tearDown(): void
    {
        global $iconset;

        $iconset = $this->originalIconset;

        TikiLib::lib('cache')->replaceImplementation($this->originalCacheImpl);

        $this->testhelpers->removeAllVersions('foo');
        $this->testhelpers->stopSimulatingTikiScriptContext();
    }

    /**
     * @covers       ParserLib::parse_data
     * @dataProvider provider
     * @param $input
     * @param $output
     * @param array $options
     * @throws Exception
     */
    public function testWikiParser($input, $output, $options = []): void
    {
        global $prefs;
        $prefs['feature_page_title'] = 'y';
        $prefs['feature_wiki_paragraph_formatting'] = 'n';
        $prefs['pass_chr_special'] = 'n';
        $prefs['wiki_heading_links'] = 'n';
        $prefs['feature_wiki_show_int_link_title'] = 'y';
        $this->assertEquals($output, TikiLib::lib('parser')->parse_data($input, $options));
    }

    public static function provider(): array
    {
        return [
            ['', ''],
            ['foo', "foo<br />"],
            ['---', "<hr />"],
            ['%%%', "<br /><br />"], // Line break (forced)
            ['%%%%%%', "<br /><br /><br />"], // Two line breaks (forced)
            ['%%% %%%', "<br /> <br /><br />"], // Two line breaks (forced) with a space in between
            ['bar: 100%%%%', "bar: 100%<br /><br />"], // Line break after 100% string at the end of the line
            ['Line one ending in number 42%%%Line two', "Line one ending in number 42<br />Line two<br />"], // Line break (forced) after a number at the end of the line

            ["''foo''", '<em>foo</em>' . "<br />"],

            ['__foo__', "<strong>foo</strong><br />"],   // bold`
            ['__ foo __', "<strong> foo </strong><br />"],   // bold

            ['===foo===', '<u>foo</u>' . "<br />"], // underline
            ['=== foo ===', '<u> foo </u>' . "<br />"], // underline

            ['-=foo=-', '<div class="titlebar">foo</div>' . "\n"],  // title bar
            ['-= foo =-', '<div class="titlebar"> foo </div>' . "\n"],  // title bar

            ['^foo^', '<div class="card bg-light"><div class="card-body">foo</div></div><br />'],   // box
            ['^ foo ^', '<div class="card bg-light"><div class="card-body"> foo </div></div><br />'],   // box

            ['::foo::', '<div style="text-align: center;">foo</div><br />'],    // center align
            [':: foo ::', '<div style="text-align: center;"> foo </div><br />'],    // center align

            ['! foo', '<h1 class="showhide_heading d-flex justify-content-start" id="foo"> foo</h1>'],   // heading 1
            ['!!foo', '<h2 class="showhide_heading d-flex justify-content-start" id="foo">foo</h2>'],    // heading 2
            ['!! foo', '<h2 class="showhide_heading d-flex justify-content-start" id="foo"> foo</h2>'],  // heading 2

            //heading 1 with collapsible text open
            [
                "!+foo\nheading text section",
                "<h1 class=\"showhide_heading d-flex justify-content-start\" id=\"foo\">foo</h1><a id=\"flipperidHomePage1\" class=\"link\" href=\"#\" onclick=\"flipWithSign('idHomePage1');return false;\">[-]</a><div id=\"idHomePage1\" class=\"showhide_heading\" style=\"display:block;\">\nheading text section<br /></div>",
                ['page' => 'HomePage'],
            ],

            //heading 1 with collapsible text closed
            [
                "!-foo\nheading text section",
                "<h1 class=\"showhide_heading d-flex justify-content-start\" id=\"foo\">foo</h1><a id=\"flipperidHomePage1\" class=\"link\" href=\"#\" onclick=\"flipWithSign('idHomePage1');return false;\">[+]</a><div id=\"idHomePage1\" class=\"showhide_heading\" style=\"display:none;\">\nheading text section<br /></div>",
                ['page' => 'HomePage'],
            ],

            ['--foo--', "<strike>foo</strike><br />"],  // strike out
            ['-- foo --', "-- foo --<br />"],   // not parsed

            ['[foo]', '<a class="wiki tips"  data-bs-content="foo" data-bs-toggle="tooltip" href="foo" rel="">foo</a><br />'], // link
            ['[foo|bar]', '<a class="wiki tips"  data-bs-content="foo" data-bs-toggle="tooltip" href="foo" rel="">bar</a><br />'], // link

            ['[[foo', '[foo<br />'], // Square brackets
            ['[[foo]]', '[[foo]]<br />'], // Square brackets
            ['[[foo]', '[foo]<br />'], // Square brackets

            ['-+foo+- ', '<code>foo</code><br />' . ""], // Monospace font
            ['-+ foo +- ', '<code> foo </code><br />' . ""], // Monospace font

            ['{r2l}foo', "<div dir='rtl'>foo<br /></div>"], // Right to left
            ['{l2r}foo', "<div dir='ltr'>foo<br /></div>"], // Left to right
            ['{rm}foo', "&rlm;foo<br />"],
            ['~amp~foo', "&amp;foo<br />"], // Special character &amp;
            ['~hs~foo', "&nbsp;foo<br />"], // Hard space

            [";foo1:bar1\n;foo2:bar2", "<dl><dt>foo1</dt><dd>bar1</dd></dl><br />\n<dl><dt>foo2</dt><dd>bar2</dd></dl><br />"], // Definition list

            ["* foo\n* bar\n", "<ul><li> foo\n</li><li> bar\n</li></ul><br />"], // Bulleted list
            ["* foo1\n** foo11\n**foo12\n* bar1\n", "<ul><li> foo1\n<ul><li> foo11\n</li><li>foo12\n</li></ul></li><li> bar1\n</li></ul><br />"], // Nested Bulleted list
            ["* foo\n+ Continuation1\n+Continuation2\n* bar\n", "<ul><li> foo\n<br /> Continuation1\n<br />Continuation2\n</li><li> bar\n</li></ul><br />"], // Bulleted list with continuation

            ["# foo\n# bar\n", "<ol><li> foo\n</li><li> bar\n</li></ol><br />"],    // Numbered list
            ["# foo1\n## foo11\n##foo12\n# bar1\n", "<ol><li> foo1\n<ol><li> foo11\n</li><li>foo12\n</li></ol></li><li> bar1\n</li></ol><br />"],   // Nested Numbered list
            ["# foo\n+ Continuation1\n+Continuation2\n# bar\n", "<ol><li> foo\n<br /> Continuation1\n<br />Continuation2\n</li><li> bar\n</li></ol><br />"], // Numbered list with continuation

            // --- TESTS FOR COLLAPSIBLE LISTS ---

            // Test 1: Basic collapsible list, shown by default (+)
            [
                "*+ Collapsible List Item",
                '<ul><li><a id="flipperidHomePage1" class="link" href="javascript:flipWithSign(\'idHomePage1\')">[-]</a> Collapsible List Item<ul id="idHomePage1" style="display:block;"></ul></li></ul><br />',
                ['page' => 'HomePage']
            ],

            // Test 2: Basic collapsible list, hidden by default (-)
            [
                "*- Collapsible List Item",
                '<ul><li><a id="flipperidHomePage1" class="link" href="javascript:flipWithSign(\'idHomePage1\')">[+]</a> Collapsible List Item<ul id="idHomePage1" style="display:none;"></ul></li></ul><br />',
                ['page' => 'HomePage']
            ],

            // Test 3: Nested collapsible list with children
            [
                "*+ Parent\n**- Child\n*** Grandchild",
                '<ul><li><a id="flipperidHomePage1" class="link" href="javascript:flipWithSign(\'idHomePage1\')">[-]</a> Parent<ul id="idHomePage1" style="display:block;">' . "\n" . '<li><a id="flipperidHomePage2" class="link" href="javascript:flipWithSign(\'idHomePage2\')">[+]</a> Child<ul id="idHomePage2" style="display:none;">' . "\n" . '<li> Grandchild</li></ul></li></ul></li></ul><br />',
                ['page' => 'HomePage']
            ],

            // Test 4: Ensure blank lines do not break a nested list
            [
                "*+ Parent\n\n** Child",
                '<ul><li><a id="flipperidHomePage1" class="link" href="javascript:flipWithSign(\'idHomePage1\')">[-]</a> Parent<ul id="idHomePage1" style="display:block;">' . "\n\n" . '<li> Child</li></ul></li></ul><br />',
                ['page' => 'HomePage']
            ],

            ["||r1c1|r1c2\nr2c1|r2c2||", '<table class="wikitable table table-striped table-hover"><tr><td class="wikicell" >r1c1</td><td class="wikicell" >r1c2</td></tr><tr><td class="wikicell" >r2c1</td><td class="wikicell" >r2c2</td></tr></table><br />'],
            ["~pp~foo~/pp~", "<pre>foo</pre><br />"],
        ];
    }
}
