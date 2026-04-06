<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Lib\HeaderLib;

use HeaderLib;
use TikiTestCase;

/**
 * Tests for HeaderLib robots.txt integration.
 *
 * These tests verify that the getRobots() method correctly parses robots.txt
 * and returns appropriate NOINDEX directives based on the current URL.
 */
class HeaderLibTest extends TikiTestCase
{
    private ?HeaderLib $headerLib;
    private string $robotsTxtPath;
    private ?string $originalRequestUri;
    private ?string $originalRobotsTxtContent = null;

    /**
     * This method is called before each test.
     * It creates a temporary, controlled robots.txt file and backs up the server state.
     */
    protected function setUp(): void
    {
        $this->headerLib = new HeaderLib();
        $this->originalRequestUri = $_SERVER['REQUEST_URI'] ?? null;
        $this->robotsTxtPath = realpath(__DIR__ . '/../../../') . '/robots.txt';

        // Backup original robots.txt if it exists
        if (file_exists($this->robotsTxtPath)) {
            $this->originalRobotsTxtContent = file_get_contents($this->robotsTxtPath);
        }

        // Create test robots.txt
        $testRobotsContent = <<<'ROBOTS'
User-agent: *
# Rule for the original bug - block script but not SEF URLs
Disallow: /tiki-index.php
# Rule for simple directory matching
Disallow: /private/
# Rules for specificity test (longest match wins)
Allow: /private/public/
Disallow: /private/public/secret.html
# Rule for wildcard and end-of-path test
Disallow: /*.pdf$
# Rule with wildcard for query strings
Disallow: /*?sort_mode=*
# Rule with no path, should be ignored
Disallow:
ROBOTS;

        file_put_contents($this->robotsTxtPath, $testRobotsContent);
    }

    /**
     * This method is called after each test.
     * It restores the original robots.txt file and server state.
     */
    protected function tearDown(): void
    {
        // Restore original robots.txt
        if ($this->originalRobotsTxtContent !== null) {
            file_put_contents($this->robotsTxtPath, $this->originalRobotsTxtContent);
        } elseif (file_exists($this->robotsTxtPath)) {
            // If there was no original file, we could delete it, but safer to leave test content
        }

        if ($this->originalRequestUri !== null) {
            $_SERVER['REQUEST_URI'] = $this->originalRequestUri;
        } else {
            unset($_SERVER['REQUEST_URI']);
        }
        $this->headerLib = null;
    }

    private function setRequestUri(string $uri): void
    {
        $_SERVER['REQUEST_URI'] = $uri;
    }

    /**
     * Test Case 1: SEF URL should be allowed when only base script is blocked.
     * This is the core fix from AVT-1372.
     */
    public function testSefUrlIsAllowedWhenBaseScriptIsDisallowed(): void
    {
        $this->setRequestUri('/HomePage');
        $this->assertNull($this->headerLib->getRobots(), "SEF URL '/HomePage' should be allowed.");
    }

    /**
     * Test Case 1b: Direct PHP script access should be blocked.
     */
    public function testDirectPhpScriptIsDisallowed(): void
    {
        $this->setRequestUri('/tiki-index.php');
        $this->assertEquals('NOINDEX, NOFOLLOW', $this->headerLib->getRobots(), "Direct script '/tiki-index.php' should be disallowed.");
    }

    /**
     * Test Case 1c: PHP script with query string should be blocked.
     */
    public function testPhpScriptWithQueryStringIsDisallowed(): void
    {
        $this->setRequestUri('/tiki-index.php?page=HomePage');
        $this->assertEquals('NOINDEX, NOFOLLOW', $this->headerLib->getRobots(), "Script with query '/tiki-index.php?page=HomePage' should be disallowed.");
    }

    /**
     * Test Case 7: Longest match wins - more specific Disallow.
     */
    public function testLongestMatchWinsDisallow(): void
    {
        $this->setRequestUri('/private/public/secret.html');
        $this->assertEquals('NOINDEX, NOFOLLOW', $this->headerLib->getRobots(), "Specificity test: The most specific Disallow rule should win.");
    }

    /**
     * Test Case 7: Longest match wins - more specific Allow.
     */
    public function testLongestMatchWinsAllow(): void
    {
        $this->setRequestUri('/private/public/some-other-page.html');
        $this->assertNull($this->headerLib->getRobots(), "Specificity test: The most specific Allow rule should win.");
    }

    /**
     * Test Case 8: Wildcard with end anchor should match.
     */
    public function testWildcardAndEndOfPathIsDisallowed(): void
    {
        $this->setRequestUri('/any/folder/document.pdf');
        $this->assertEquals('NOINDEX, NOFOLLOW', $this->headerLib->getRobots(), "Wildcard rule '/*.pdf$' should disallow the URL.");
    }

    /**
     * Test Case 8b: URL not matching wildcard end anchor should be allowed.
     */
    public function testUrlNotMatchingWildcardEndAnchorIsAllowed(): void
    {
        $this->setRequestUri('/any/folder/document.pdf-and-more');
        $this->assertNull($this->headerLib->getRobots(), "URL should not match '/*.pdf\$' because of extra characters.");
    }

    /**
     * Test Case 8c: Wildcard for query strings should match.
     */
    public function testWildcardQueryStringIsDisallowed(): void
    {
        $this->setRequestUri('/tiki-listpages.php?sort_mode=lastModif_desc');
        $this->assertEquals('NOINDEX, NOFOLLOW', $this->headerLib->getRobots(), "Wildcard rule '/*?sort_mode=*' should disallow the URL.");
    }

    /**
     * Test: URL with no matching rules should be allowed.
     */
    public function testNoMatchingRulesIsAllowed(): void
    {
        $this->setRequestUri('/a-completely-unrelated-page');
        $this->assertNull($this->headerLib->getRobots(), "A URL with no matching rules should be allowed by default.");
    }

    /**
     * Test: Simple directory blocking works.
     */
    public function testSimpleDirectoryBlockingWorks(): void
    {
        $this->setRequestUri('/private/secret-file.html');
        $this->assertEquals('NOINDEX, NOFOLLOW', $this->headerLib->getRobots(), "Directory '/private/' should be blocked.");
    }

    /**
     * Test: Query string rules are enforced.
     */
    public function testQueryStringRulesAreEnforced(): void
    {
        // Create a robots.txt with a specific query string rule
        $robotsContent = "User-agent: *\nDisallow: /tiki-index.php?page=sensitive";
        file_put_contents($this->robotsTxtPath, $robotsContent);

        $this->setRequestUri('/tiki-index.php?page=sensitive');
        $result = $this->headerLib->getRobots();

        $this->assertEquals('NOINDEX, NOFOLLOW', $result, "Rules containing query strings should be enforced.");
    }

    /**
     * Test: Fragment (#) in URL is ignored for matching.
     */
    public function testFragmentIsIgnoredInMatching(): void
    {
        $this->setRequestUri('/tiki-index.php#section');
        $this->assertEquals('NOINDEX, NOFOLLOW', $this->headerLib->getRobots(), "Fragment should be stripped; script should still be blocked.");
    }

    /**
     * Test: Empty REQUEST_URI returns null.
     */
    public function testEmptyRequestUriReturnsNull(): void
    {
        $_SERVER['REQUEST_URI'] = '';
        $this->assertNull($this->headerLib->getRobots(), "Empty REQUEST_URI should return null.");
    }
}
