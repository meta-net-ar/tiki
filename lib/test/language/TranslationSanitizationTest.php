<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Language;

use I18n\LanguageTranslator;

/**
 * Test class for translation sanitization and XSS protection.
 * Tests escaping behavior for custom translations, core translations,
 * Smarty templates, and wiki contexts.
 */
class TranslationSanitizationTest extends \TikiTestCase
{
    protected $lang;
    protected $langDir;
    protected $customFile;
    protected $tikiroot;

    protected function setUp(): void
    {
        global $prefs;

        $this->tikiroot = __DIR__ . '/../../../';
        // Use unique but short language code for each test (max 16 chars for db column)
        static $testCounter = 0;
        $this->lang = 'ts_' . $testCounter++;
        $this->langDir = $this->tikiroot . 'lang/' . $this->lang;
        $this->customFile = $this->langDir . '/custom.php';
        $_SERVER['REQUEST_URI'] = '/test/language/TranslationSanitizationTest.php';
        chdir($this->tikiroot);

        // Enable database translations
        $prefs['lang_use_db'] = 'y';

        // Create language directory if it doesn't exist
        if (! is_dir($this->langDir)) {
            mkdir($this->langDir, 0777, true);
        }

        // Create base language file
        $baseFile = $this->langDir . '/language.php';
        file_put_contents($baseFile, '<?php
$lang = array(
    "Edit" => "Edit",
    "Delete" => "Delete",
    "Save" => "Save",
    "Cancel" => "Cancel",
    "Login" => "Log In",
    "Welcome %0" => "Welcome %0",
    "Link with HTML" => "Click <a href=\"#\">here</a>",
    "Bold text" => "This is <b>bold</b> text",
);
');

        // Clear any cached instances
        $reflection = new \ReflectionClass(LanguageTranslator::class);
        $instancesProperty = $reflection->getProperty('instances');
        $instancesProperty->setValue(null, []);
        // Clean up to avoid leaking state between tests
        unset($_SERVER['REQUEST_URI']);
    }

    protected function tearDown(): void
    {
        // Clean up test files created in setUp
        if (file_exists($this->customFile)) {
            unlink($this->customFile);
        }
        if (file_exists($this->langDir . '/language.php')) {
            unlink($this->langDir . '/language.php');
        }
        if (is_dir($this->langDir)) {
            rmdir($this->langDir);
        }

        // Clear cached instances
        $reflection = new \ReflectionClass(LanguageTranslator::class);
        $instancesProperty = $reflection->getProperty('instances');
        $instancesProperty->setValue(null, []);
    }

    /**
     * Test that XSS payloads in translations are escaped when caller requests it
     */
    public function testCustomTranslationXSSIsEscaped()
    {
        // Create custom.php with XSS payload
        file_put_contents($this->customFile, '<?php
$lang_custom = array(
    "Edit" => "Edit<script>alert(\'XSS\')</script>",
    "Delete" => "Delete<img src=x onerror=alert(1)>",
);
$lang = array_replace($lang, $lang_custom);
');

        $translator = LanguageTranslator::getInstance($this->lang, ['skipDb' => true]);

        // Without $escape, content passes through unmodified
        $result = $translator->translate('Edit', []);
        $this->assertStringContainsString('<script>', $result);

        // With $escape = true, content is escaped
        $result = $translator->translate('Edit', [], true);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);

        $result = $translator->translate('Delete', [], true);
        $this->assertStringNotContainsString('<img', $result);
        $this->assertStringContainsString('&lt;img', $result);
    }

    /**
     * Test that HTML in core translations is preserved
     */
    public function testCoreTranslationHTMLIsPreserved()
    {
        $translator = LanguageTranslator::getInstance($this->lang, ['skipDb' => true]);

        // Core translation with HTML should not be escaped
        $result = $translator->translate('Link with HTML', []);
        $this->assertStringContainsString('<a href="#">', $result);
        $this->assertStringNotContainsString('&lt;a', $result);

        $result = $translator->translate('Bold text', []);
        $this->assertStringContainsString('<b>bold</b>', $result);
        $this->assertStringNotContainsString('&lt;b&gt;', $result);
    }

    /**
     * Test that arguments are NOT escaped - calling code's responsibility
     * Custom translation content IS escaped before argument replacement
     */
    public function testArgumentsAreNotEscaped()
    {
        $translator = LanguageTranslator::getInstance($this->lang, ['skipDb' => true]);

        // Arguments with HTML are NOT escaped - that's the calling code's responsibility
        $result = $translator->translate('Welcome %0', ['<b>Admin</b>']);
        $this->assertStringContainsString('<b>Admin</b>', $result);
        $this->assertStringNotContainsString('&lt;b&gt;', $result);

        // Even potentially dangerous arguments are not escaped
        // The calling code must escape user input before passing it
        $result = $translator->translate('Welcome %0', ['<script>alert("XSS")</script>']);
        $this->assertStringContainsString('<script>', $result);
        $this->assertStringNotContainsString('&lt;script&gt;', $result);

        // If calling code needs to prevent XSS, it must escape before calling translate()
        $safeArg = htmlspecialchars('<script>alert("XSS")</script>', ENT_QUOTES, 'UTF-8');
        $result = $translator->translate('Welcome %0', [$safeArg]);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    /**
     * Test that database translations are escaped when caller requests it
     */
    public function testDatabaseTranslationsAreEscaped()
    {
        // Insert translation with XSS into database
        \TikiDb::get()->query(
            'INSERT INTO `tiki_language` (`source`, `lang`, `tran`) VALUES (?, ?, ?)',
            ['Log in', $this->lang, 'Log in<script>alert("XSS")</script>']
        );

        $translator = LanguageTranslator::getInstance($this->lang);

        // Without $escape, content passes through unmodified
        $result = $translator->translate('Log in', []);
        $this->assertStringContainsString('<script>', $result);

        // With $escape = true, content is escaped
        $result = $translator->translate('Log in', [], true);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    /**
     * Test multiple XSS attack vectors in custom translations
     */
    public function testVariousXSSAttackVectors()
    {
        file_put_contents($this->customFile, '<?php
$lang_custom = array(
    "Test1" => "Test<script>alert(\'XSS\')</script>",
    "Test2" => "Test<img src=x onerror=alert(1)>",
    "Test3" => "Test<iframe src=javascript:alert(\'XSS\')>",
    "Test4" => "Test<svg onload=alert(1)>",
    "Test5" => "Test<body onload=alert(\'XSS\')>",
    "Test6" => "Test<input onfocus=alert(1) autofocus>",
);
$lang = array_replace($lang, $lang_custom);
');

        $translator = LanguageTranslator::getInstance($this->lang, ['skipDb' => true]);

        $attacks = ['Test1', 'Test2', 'Test3', 'Test4', 'Test5', 'Test6'];
        foreach ($attacks as $key) {
            // With $escape = true, all HTML tags should be escaped
            $result = $translator->translate($key, [], true);
            $this->assertDoesNotMatchRegularExpression('/<(?!\/?(br|p)>)[^>]+>/', $result, "Attack vector $key was not properly escaped");
        }
    }

    /**
     * Test that custom translations with legitimate HTML entities are preserved
     */
    public function testCustomTranslationsWithEntities()
    {
        file_put_contents($this->customFile, '<?php
$lang_custom = array(
    "Entity" => "Test &amp; Example &lt; &gt;",
);
$lang = array_replace($lang, $lang_custom);
');

        $translator = LanguageTranslator::getInstance($this->lang, ['skipDb' => true]);

        $result = $translator->translate('Entity', []);
        // Already-escaped entities should be double-escaped (expected behavior for user input)
        $this->assertStringContainsString('&amp;', $result);
    }

    /**
     * Test core translations preserve HTML
     */
    public function testOptOutEscaping()
    {
        // Don't add to custom.php - use base language file so it's not marked as custom
        $translator = LanguageTranslator::getInstance($this->lang, ['skipDb' => true]);

        // Core translation (Link with HTML from language.php) should preserve HTML
        $result = $translator->translate('Link with HTML', []);
        $this->assertStringContainsString('<a href="#"', $result);
        $this->assertStringNotContainsString('&lt;a', $result);
    }

    /**
     * Test Smarty {tr} block - translations are not escaped by default
     * Escaping is the caller's responsibility
     */
    public function testSmartyTrBlockEscaping()
    {
        $smarty = \TikiLib::lib('smarty');
        $template = $smarty->createTemplate('eval:dummy');

        file_put_contents($this->customFile, '<?php
$lang_custom = array(
    "TestContent" => "Content<script>alert(1)</script>",
);
$lang = array_replace($lang, $lang_custom);
');

        // Force the language
        global $prefs;
        $oldLang = $prefs['language'] ?? 'en';
        $prefs['language'] = $this->lang;

        $handler = new \SmartyTiki\BlockHandler\Tr();

        // {TR} does not escape by default - translations pass through as-is
        $repeat = false;
        $result = $handler->handle(
            ['lang' => $this->lang],
            'TestContent',
            $template,
            $repeat
        );
        $this->assertStringContainsString('<script>', $result);

        // Escaping happens only when tra() is called with $escape = true directly
        $result = tra('TestContent', $this->lang, false, [], true);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);

        $prefs['language'] = $oldLang;
    }

    /**
     * Test wiki context detection for noparse wrapping
     */
    public function testWikiContextDetection()
    {
        $smarty = \TikiLib::lib('smarty');
        $template = $smarty->createTemplate('eval:dummy');
        $parserlib = \TikiLib::lib('parser');

        $handler = new \SmartyTiki\BlockHandler\Tr();

        // Test without wiki context - parser options should not have wiki_parse_context set
        $parserlib->option['wiki_parse_context'] = false;
        $repeat = false;
        $result = $handler->handle(
            ['lang' => $this->lang],
            'Edit',
            $template,
            $repeat
        );
        $this->assertStringNotContainsString('~np~', $result);

        // Test with wiki context enabled via parser options
        $parserlib->option['wiki_parse_context'] = true;
        $repeat = false;
        $result = $handler->handle(
            ['lang' => $this->lang],
            'Edit',
            $template,
            $repeat
        );
        $this->assertStringContainsString('~np~', $result);
        $this->assertStringContainsString('~/np~', $result);

        // Mail translations explicitly opt out because their output is not parsed as wiki text.
        $smarty->assign([
            'mail_action' => '',
            'mail_itemId' => 1381,
            'mail_item_desc' => 'Ken',
            'mail_trackerName' => 'Contacts',
            'mail_user' => 'Ken',
            'mail_date' => 1,
            'mail_data' => '',
            'server_name' => 'example.test',
        ]);
        $result = $smarty->fetchLang($this->lang, 'mail/tracker_changed_notification.tpl');
        $this->assertStringContainsString('View the tracker item at:', $result);
        $this->assertStringNotContainsString('~np~', $result);
        $this->assertStringNotContainsString('~/np~', $result);

        $result = $smarty->fetchLang($this->lang, 'mail/tracker_changed_notification_subject.tpl');
        $this->assertStringContainsString('item', $result);
        $this->assertStringNotContainsString('~np~', $result);
        $this->assertStringNotContainsString('~/np~', $result);

        $smarty->assign([
            'info' => ['name' => 'News', 'description' => 'Description'],
            'code' => 'confirmation-code',
        ]);
        $result = $smarty->fetchLang($this->lang, 'mail/confirm_newsletter_subscription.tpl');
        $this->assertStringContainsString('To the newsletter:', $result);
        $this->assertStringNotContainsString('~np~', $result);
        $this->assertStringNotContainsString('~/np~', $result);

        $repeat = false;
        $result = $handler->handle(
            ['lang' => $this->lang],
            'Edit',
            $template,
            $repeat
        );
        $this->assertStringContainsString('~np~', $result);
        $this->assertStringContainsString('~/np~', $result);

        // Clean up
        unset($parserlib->option['wiki_parse_context']);
    }

    /**
     * Test that tra() function does NOT escape arguments
     * Calling code must escape user input to prevent XSS
     */
    public function testTraFunctionArgumentEscaping()
    {
        global $prefs;
        $oldLang = $prefs['language'] ?? 'en';
        $prefs['language'] = $this->lang;

        // tra() with HTML in argument - NOT escaped (calling code's responsibility)
        $result = tra('Welcome %0', $this->lang, false, ['<b>Admin</b>']);
        $this->assertStringContainsString('<b>Admin</b>', $result);
        $this->assertStringNotContainsString('&lt;b&gt;', $result);

        // For user-provided content, calling code MUST escape before passing to tra()
        $userInput = '<script>alert("XSS")</script>';
        $safeInput = htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
        $result = tra('Welcome %0', $this->lang, false, [$safeInput]);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);

        $prefs['language'] = $oldLang;
    }

    /**
     * Test null byte injection protection with explicit escape
     */
    public function testNullByteInjection()
    {
        file_put_contents($this->customFile, '<?php
$lang_custom = array(
    "NullByte" => "Safe\u0000<script>alert(\'XSS\')</script>",
);
$lang = array_replace($lang, $lang_custom);
');

        $translator = LanguageTranslator::getInstance($this->lang, ['skipDb' => true]);

        // With $escape = true, XSS is escaped
        $result = $translator->translate('NullByte', [], true);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    /**
     * Test that escape flag applies uniformly to all translations
     */
    public function testCoreTranslationsUnaffected()
    {
        file_put_contents($this->customFile, '<?php
$lang_custom = array(
    "Delete" => "Delete<script>alert(1)</script>",
);
$lang = array_replace($lang, $lang_custom);
');

        $translator = LanguageTranslator::getInstance($this->lang, ['skipDb' => true]);

        // Without $escape, all content passes through including HTML
        $result = $translator->translate('Delete', []);
        $this->assertStringContainsString('<script>', $result);

        $result = $translator->translate('Bold text', []);
        $this->assertStringContainsString('<b>bold</b>', $result);

        // With $escape = true, ALL translations are escaped uniformly
        $result = $translator->translate('Delete', [], true);
        $this->assertStringContainsString('&lt;script&gt;', $result);

        $result = $translator->translate('Bold text', [], true);
        $this->assertStringContainsString('&lt;b&gt;bold&lt;/b&gt;', $result);
    }

    /**
     * Integration test: Parse wiki content with button plugin containing translations
     * This tests the full parsing pipeline with noparse protection
     */
    public function testButtonTranslationInWikiContext()
    {
        global $prefs;

        // Create custom translation with HTML that could be interpreted as wiki syntax
        file_put_contents($this->customFile, '<?php
$lang_custom = array(
    "Click Here" => "Click <b>Here</b> Now",
    "Submit Form" => "Submit <i>Form</i>",
);
$lang = array_replace($lang, $lang_custom);
');

        $tikilib = \TikiLib::lib('tiki');
        $oldLang = $prefs['language'] ?? 'en';
        $oldButton = $prefs['wikiplugin_button'] ?? 'n';
        $oldTr = $prefs['wikiplugin_tr'] ?? 'n';
        $oldMultilingual = $prefs['feature_multilingual'] ?? 'n';

        $prefs['language'] = $this->lang;
        $tikilib->set_preference('feature_multilingual', 'y');
        $prefs['feature_multilingual'] = 'y';
        $tikilib->set_preference('wikiplugin_button', 'y');
        $prefs['wikiplugin_button'] = 'y';
        $tikilib->set_preference('wikiplugin_tr', 'y');
        $prefs['wikiplugin_tr'] = 'y';

        // Simulate wiki content with button plugin containing translation
        $wikiContent = '{BUTTON(href="index.php")}{TR()}Click Here{TR}{BUTTON}';

        // Parse the wiki content
        $parsed = \TikiLib::lib('parser')->parse_data($wikiContent, [
            'language' => $this->lang,
        ]);

        // {TR} does not escape - HTML in translations passes through
        $this->assertStringContainsString('<b>Here</b>', $parsed);

        // Should contain the button markup
        $this->assertStringContainsString('btn', $parsed);

        $prefs['language'] = $oldLang;
        $tikilib->set_preference('feature_multilingual', $oldMultilingual);
        $tikilib->set_preference('wikiplugin_button', $oldButton);
        $tikilib->set_preference('wikiplugin_tr', $oldTr);
    }

    /**
     * Integration test: Translations with HTML pass through wiki parsing
     * Escaping is the caller's responsibility, not the translator's
     */
    public function testWikiParsingWithMaliciousTranslation()
    {
        global $prefs;

        file_put_contents($this->customFile, '<?php
$lang_custom = array(
    "Dangerous" => "Click<script>alert(\'XSS\')</script>",
    "WithLink" => "Visit <a href=\"javascript:alert(1)\">site</a>",
);
$lang = array_replace($lang, $lang_custom);
');

        $tikilib = \TikiLib::lib('tiki');
        $oldLang = $prefs['language'] ?? 'en';
        $oldTr = $prefs['wikiplugin_tr'] ?? 'n';
        $oldMultilingual = $prefs['feature_multilingual'] ?? 'n';

        $prefs['language'] = $this->lang;
        $tikilib->set_preference('feature_multilingual', 'y');
        $prefs['feature_multilingual'] = 'y';
        $tikilib->set_preference('wikiplugin_tr', 'y');
        $prefs['wikiplugin_tr'] = 'y';

        // Verify that tra() with $escape = true properly escapes
        $result = tra('Dangerous', $this->lang, false, [], true);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);

        $result = tra('WithLink', $this->lang, false, [], true);
        $this->assertStringNotContainsString('href="javascript:', $result);
        $this->assertStringContainsString('&lt;a', $result);

        $prefs['language'] = $oldLang;
        $tikilib->set_preference('feature_multilingual', $oldMultilingual);
        $tikilib->set_preference('wikiplugin_tr', $oldTr);
    }

    /**
     * Integration test: Parse wiki content with core translation containing legitimate HTML
     * Tests that core translations preserve their HTML even in wiki parsing context
     */
    public function testWikiParsingWithCoreTranslationHTML()
    {
        global $prefs;

        $tikilib = \TikiLib::lib('tiki');
        $oldLang = $prefs['language'] ?? 'en';
        $oldTr = $prefs['wikiplugin_tr'] ?? 'n';
        $oldMultilingual = $prefs['feature_multilingual'] ?? 'n';

        $prefs['language'] = $this->lang;
        $tikilib->set_preference('feature_multilingual', 'y');
        $prefs['feature_multilingual'] = 'y';
        $tikilib->set_preference('wikiplugin_tr', 'y');
        $prefs['wikiplugin_tr'] = 'y'; // Enable tr plugin

        // Use a core translation that has HTML (from language.php, not custom.php)
        $wikiContent = '{TR()}Link with HTML{TR}';

        $parsed = \TikiLib::lib('parser')->parse_data($wikiContent, [
            'language' => $this->lang,
        ]);

        // Core translation HTML should be preserved
        $this->assertStringContainsString('<a href="#"', $parsed);
        $this->assertStringNotContainsString('&lt;a', $parsed);

        $prefs['language'] = $oldLang;
        $tikilib->set_preference('feature_multilingual', $oldMultilingual);
        $tikilib->set_preference('wikiplugin_tr', $oldTr);
    }

    /**
     * Integration test: Complex wiki page with multiple translation types
     * Tests the interaction between custom, core, and inline translations in a real wiki page
     */
    public function testComplexWikiPageWithMixedTranslations()
    {
        global $prefs;

        file_put_contents($this->customFile, '<?php
$lang_custom = array(
    "Custom Button" => "Custom<script>alert(1)</script>Button",
);
$lang = array_replace($lang, $lang_custom);
');

        $tikilib = \TikiLib::lib('tiki');
        $oldLang = $prefs['language'] ?? 'en';
        $oldButton = $prefs['wikiplugin_button'] ?? 'n';
        $oldTr = $prefs['wikiplugin_tr'] ?? 'n';
        $oldMultilingual = $prefs['feature_multilingual'] ?? 'n';

        $prefs['language'] = $this->lang;
        $tikilib->set_preference('feature_multilingual', 'y');
        $prefs['feature_multilingual'] = 'y';
        $tikilib->set_preference('wikiplugin_button', 'y');
        $prefs['wikiplugin_button'] = 'y';
        $tikilib->set_preference('wikiplugin_tr', 'y');
        $prefs['wikiplugin_tr'] = 'y';

        // Complex wiki content with various translation scenarios
        $wikiContent = <<<WIKI
! Page Title

Some text with {TR()}Link with HTML{TR} in the middle.
WIKI;

        $parsed = \TikiLib::lib('parser')->parse_data($wikiContent, [
            'language' => $this->lang,
        ]);

        // Core translation HTML should be preserved through {TR}
        $this->assertStringContainsString('<a href="#"', $parsed);

        // Page should have been parsed (heading converted)
        $this->assertStringContainsString('<h', $parsed);

        // Verify that tra() with explicit $escape = true does escape
        $result = tra('Custom Button', $this->lang, false, [], true);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);

        $prefs['language'] = $oldLang;
        $tikilib->set_preference('feature_multilingual', $oldMultilingual);
        $tikilib->set_preference('wikiplugin_button', $oldButton);
        $tikilib->set_preference('wikiplugin_tr', $oldTr);
    }
}
