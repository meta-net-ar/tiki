<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\WikiPlugins;

use TikiTestCase;
use TikiLib;

require_once(__DIR__ . '/../../wiki-plugins/wikiplugin_wantedpages.php');
require_once(__DIR__ . '/../../test/TestHelpers.php');

class WikiPluginWantedPagesTest extends TikiTestCase
{
    private $orig_user;
    private array $testPages = [];

    // Unique fromPage used in all test link inserts so tearDown can clean precisely
    private string $testFromPage = 'WantedPagesTest_FromPage';

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

        unset($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']);
        $user = $this->orig_user;
    }

    private function createTestPage(string $name): void
    {
        (new \TestHelpers())->createPage($name, 0, 'Test content');
        $this->testPages[] = $name;
    }

    private function insertLink(string $toPage): void
    {
        TikiLib::lib('tiki')->replace_link($this->testFromPage, $toPage);
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
