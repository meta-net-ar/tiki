<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\RobotsTxt;

use PHPUnit\Framework\TestCase;
use Tiki\RobotsTxt\Matcher;
use Tiki\RobotsTxt\Parser;

/**
 * Tests for Parser and Matcher following Google's robots.txt specification.
 */
class MatcherTest extends TestCase
{
    /**
     * Helper method to check if a URL is allowed.
     * Uses the instance-based API via Matcher::fromString().
     */
    private function isAllowed(string $robotsContent, string $userAgent, string $pathQuery): bool
    {
        $matcher = Matcher::fromString($robotsContent);
        return $matcher->isAllowed($pathQuery, $userAgent);
    }

    /**
     * Helper method to check if a URL is disallowed.
     * Uses the instance-based API via Matcher::fromString().
     */
    private function isDisallowed(string $robotsContent, string $userAgent, string $pathQuery): bool
    {
        $matcher = Matcher::fromString($robotsContent);
        return $matcher->isDisallowed($pathQuery, $userAgent);
    }

    // =========================================================================
    // User-Agent Matching Tests
    // =========================================================================

    public function testSelectsMostSpecificUserAgentGroupWithoutInheritingStarGroup(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /private

User-agent: Googlebot
Disallow: /nogoogle
Allow: /private/google
ROBOTS;

        // Googlebot group should win (more specific), and it should not inherit Disallow from '*'
        $this->assertTrue($this->isAllowed($robots, 'Googlebot', '/private'));
        $this->assertFalse($this->isAllowed($robots, 'Googlebot', '/nogoogle'));
        $this->assertTrue($this->isAllowed($robots, 'Googlebot-Image', '/private/google/image.png'));
    }

    public function testUserAgentMatchingIsCaseInsensitive(): void
    {
        $robots = <<<'ROBOTS'
User-agent: Googlebot
Disallow: /secret
ROBOTS;

        $this->assertFalse($this->isAllowed($robots, 'googlebot', '/secret'));
        $this->assertFalse($this->isAllowed($robots, 'GOOGLEBOT', '/secret'));
        $this->assertFalse($this->isAllowed($robots, 'GoogleBot', '/secret'));
    }

    public function testUserAgentSubstringMatching(): void
    {
        $robots = <<<'ROBOTS'
User-agent: Googlebot
Disallow: /blocked
ROBOTS;

        // "Googlebot-Image/1.0" contains "googlebot" as substring
        $this->assertFalse($this->isAllowed($robots, 'Googlebot-Image/1.0', '/blocked'));
        $this->assertFalse($this->isAllowed($robots, 'Mozilla/5.0 (compatible; Googlebot/2.1)', '/blocked'));
    }

    public function testFallbackToWildcardWhenNoSpecificMatch(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /admin

User-agent: Googlebot
Disallow: /nogoogle
ROBOTS;

        // UnknownBot should fall back to '*' rules
        $this->assertFalse($this->isAllowed($robots, 'UnknownBot', '/admin'));
        $this->assertTrue($this->isAllowed($robots, 'UnknownBot', '/nogoogle'));
    }

    public function testMultipleUserAgentsShareRulesInSameGroup(): void
    {
        $robots = <<<'ROBOTS'
User-agent: Googlebot
User-agent: Bingbot
Disallow: /private
ROBOTS;

        $this->assertFalse($this->isAllowed($robots, 'Googlebot', '/private/area'));
        $this->assertFalse($this->isAllowed($robots, 'Bingbot', '/private/area'));
        $this->assertTrue($this->isAllowed($robots, 'DuckDuckBot', '/private/area'));
    }

    public function testEmptyUserAgentFallsBackToWildcard(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /blocked
ROBOTS;

        $this->assertFalse($this->isAllowed($robots, '', '/blocked'));
    }

    // =========================================================================
    // Allow/Disallow Precedence Tests
    // =========================================================================

    public function testLongestMatchWins(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /folder/
Allow: /folder/public/
ROBOTS;

        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/folder/other'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/folder/public/index.html'));
    }

    public function testAllowWinsOnTie(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /page
Allow: /page
ROBOTS;

        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/page'));
    }

    public function testAllowWinsOnTieWithEndAnchor(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /a$
Allow: /a$
ROBOTS;

        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/a'));
    }

    public function testMoreSpecificAllowOverridesLessSpecificDisallow(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /
Allow: /public/
ROBOTS;

        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/private'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/public/page.html'));
    }

    // =========================================================================
    // Empty Rules Tests
    // =========================================================================

    public function testEmptyDisallowAllowsEverything(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow:
ROBOTS;

        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/anything'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/admin'));
    }

    public function testEmptyRobotsTxtAllowsEverything(): void
    {
        $this->assertTrue($this->isAllowed('', 'AnyBot', '/anything'));
    }

    public function testNoMatchingUserAgentAllowsEverything(): void
    {
        $robots = <<<'ROBOTS'
User-agent: Googlebot
Disallow: /
ROBOTS;

        // Bingbot has no rules, so everything is allowed
        $this->assertTrue($this->isAllowed($robots, 'Bingbot', '/blocked'));
    }

    // =========================================================================
    // Wildcard Tests
    // =========================================================================

    public function testWildcardMatchesAnySequence(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /search*result
ROBOTS;

        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/searchresult'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/search_result'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/search/foo/result'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/search'));
    }

    public function testQueryStringMatchingWithWildcards(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /*?id=1$
ROBOTS;

        // With end anchor $, only exact match at end
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/page?id=1'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/foo/bar?id=1'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/page?id=2'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/page?id=10'));
    }

    public function testQueryStringMatchingWithoutEndAnchor(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /*?id=1
ROBOTS;

        // Without end anchor, this is a prefix match - ?id=1 matches ?id=10, ?id=100, etc.
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/page?id=1'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/page?id=10'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/page?id=1&foo=bar'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/page?id=2'));
    }

    public function testDisallowAllWithWildcard(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /*
Allow: /$
ROBOTS;

        // Only root is allowed
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/page'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/a'));
    }

    // =========================================================================
    // End Anchor ($) Tests
    // =========================================================================

    public function testEndAnchorMatchesExactEnd(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /exact$
ROBOTS;

        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/exact'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/exact/more'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/exactplus'));
    }

    public function testEndAnchorWithExtension(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /*.pdf$
ROBOTS;

        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/document.pdf'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/path/to/file.pdf'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/document.pdf.bak'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/document.txt'));
    }

    public function testRootOnlyWithEndAnchor(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /
Allow: /$
ROBOTS;

        // Only exactly "/" is allowed
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/any'));
    }

    // =========================================================================
    // Path Matching Tests
    // =========================================================================

    public function testPathMatchingIsCaseSensitive(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /Private
ROBOTS;

        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/Private'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/private'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/PRIVATE'));
    }

    public function testIgnoresInvalidRulesWithoutLeadingSlash(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: foo
Allow: bar
ROBOTS;

        // Rules without leading '/' are ignored per spec
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/foo'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/bar'));
    }

    public function testPrefixMatching(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /admin
ROBOTS;

        // "/admin" matches any path starting with "/admin"
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/admin'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/admin/'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/admin/users'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/administrator'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/admi'));
    }

    // =========================================================================
    // Comment Handling Tests
    // =========================================================================

    public function testInlineCommentsAreStripped(): void
    {
        $robots = <<<'ROBOTS'
User-agent: * # this is a comment
Disallow: /blocked # block this path
Allow: /allowed#notacomment
ROBOTS;

        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/blocked'));
        // Note: "/allowed#notacomment" - the # and text after is stripped as comment
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/allowed'));
    }

    public function testFullLineCommentsAreIgnored(): void
    {
        $robots = <<<'ROBOTS'
# This is a full line comment
User-agent: *
# Another comment
Disallow: /blocked
ROBOTS;

        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/blocked'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/allowed'));
    }

    // =========================================================================
    // Encoding Tests
    // =========================================================================

    public function testUtf8BomIsStripped(): void
    {
        // UTF-8 BOM followed by robots.txt content
        $robots = "\xEF\xBB\xBF" . <<<'ROBOTS'
User-agent: *
Disallow: /blocked
ROBOTS;

        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/blocked'));
    }

    public function testDifferentLineEndings(): void
    {
        // Test with Windows line endings (CRLF)
        $robotsCrlf = "User-agent: *\r\nDisallow: /blocked\r\n";
        $this->assertFalse($this->isAllowed($robotsCrlf, 'AnyBot', '/blocked'));

        // Test with old Mac line endings (CR only)
        $robotsCr = "User-agent: *\rDisallow: /blocked\r";
        $this->assertFalse($this->isAllowed($robotsCr, 'AnyBot', '/blocked'));

        // Test with Unix line endings (LF)
        $robotsLf = "User-agent: *\nDisallow: /blocked\n";
        $this->assertFalse($this->isAllowed($robotsLf, 'AnyBot', '/blocked'));
    }

    // =========================================================================
    // Group Separation Tests
    // =========================================================================

    public function testBlankLineSeparatesGroups(): void
    {
        $robots = <<<'ROBOTS'
User-agent: Googlebot
Disallow: /google-only

User-agent: Bingbot
Disallow: /bing-only
ROBOTS;

        $this->assertFalse($this->isAllowed($robots, 'Googlebot', '/google-only'));
        $this->assertTrue($this->isAllowed($robots, 'Googlebot', '/bing-only'));
        $this->assertFalse($this->isAllowed($robots, 'Bingbot', '/bing-only'));
        $this->assertTrue($this->isAllowed($robots, 'Bingbot', '/google-only'));
    }

    public function testNewUserAgentAfterRulesStartsNewGroup(): void
    {
        $robots = <<<'ROBOTS'
User-agent: Googlebot
Disallow: /google-blocked
User-agent: Bingbot
Disallow: /bing-blocked
ROBOTS;

        // Even without blank line, new user-agent after rules starts new group
        $this->assertFalse($this->isAllowed($robots, 'Googlebot', '/google-blocked'));
        $this->assertTrue($this->isAllowed($robots, 'Googlebot', '/bing-blocked'));
    }

    // =========================================================================
    // Special Characters Tests
    // =========================================================================

    public function testSpecialRegexCharactersAreEscaped(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /path?query=value
Disallow: /file.html
Disallow: /dir[1]
ROBOTS;

        // These should be literal matches, not regex patterns
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/path?query=value'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/file.html'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/dir[1]'));

        // Without the special chars, should be allowed
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/pathXquery=value'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/fileXhtml'));
    }

    // =========================================================================
    // Real-World Scenario Tests
    // =========================================================================

    public function testTypicalTikiRobotsTxt(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /tiki-admin*
Disallow: /tiki-install.php
Disallow: /*?*session*
Allow: /tiki-index.php

User-agent: Googlebot
Disallow: /tiki-admin*
Allow: /tiki-view_articles.php
ROBOTS;

        // Default bot
        $this->assertFalse($this->isAllowed($robots, 'SomeBot', '/tiki-admin.php'));
        $this->assertFalse($this->isAllowed($robots, 'SomeBot', '/tiki-install.php'));
        $this->assertFalse($this->isAllowed($robots, 'SomeBot', '/page?session=abc123'));
        $this->assertTrue($this->isAllowed($robots, 'SomeBot', '/tiki-index.php'));

        // Googlebot uses its own rules
        $this->assertFalse($this->isAllowed($robots, 'Googlebot', '/tiki-admin.php'));
        $this->assertTrue($this->isAllowed($robots, 'Googlebot', '/tiki-view_articles.php'));
        // Googlebot doesn't inherit session blocking from *
        $this->assertTrue($this->isAllowed($robots, 'Googlebot', '/page?session=abc123'));
    }

    public function testAgentVAndAgentWShareRules(): void
    {
        $robots = <<<'ROBOTS'
User-agent: agentV
User-agent: agentW
Disallow: /foo
Allow: /bar
ROBOTS;

        $this->assertFalse($this->isAllowed($robots, 'agentV', '/foo'));
        $this->assertTrue($this->isAllowed($robots, 'agentV', '/bar'));
        $this->assertFalse($this->isAllowed($robots, 'agentW', '/foo'));
        $this->assertTrue($this->isAllowed($robots, 'agentW', '/bar'));
        // /Foo is different from /foo (case sensitive paths)
        $this->assertTrue($this->isAllowed($robots, 'agentV', '/Foo'));
    }

    public function testCrawlerZAllowsOnlyRoot(): void
    {
        $robots = <<<'ROBOTS'
User-agent: crawlerZ
Disallow:
Disallow: /
Allow: /$
ROBOTS;

        $this->assertTrue($this->isAllowed($robots, 'crawlerZ', '/'));
        $this->assertFalse($this->isAllowed($robots, 'crawlerZ', '/forum'));
        $this->assertFalse($this->isAllowed($robots, 'crawlerZ', '/public'));
    }

    public function testBotYWithEndAnchorAllow(): void
    {
        $robots = <<<'ROBOTS'
User-agent: botY
Disallow: /
Allow: /forum/$
Allow: /article
ROBOTS;

        $this->assertFalse($this->isAllowed($robots, 'botY-test', '/'));
        $this->assertFalse($this->isAllowed($robots, 'botY-test', '/forum'));
        $this->assertTrue($this->isAllowed($robots, 'botY-test', '/forum/'));
        $this->assertFalse($this->isAllowed($robots, 'botY-test', '/forum/topic'));
        $this->assertTrue($this->isAllowed($robots, 'botY-test', '/article'));
        $this->assertTrue($this->isAllowed($robots, 'botY-test', '/article/123'));
    }

    // =========================================================================
    // isDisallowed Tests
    // =========================================================================

    public function testIsDisallowedIsInverseOfIsAllowed(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /blocked
ROBOTS;

        $this->assertTrue($this->isDisallowed($robots, 'AnyBot', '/blocked'));
        $this->assertFalse($this->isDisallowed($robots, 'AnyBot', '/allowed'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/blocked'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/allowed'));
    }

    // =========================================================================
    // Cache Tests
    // =========================================================================

    public function testCacheWorksCorrectly(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /blocked
ROBOTS;

        // First call builds tree and caches
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/blocked'));

        // Second call should use cache (same result)
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/blocked'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/allowed'));
    }

    // =========================================================================
    // Real-World robots.txt Structure Tests
    // =========================================================================

    public function testBlankLinesWithinGroupForReadability(): void
    {
        // Many robots.txt files use blank lines within a single group for readability
        // This should NOT create separate groups
        $robots = <<<'ROBOTS'
User-agent: *

# Section 1: Directory blocks
Disallow: /temp/
Disallow: /admin/

# Section 2: Query string blocks
Disallow: /*?sort_mode=*
Disallow: /*?print=y*
ROBOTS;

        // All rules should apply to the same * group
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/temp/file'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/admin/'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/page?sort_mode=date'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/page?print=y'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/public/page'));
    }

    public function testMultipleGroupsSeparatedByBlankLineAndNewUserAgent(): void
    {
        // Blank line followed by new User-agent should create a new group
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /private/

User-agent: Googlebot
Disallow: /nogoogle/
ROBOTS;

        // * rules
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/private/'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/nogoogle/'));

        // Googlebot rules (does NOT inherit from *)
        $this->assertTrue($this->isAllowed($robots, 'Googlebot', '/private/'));
        $this->assertFalse($this->isAllowed($robots, 'Googlebot', '/nogoogle/'));
    }

    public function testTikiRobotsTxtStructure(): void
    {
        // Simulates the structure of Tiki's robots.txt
        $robots = <<<'ROBOTS'
# Header comments
# More comments

User-agent: *
# Comment about crawl delay
Crawl-delay: 10

# Directory blocks
Disallow: /temp/
Allow: /temp/public/
Disallow: /vendor*
Allow: /vendor*.js$
Allow: /vendor*.css$

# Query string blocks
Disallow: /*?sort_mode=*
Disallow: /*?PHPSESSID=*
Disallow: /*?print=y*
ROBOTS;

        // Directory rules
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/temp/cache'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/temp/public/file'));

        // Vendor rules with end anchors
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/vendor/lib/foo.php'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/vendor/js/app.js'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/vendor/css/style.css'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/vendor/css/style.css.map'));

        // Query string rules
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/page?sort_mode=date'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/page?PHPSESSID=abc123'));
        $this->assertFalse($this->isAllowed($robots, 'AnyBot', '/page?print=y'));
        $this->assertTrue($this->isAllowed($robots, 'AnyBot', '/page?id=123'));
    }

    // =========================================================================
    // Instance-based API Tests (fromString)
    // =========================================================================

    public function testFromStringWithEmptyContent(): void
    {
        $matcher = Matcher::fromString('');
        $this->assertTrue($matcher->isAllowed('/anything', 'AnyBot'));
        $this->assertFalse($matcher->isDisallowed('/anything', 'AnyBot'));
    }

    public function testFromStringWithValidContent(): void
    {
        $robots = "User-agent: *\nDisallow: /blocked/\nAllow: /blocked/public/";
        $matcher = Matcher::fromString($robots);

        $this->assertFalse($matcher->isAllowed('/blocked/private', 'AnyBot'));
        $this->assertTrue($matcher->isAllowed('/blocked/public/file', 'AnyBot'));
        $this->assertTrue($matcher->isAllowed('/allowed/page', 'AnyBot'));
    }

    public function testIsAllowedWithEmptyUri(): void
    {
        $matcher = Matcher::fromString("User-agent: *\nDisallow: /");
        // Empty URI should be allowed by default
        $this->assertTrue($matcher->isAllowed('', 'AnyBot'));
    }

    public function testIsAllowedWithEmptyUserAgent(): void
    {
        $robots = "User-agent: *\nDisallow: /blocked/";
        $matcher = Matcher::fromString($robots);

        // Empty user agent should default to '*'
        $this->assertFalse($matcher->isAllowed('/blocked/', ''));
        $this->assertFalse($matcher->isAllowed('/blocked/', '   '));
    }

    public function testIsAllowedWithUserAgentMatching(): void
    {
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /private/

User-agent: Googlebot
Disallow: /nogoogle/
Allow: /private/
ROBOTS;
        $matcher = Matcher::fromString($robots);

        // Generic bot uses * rules
        $this->assertFalse($matcher->isAllowed('/private/', 'AnyBot'));
        $this->assertTrue($matcher->isAllowed('/nogoogle/', 'AnyBot'));

        // Googlebot uses its specific rules
        $this->assertTrue($matcher->isAllowed('/private/', 'Googlebot/2.1'));
        $this->assertFalse($matcher->isAllowed('/nogoogle/', 'Googlebot/2.1'));
    }

    public function testGetTreeReturnsInternalTree(): void
    {
        $robots = "User-agent: *\nDisallow: /blocked/";
        $matcher = Matcher::fromString($robots);
        $tree = $matcher->getTree();

        $this->assertIsArray($tree);
        $this->assertArrayHasKey('*', $tree);
        $this->assertContains('/blocked/', $tree['*']['disallow']);
    }

    // =========================================================================
    // Parser Integration Tests
    // =========================================================================

    public function testBuildTreeProducesConsistentResults(): void
    {
        // Verify that buildTree produces consistent results across multiple calls
        $robots = <<<'ROBOTS'
User-agent: *
Disallow: /temp/
Allow: /temp/public/
Disallow: /*.pdf$
Disallow: /*?sort_mode=*
ROBOTS;
        $tree1 = Parser::buildTree($robots);
        $tree2 = Parser::buildTree($robots);

        // Trees should be identical
        $this->assertSame($tree1, $tree2);

        // Results should be consistent using instance-based API
        $matcher = Matcher::fromString($robots);
        $testCases = [
            ['/temp/cache', 'AnyBot', false],
            ['/temp/public/file', 'AnyBot', true],
            ['/document.pdf', 'AnyBot', false],
            ['/document.pdf.bak', 'AnyBot', true],
            ['/page?sort_mode=date', 'AnyBot', false],
            ['/page?id=123', 'AnyBot', true],
            ['/allowed', 'AnyBot', true],
        ];

        foreach ($testCases as [$path, $ua, $expected]) {
            $result = $matcher->isAllowed($path, $ua);
            $this->assertSame(
                $expected,
                $result,
                "Expected " . ($expected ? 'allowed' : 'disallowed') . " for path '$path'"
            );
        }
    }
}
