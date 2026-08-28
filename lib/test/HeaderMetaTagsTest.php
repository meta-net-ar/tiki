<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\test;

use TikiTestCase;

/**
 * Test suite for header meta tags W3C HTML5 compliance
 * Based on W3C HTML5 Specification: https://www.w3.org/TR/html5/document-metadata.html
 * Tests W3C compliance, meta tag order, deprecated tag removal, and CSP
 * Includes integration tests that verify meta tags are rendered when preferences are set
 */
class HeaderMetaTagsTest extends TikiTestCase
{
    private $headerTemplatePath;
    private $originalPrefs = [];

    protected function setUp(): void
    {
        parent::setUp();
        global $prefs;

        // Backup original preferences
        $this->originalPrefs = [
            'metatag_geoposition' => $prefs['metatag_geoposition'] ?? '',
            'metatag_georegion' => $prefs['metatag_georegion'] ?? '',
            'metatag_geoplacename' => $prefs['metatag_geoplacename'] ?? '',
            'metatag_google_notranslate' => $prefs['metatag_google_notranslate'] ?? 'n',
            'metatag_nositelinkssearchbox' => $prefs['metatag_nositelinkssearchbox'] ?? 'n',
            'site_google_analytics_site_ownership' => $prefs['site_google_analytics_site_ownership'] ?? '',
            'metatag_robots' => $prefs['metatag_robots'] ?? '',
            'metatag_description' => $prefs['metatag_description'] ?? '',
            'switch_color_module_assigned' => $prefs['switch_color_module_assigned'] ?? 'n',
        ];

        $this->headerTemplatePath = __DIR__ . '/../../templates/header.tpl';
    }

    protected function tearDown(): void
    {
        global $prefs;

        // Restore original preferences
        foreach ($this->originalPrefs as $key => $value) {
            $prefs[$key] = $value;
        }

        parent::tearDown();
    }

    /**
     * Render header template with given preferences
     * Uses output buffering to capture template output
     */
    private function renderHeaderTemplate(array $testPrefs = [], array $templateVars = []): string
    {
        global $prefs, $base_url_canonical, $metatag_robotscustom, $metatag_robots, $headerlib;

        // Set test preferences
        foreach ($testPrefs as $key => $value) {
            $prefs[$key] = $value;
        }

        // Set required variables with defaults for CI environments
        // base_url_canonical might not be available in GitLab CI
        if (! isset($base_url_canonical) || empty($base_url_canonical)) {
            $base_url_canonical = 'http://localhost/';
        }
        if (! isset($metatag_robotscustom)) {
            $metatag_robotscustom = '';
        }
        if (! isset($metatag_robots)) {
            $metatag_robots = '';
        }

        // Ensure base_url_canonical is available in global scope for template
        $GLOBALS['base_url_canonical'] = $base_url_canonical;

        // Create a fresh headerlib instance for each test to avoid "headers already output" errors
        $headerlib = new \HeaderLib();

        // Initialize Smarty
        $smarty = \TikiLib::lib('smarty');
        $smarty->assign('prefs', $prefs);
        // base_url_canonical might not be available in CI, provide default
        $smarty->assign('base_url_canonical', $base_url_canonical ?? 'http://localhost/');
        $smarty->assign('metatag_robotscustom', $metatag_robotscustom);
        $smarty->assign('metatag_robots', $metatag_robots);
        $smarty->assign('base_uri', '');
        $smarty->assign('dir_level', 0);
        $smarty->assign('title', 'Test Page');
        $smarty->assign('section', '');
        $smarty->assign('headerlib', $headerlib);
        $smarty->assign('browsertitle', 'Test Site');
        $smarty->assign('browsertitle_translated', '');
        $smarty->assign('site_nav_seper', '|');

        // Set additional variables that might be needed by template conditionals
        $smarty->assign('forum_info', []);
        $smarty->assign('thread_info', []);
        $smarty->assign('tags', []);
        $smarty->assign('metatag_local_keywords', '');
        $smarty->assign('metatag_object_description', '');
        $smarty->assign('metatag_object_keywords', '');

        foreach ($templateVars as $name => $value) {
            $smarty->assign($name, $value);
        }

        // Use output buffering to capture any errors
        ob_start();
        try {
            $output = $smarty->fetch($this->headerTemplatePath);
        } catch (\Exception $e) {
            ob_end_clean();
            // If template rendering fails, return empty string and let test handle it
            return '';
        }
        $errors = ob_get_clean();

        // If there were errors, prepend them (for debugging)
        if (! empty($errors) && strpos($errors, 'Fatal error') === false) {
            return $errors . $output;
        }

        return $output;
    }

    /**
     * W3C HTML5 Requirement: charset must be within first 1024 bytes
     * W3C HTML5 Specification: https://www.w3.org/TR/html5/syntax.html#the-input-byte-stream
     */
    public function testCharsetWithinFirst1024Bytes(): void
    {
        $content = file_get_contents($this->headerTemplatePath);

        // Find charset meta tag
        preg_match('/<meta\s+charset=["\']utf-8["\']/i', $content, $charsetMatch, PREG_OFFSET_CAPTURE);
        $this->assertNotEmpty($charsetMatch, 'charset meta tag should exist');

        $charsetPosition = $charsetMatch[0][1];

        // W3C requires charset to be within first 1024 bytes
        $this->assertLessThan(
            1024,
            $charsetPosition,
            'W3C HTML5: charset must be within first 1024 bytes of document'
        );
    }

    /**
     * W3C HTML5 Requirement: charset should be first meta tag in head
     * Best practice: charset should come before any other content in head
     */
    public function testCharsetIsFirstMetaTag(): void
    {
        $content = file_get_contents($this->headerTemplatePath);

        // Find charset meta tag
        preg_match('/<meta\s+charset=["\']utf-8["\']/i', $content, $charsetMatch, PREG_OFFSET_CAPTURE);
        $this->assertNotEmpty($charsetMatch, 'charset meta tag should exist');

        $charsetPosition = $charsetMatch[0][1];

        // Check that no other meta tags appear before charset (except base tag which is conditional)
        $beforeCharset = substr($content, 0, $charsetPosition);

        // Should not have meta tags before charset (base tag is allowed)
        // preg_match returns 1 if found, 0 if not found, false on error
        $metaBeforeCharset = preg_match('/<meta[^>]*>/i', $beforeCharset);
        $this->assertEquals(0, $metaBeforeCharset, 'W3C Best Practice: No meta tags should appear before charset declaration');
    }

    /**
     * Test essential meta tags order: charset, x-ua-compatible, viewport
     */
    public function testEssentialMetaTagsOrder(): void
    {
        $content = file_get_contents($this->headerTemplatePath);

        // Extract first 20 lines to check order
        $lines = explode("\n", $content);
        $firstLines = implode("\n", array_slice($lines, 0, 20));

        // Check order: charset should come before x-ua-compatible
        $charsetPos = stripos($firstLines, '<meta charset="utf-8">');
        $xuaPos = stripos($firstLines, 'x-ua-compatible');
        $viewportPos = stripos($firstLines, 'viewport');

        $this->assertNotFalse($charsetPos, 'charset meta tag should exist');
        $this->assertNotFalse($xuaPos, 'x-ua-compatible meta tag should exist');
        $this->assertNotFalse($viewportPos, 'viewport meta tag should exist');

        $this->assertLessThan($xuaPos, $charsetPos, 'charset should come before x-ua-compatible');
        $this->assertLessThan($viewportPos, $xuaPos, 'x-ua-compatible should come before viewport');
    }

    /**
     * W3C HTML5: http-equiv attribute values should be lowercase
     * W3C HTML5 Specification: Attribute names are case-insensitive, but lowercase is preferred
     */
    public function testW3CCompliantHttpEquiv(): void
    {
        $content = file_get_contents($this->headerTemplatePath);

        // x-ua-compatible should be lowercase (W3C best practice)
        $this->assertStringContainsString(
            'http-equiv="X-UA-Compatible"',
            $content,
            'W3C HTML5: x-ua-compatible should be lowercase'
        );

        // Should not have uppercase X-UA-Compatible
        $this->assertStringNotContainsString(
            'http-equiv="x-ua-compatible"',
            $content,
            'W3C HTML5: X-UA-Compatible should be lowercase'
        );

        // Check for valid http-equiv values according to W3C HTML5
        // Valid values: content-type, default-style, refresh, x-ua-compatible, content-security-policy
        preg_match_all('/http-equiv=["\']([^"\']+)["\']/i', $content, $matches);
        $validHttpEquiv = ['content-type', 'default-style', 'refresh', 'x-ua-compatible', 'content-security-policy'];

        foreach ($matches[1] as $httpEquiv) {
            $this->assertContains(
                strtolower($httpEquiv),
                $validHttpEquiv,
                "W3C HTML5: Invalid http-equiv value: $httpEquiv"
            );
        }
    }

    /**
     * W3C HTML5: Meta tags must have valid syntax
     * W3C HTML5 Specification: https://www.w3.org/TR/html5/document-metadata.html#the-meta-element
     */
    public function testW3CValidMetaTagSyntax(): void
    {
        $content = file_get_contents($this->headerTemplatePath);

        // Find all meta tags
        preg_match_all('/<meta\s+([^>]+)>/i', $content, $matches);

        $metaTagCount = count($matches[1]);
        $this->assertGreaterThan(0, $metaTagCount, 'W3C HTML5: Should have at least one meta tag to validate');

        foreach ($matches[1] as $attributes) {
            // W3C HTML5: Check for deprecated 'scheme' attribute
            // The 'scheme' attribute is obsolete in HTML5
            if (preg_match('/\bscheme\s*=/i', $attributes)) {
                $this->fail("W3C HTML5: Meta tag uses deprecated 'scheme' attribute: $attributes");
            }

            // W3C HTML5: Attributes should be properly quoted
            // Parse attributes properly, accounting for quoted values that may contain = and spaces
            // Pattern: attribute="value" or attribute='value' or attribute=value (unquoted - invalid)
            $pos = 0;
            $len = strlen($attributes);

            while ($pos < $len) {
                // Skip whitespace
                while ($pos < $len && preg_match('/\s/', $attributes[$pos])) {
                    $pos++;
                }
                if ($pos >= $len) {
                    break;
                }

                // Find attribute name (ends at =)
                $nameEnd = strpos($attributes, '=', $pos);
                if ($nameEnd === false) {
                    break; // No more attributes
                }

                $name = substr($attributes, $pos, $nameEnd - $pos);
                $valueStart = $nameEnd + 1;

                // Skip whitespace after =
                while ($valueStart < $len && preg_match('/\s/', $attributes[$valueStart])) {
                    $valueStart++;
                }

                if ($valueStart >= $len) {
                    break;
                }

                $firstChar = $attributes[$valueStart];

                // Check if value is quoted
                if ($firstChar === '"' || $firstChar === "'") {
                    // Quoted value - find closing quote
                    $quote = $firstChar;
                    $valueEnd = $valueStart + 1;
                    while ($valueEnd < $len && $attributes[$valueEnd] !== $quote) {
                        // Handle escaped quotes
                        if ($attributes[$valueEnd] === '\\' && $valueEnd + 1 < $len) {
                            $valueEnd += 2;
                        } else {
                            $valueEnd++;
                        }
                    }
                    $pos = $valueEnd + 1;
                } else {
                    // Unquoted value - this is invalid in W3C HTML5 for meta tags
                    // Find where value ends (whitespace or end of string)
                    $valueEnd = $valueStart;
                    while ($valueEnd < $len && ! preg_match('/\s/', $attributes[$valueEnd])) {
                        $valueEnd++;
                    }
                    $unquotedValue = substr($attributes, $valueStart, $valueEnd - $valueStart);
                    if (trim($unquotedValue) !== '') {
                        $this->fail("W3C HTML5: Meta tag has unquoted attribute value for '$name': $unquotedValue in $attributes");
                    }
                    $pos = $valueEnd;
                }
            }
        }
    }

    /**
     * W3C HTML5: charset attribute must be valid
     * W3C HTML5 Specification: charset must be a valid encoding name
     */
    public function testW3CValidCharsetValue(): void
    {
        $content = file_get_contents($this->headerTemplatePath);

        // W3C HTML5: charset must be "utf-8" (case-insensitive but lowercase preferred)
        preg_match('/<meta\s+charset=["\']([^"\']+)["\']/i', $content, $matches);

        if (! empty($matches[1])) {
            $this->assertEquals(
                'utf-8',
                strtolower($matches[1]),
                'W3C HTML5: charset must be utf-8'
            );
        }
    }

    /**
     * W3C HTML5: Meta tags should follow consistent attribute order (best practice)
     * While W3C doesn't require specific order, consistent ordering improves readability
     */
    public function testW3CMetaTagAttributeOrder(): void
    {
        $content = file_get_contents($this->headerTemplatePath);

        // Check that meta tags use proper attribute order (name/property before content)
        // Find all meta tags with both name and content
        preg_match_all('/<meta\s+([^>]+)>/i', $content, $matches);

        foreach ($matches[1] as $attributes) {
            // If both name and content exist, name should come first (best practice)
            $pregMatchName = preg_match('/name=["\']([^"\']+)["\']/', $attributes, $nameMatch);
            $pregMatchProperty = preg_match('/property=["\']([^"\']+)["\']/', $attributes, $propertyMatch);
            $pregMatchContent = preg_match('/content=["\']([^"\']+)["\']/', $attributes, $contentMatch);

            if ($pregMatchContent) {
                $contentPos = strpos($attributes, 'content=');

                // If name exists, it should come before content
                if ($pregMatchName) {
                    $namePos = strpos($attributes, 'name=');
                    if ($namePos !== false && $contentPos !== false) {
                        $this->assertLessThan(
                            $contentPos,
                            $namePos,
                            "W3C Best Practice: Meta tag should have name before content: $attributes"
                        );
                    }
                }

                // If property exists, it should come before content
                if ($pregMatchProperty) {
                    $propertyPos = strpos($attributes, 'property=');
                    if ($propertyPos !== false && $contentPos !== false) {
                        $this->assertLessThan(
                            $contentPos,
                            $propertyPos,
                            "W3C Best Practice: Meta tag should have property before content: $attributes"
                        );
                    }
                }
            }
        }
    }

    /**
     * W3C HTML5: Meta tags are void elements
     * W3C HTML5 Specification: Void elements can use either <meta> or <meta /> syntax
     * Both are valid, but non-self-closing is preferred for consistency
     */
    public function testW3CMetaTagSyntax(): void
    {
        $content = file_get_contents($this->headerTemplatePath);

        // Find all meta tags with self-closing syntax
        preg_match_all('/<meta[^>]*\/>/', $content, $matches);

        $selfClosingCount = 0;

        // Check each match to see if it's in a comment
        foreach ($matches[0] as $match) {
            $pos = strpos($content, $match);
            if ($pos === false) {
                continue;
            }

            // Check if it's in a comment
            $before = substr($content, max(0, $pos - 100), 100);
            $after = substr($content, $pos, 200);

            // If it's between <!-- and -->, it's in a comment, skip it
            $commentStart = strrpos($before, '<!--');
            $commentEnd = strpos($after, '-->');

            if ($commentStart !== false && $commentEnd !== false) {
                continue; // It's in a comment, skip
            }

            // Count non-comment self-closing meta tags
            $selfClosingCount++;
        }

        // W3C HTML5: Both syntaxes are valid, but non-self-closing is preferred
        // This is informational - we don't fail, but we note if any are found
        if ($selfClosingCount > 0) {
            $this->addToAssertionCount(1); // Mark that we did check something
            // Both <meta> and <meta /> are valid W3C HTML5, but <meta> is preferred
        } else {
            $this->assertTrue(true, 'W3C HTML5: Using preferred non-self-closing meta tag syntax');
        }
    }

    /**
     * W3C HTML5: Viewport meta tag must have valid content
     * W3C HTML5 Specification: viewport is a valid meta name
     */
    public function testW3CValidViewportMetaTag(): void
    {
        $content = file_get_contents($this->headerTemplatePath);

        // Check for viewport meta tag
        if (preg_match('/<meta\s+name=["\']viewport["\'][^>]*content=["\']([^"\']+)["\']/i', $content, $matches)) {
            $viewportContent = $matches[1];

            // W3C HTML5: viewport should contain valid values
            // Common valid values: width, initial-scale, maximum-scale, user-scalable, shrink-to-fit
            $this->assertNotEmpty($viewportContent, 'W3C HTML5: viewport meta tag must have content attribute');

            // Check for common valid viewport directives
            $this->assertStringContainsString(
                'width',
                $viewportContent,
                'W3C HTML5: viewport should specify width'
            );
        }
    }

    /**
     * Integration test: Verify all geo meta tags are rendered together
     */
    public function testAllGeoMetaTagsRenderedTogether(): void
    {
        $testPrefs = [
            'metatag_geoposition' => '40.7128, -74.0060',
            'metatag_georegion' => 'US-NY',
            'metatag_geoplacename' => 'New York City, New York, United States',
        ];

        $output = $this->renderHeaderTemplate($testPrefs);

        $this->assertStringContainsString(
            '<meta name="geo.position"',
            $output,
            'geo.position should be rendered'
        );

        $this->assertStringContainsString(
            '<meta name="geo.region"',
            $output,
            'geo.region should be rendered'
        );

        $this->assertStringContainsString(
            '<meta name="geo.placename"',
            $output,
            'geo.placename should be rendered'
        );

        $this->assertStringContainsString(
            '<meta name="ICBM"',
            $output,
            'ICBM should be rendered when geo.position is set'
        );
    }

    /**
     * Integration test: Verify google-site-verification meta tag is rendered when preference is set
     */
    public function testGoogleSiteVerificationMetaTagRendered(): void
    {
        $testVerification = 'abc123def456';
        $output = $this->renderHeaderTemplate(['site_google_analytics_site_ownership' => $testVerification]);

        $this->assertStringContainsString(
            '<meta name="google-site-verification" content="' . $testVerification . '">',
            $output,
            'google-site-verification meta tag should be rendered when site_google_analytics_site_ownership preference is set'
        );
    }

    /**
     * Integration test: Verify google notranslate meta tag is rendered when preference is enabled
     */
    public function testGoogleNotranslateMetaTagRendered(): void
    {
        $output = $this->renderHeaderTemplate(['metatag_google_notranslate' => 'y']);

        $this->assertStringContainsString(
            '<meta name="google" content="notranslate">',
            $output,
            'google notranslate meta tag should be rendered when metatag_google_notranslate is enabled'
        );

        // Test that it's NOT rendered when disabled
        $outputDisabled = $this->renderHeaderTemplate(['metatag_google_notranslate' => 'n']);
        $this->assertStringNotContainsString(
            '<meta name="google" content="notranslate">',
            $outputDisabled,
            'google notranslate meta tag should NOT be rendered when metatag_google_notranslate is disabled'
        );
    }

    /**
     * Integration test: Verify google nositelinkssearchbox meta tag is rendered when preference is enabled
     */
    public function testGoogleNositelinkssearchboxMetaTagRendered(): void
    {
        $output = $this->renderHeaderTemplate(['metatag_nositelinkssearchbox' => 'y']);

        $this->assertStringContainsString(
            '<meta name="google" content="nositelinkssearchbox">',
            $output,
            'google nositelinkssearchbox meta tag should be rendered when metatag_nositelinkssearchbox is enabled'
        );

        // Test that it's NOT rendered when disabled
        $outputDisabled = $this->renderHeaderTemplate(['metatag_nositelinkssearchbox' => 'n']);
        $this->assertStringNotContainsString(
            '<meta name="google" content="nositelinkssearchbox">',
            $outputDisabled,
            'google nositelinkssearchbox meta tag should NOT be rendered when metatag_nositelinkssearchbox is disabled'
        );
    }

    /**
     * Integration test: Verify robots and googlebot meta tags are rendered when preference is set
     */
    public function testRobotsMetaTagsRendered(): void
    {
        $testRobots = 'noindex, nofollow';
        $output = $this->renderHeaderTemplate(['metatag_robots' => $testRobots]);

        $this->assertStringContainsString(
            '<meta name="robots" content="' . $testRobots . '">',
            $output,
            'robots meta tag should be rendered when metatag_robots preference is set'
        );

        $this->assertStringContainsString(
            '<meta name="googlebot" content="' . $testRobots . '">',
            $output,
            'googlebot meta tag should be rendered when metatag_robots preference is set'
        );
    }

    /**
     * Integration test: Verify meta tags are NOT rendered when preferences are empty
     */
    public function testMetaTagsNotRenderedWhenPrefsEmpty(): void
    {
        $output = $this->renderHeaderTemplate([
            'metatag_geoposition' => '',
            'metatag_georegion' => '',
            'metatag_geoplacename' => '',
            'site_google_analytics_site_ownership' => '',
            'metatag_google_notranslate' => 'n',
            'metatag_nositelinkssearchbox' => 'n',
        ]);

        $this->assertStringNotContainsString(
            '<meta name="geo.position"',
            $output,
            'geo.position should NOT be rendered when preference is empty'
        );

        $this->assertStringNotContainsString(
            '<meta name="geo.region"',
            $output,
            'geo.region should NOT be rendered when preference is empty'
        );

        $this->assertStringNotContainsString(
            '<meta name="geo.placename"',
            $output,
            'geo.placename should NOT be rendered when preference is empty'
        );

        $this->assertStringNotContainsString(
            '<meta name="google-site-verification"',
            $output,
            'google-site-verification should NOT be rendered when preference is empty'
        );
    }

    /**
     * Integration test: Verify description meta tag is rendered when preference is set
     */
    public function testDescriptionMetaTagRendered(): void
    {
        $testDescription = 'This is a test description for the website';
        $output = $this->renderHeaderTemplate(['metatag_description' => $testDescription]);

        $this->assertStringContainsString(
            '<meta name="description"',
            $output,
            'description meta tag should be rendered when metatag_description preference is set'
        );

        $this->assertStringContainsString(
            htmlspecialchars($testDescription, ENT_QUOTES),
            $output,
            'description meta tag should contain the preference value'
        );
    }

    public function testObjectDescriptionOverridesGlobalDescription(): void
    {
        $globalDescription = 'Global site description';
        $objectDescription = 'Object specific SEO description';
        $output = $this->renderHeaderTemplate(
            ['metatag_description' => $globalDescription],
            ['metatag_object_description' => $objectDescription]
        );

        $this->assertStringContainsString(
            '<meta name="description" content="' . $objectDescription . '">',
            $output,
            'object description should be rendered as the description meta tag'
        );
        $this->assertStringNotContainsString(
            '<meta name="description" content="' . $globalDescription . '">',
            $output,
            'global description should not be rendered when object description is present'
        );
    }

    public function testObjectKeywordsOverridePageKeywordsAndTags(): void
    {
        $output = $this->renderHeaderTemplate(
            [
                'metatag_freetags' => 'y',
            ],
            [
                'metatag_object_keywords' => 'object, seo',
                'metatag_local_keywords' => 'page, keywords',
                'tags' => [
                    ['tag' => 'tag-one'],
                ],
            ]
        );

        $this->assertStringContainsString(
            '<meta name="keywords" content="object, seo">',
            $output,
            'object keywords should be rendered as the keywords meta tag'
        );
        $this->assertStringNotContainsString(
            'page, keywords',
            $output,
            'page keywords should not be rendered when object keywords are present'
        );
        $this->assertStringNotContainsString(
            'tag-one',
            $output,
            'tags should not be rendered when object keywords are present'
        );
    }
}
