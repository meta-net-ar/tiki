<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\WikiPlugins;

use TikiTestCase;
use TikiLib;

class WikiPluginWantedPagesTest extends TikiTestCase
{
    private $orig_user;
    private array $testPages = [];

    // Unique fromPage used in all test link inserts so tearDown can clean precisely
    private string $testFromPage = 'WantedPagesTest_FromPage';

    // Extra fromPages used by a single test, cleaned up alongside $testFromPage
    private array $testFromPages = [];

    protected function setUp(): void
    {
        global $user, $prefs;
        $this->orig_user = $user;

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = 'phpunit';
        $user = 'admin';

        $prefs['wikiplugin_wantedpages'] = 'y';
        $prefs['feature_wikiwords'] = 'y';
        $prefs['feature_wikiwords_usedash'] = 'y';
        $prefs['namespace_enabled'] = 'n';

        $this->setPageRegex();

        TikiLib::lib('tiki')->clear_links($this->testFromPage);
    }

    protected function tearDown(): void
    {
        global $user;

        foreach ($this->testPages as $page) {
            (new \TestHelpers())->removeAllVersions($page);
        }
        $this->testPages = [];

        TikiLib::lib('tiki')->clear_links($this->testFromPage);
        foreach ($this->testFromPages as $fromPage) {
            TikiLib::lib('tiki')->clear_links($fromPage);
        }
        $this->testFromPages = [];

        unset($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']);
        $user = $this->orig_user;
    }

    private function createTestPage(string $name, string $data = 'Test content'): void
    {
        (new \TestHelpers())->createPage($name, 0, $data);
        $this->testPages[] = $name;
    }

    private function insertLink(string $toPage, ?string $fromPage = null): void
    {
        if ($fromPage !== null) {
            $this->testFromPages[] = $fromPage;
        }
        TikiLib::lib('tiki')->replace_link($fromPage ?? $this->testFromPage, $toPage);
    }

    private function runPlugin(): string
    {
        return TikiLib::lib('parser')->invokePlugin('wantedpages', '', []);
    }

    /**
     * Positive control: a link whose toPage exactly matches pageName must not be listed.
     */
    public function testExactPageNameNotListedAsWanted(): void
    {
        $this->createTestPage('WantedPagesTest ExactMatch');
        $this->insertLink('WantedPagesTest ExactMatch');

        $output = $this->runPlugin();

        $this->assertStringNotContainsString('WantedPagesTest ExactMatch', $output);
    }

    /**
     * Regression: a link using the page slug (hyphens) must not be listed when the page
     * exists under its space-separated name.
     *
     * Before the fix, the SQL JOIN only matched toPage against pageName, so slug-form
     * links always appeared as wanted pages even when the target page existed.
     */
    public function testSlugFormLinkNotListedAsWanted(): void
    {
        $pageName = 'WantedPagesTest SlugMatch';
        $this->createTestPage($pageName);

        $pageInfo = TikiLib::lib('tiki')->get_page_info($pageName);
        $pageSlug = $pageInfo['pageSlug'];
        $this->assertNotEmpty($pageSlug, 'Tiki should generate a pageSlug for the created page.');

        $this->insertLink($pageSlug);

        $output = $this->runPlugin();

        $this->assertStringNotContainsString(
            $pageSlug,
            $output,
            'A link to an existing page via its slug should not appear in the wanted pages list.'
        );
    }

    /**
     * Regression, symmetric to testSlugFormLinkNotListedAsWanted: the page is stored hyphenated,
     * so neither pageName nor pageSlug equals the link as written and only a slug variation matches.
     */
    public function testSpaceFormLinkToDashNamedPageNotListedAsWanted(): void
    {
        $pageName = 'WantedPagesTest-DashName';
        $this->createTestPage($pageName);

        $this->insertLink('WantedPagesTest DashName');

        $output = $this->runPlugin();

        $this->assertStringNotContainsString(
            'WantedPagesTest DashName',
            $output,
            'A space-separated link to an existing hyphenated page should not appear in the wanted pages list.'
        );
    }

    public function testUnderscoreFormLinkToDashNamedPageNotListedAsWanted(): void
    {
        $pageName = 'WantedPagesTest-UnderscoreName';
        $this->createTestPage($pageName);

        $this->insertLink('WantedPagesTest_UnderscoreName');

        $output = $this->runPlugin();

        $this->assertStringNotContainsString(
            'WantedPagesTest_UnderscoreName',
            $output,
            'An underscore-separated link to an existing hyphenated page should not appear in the wanted pages list.'
        );
    }

    /**
     * Regression: a referencing page whose name contains parentheses must be rendered in full.
     * Wrapping it in wiki link syntax produced "((Name (x)))", which the parser cut at the first
     * "))", leaving a link to the truncated name and a stray ")" as text.
     */
    public function testParenthesesInReferencingPageNameNotTruncated(): void
    {
        $fromPage = 'WantedPagesTest (fr)';
        $this->createTestPage($fromPage);

        $this->insertLink('WantedPagesTest ParenthesesTarget', $fromPage);

        $output = $this->runPlugin();

        $this->assertStringContainsString(
            $fromPage . '</a>',
            $output,
            'The referencing page name should be rendered in full, closing parenthesis included.'
        );
        $this->assertStringNotContainsString(
            'WantedPagesTest (fr</a>',
            $output,
            'The referencing page name should not be cut before its closing parenthesis.'
        );
    }

    public function testEscapedLinkNotListedAsWanted(): void
    {
        $fromPage = 'WantedPagesTest_EscapedOnly';
        $toPage = 'WantedPagesTest EscapedOnlyTarget';
        $this->createTestPage($fromPage, "Example syntax: ~np~(($toPage))~/np~");

        $this->insertLink($toPage, $fromPage);

        $this->assertStringNotContainsString(
            $toPage,
            $this->runPlugin(),
            'A link that only ever appears inside ~np~ tags should not appear in the wanted pages list.'
        );
    }

    public function testSemanticEscapedLinkNotListedAsWanted(): void
    {
        $fromPage = 'WantedPagesTest_SemanticEscaped';
        $toPage = 'WantedPagesTest SemanticTarget';
        $this->createTestPage($fromPage, "Example syntax: ~np~((alias($toPage)))~/np~");

        $this->insertLink($toPage, $fromPage);

        $this->assertStringNotContainsString(
            $toPage,
            $this->runPlugin(),
            'A semantic link inside ~np~ tags should not appear in the wanted pages list.'
        );
    }

    /**
     * Regression: one escaped occurrence used to hide every other link to the same page in that
     * document, which is exactly what documentation pages do when they show syntax as an example.
     */
    public function testEscapedAndRealLinkStillListedAsWanted(): void
    {
        $fromPage = 'WantedPagesTest_EscapedAndReal';
        $toPage = 'WantedPagesTest MixedTarget';
        $this->createTestPage($fromPage, "Written as ~np~(($toPage))~/np~ and used here: (($toPage))");

        $this->insertLink($toPage, $fromPage);

        $this->assertStringContainsString(
            $toPage,
            $this->runPlugin(),
            'A page linked for real should be listed even if it is also shown escaped elsewhere.'
        );
    }

    /**
     * Negative control: a link to a genuinely missing page must appear in the list.
     */
    public function testTrulyMissingPageListedAsWanted(): void
    {
        $this->insertLink('WantedPagesTest TrulyMissingPage');

        $output = $this->runPlugin();

        $this->assertStringContainsString(
            'WantedPagesTest TrulyMissingPage',
            $output,
            'A link to a non-existent page should appear in the wanted pages list.'
        );
    }
}
