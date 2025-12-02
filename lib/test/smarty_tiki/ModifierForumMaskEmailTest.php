<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\SmartyTiki;

use TikiTestCase;
use SmartyTiki\Modifier\ForumMaskEmail;

/**
 * Unit test for ForumMaskEmail Smarty modifier
 * Tests the email masking logic independently of forum/database operations
 */
class ModifierForumMaskEmailTest extends TikiTestCase
{
    private $originalPrefs;
    private $modifier;

    protected function setUp(): void
    {
        parent::setUp();

        // Backup original prefs if they exist
        $this->originalPrefs = $GLOBALS['prefs'] ?? null;

        // Initialize the modifier
        $this->modifier = new ForumMaskEmail();

        // Set up default prefs
        $GLOBALS['prefs'] = [
            'forum_mask_emails' => 'n',
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Restore original prefs or unset if there weren't any
        if ($this->originalPrefs !== null) {
            $GLOBALS['prefs'] = $this->originalPrefs;
        } else {
            unset($GLOBALS['prefs']);
        }
    }

    /**
     * Test that emails are not masked when the feature is disabled
     */
    public function testEmailsNotMaskedWhenFeatureDisabled(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'n';

        $text = 'Contact me at john.doe@example.com for more information.';
        $result = $this->modifier->handle($text);

        $this->assertEquals($text, $result, 'Text should remain unchanged when feature is disabled');
    }

    /**
     * Test that a single email is properly masked
     */
    public function testSingleEmailMasking(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $text = 'Contact me at john.doe@example.com';
        $result = $this->modifier->handle($text);

        $this->assertStringContainsString('jo...@ex...', $result, 'Email should be masked');
        $this->assertStringNotContainsString('john.doe@example.com', $result, 'Original email should not be present');
    }

    /**
     * Test that multiple emails are properly masked
     */
    public function testMultipleEmailsMasking(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $text = 'Contact john.doe@example.com or jane.smith@test.org for help';
        $result = $this->modifier->handle($text);

        $this->assertStringContainsString('jo...@ex...', $result, 'First email should be masked');
        $this->assertStringContainsString('ja...@te...', $result, 'Second email should be masked');
        $this->assertStringNotContainsString('john.doe@example.com', $result, 'First original email should not be present');
        $this->assertStringNotContainsString('jane.smith@test.org', $result, 'Second original email should not be present');
    }

    /**
     * Test masking with short email addresses
     */
    public function testShortEmailMasking(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $text = 'Email: ab@cd.com';
        $result = $this->modifier->handle($text);

        $this->assertStringContainsString('ab...@cd...', $result, 'Short email should be masked');
        $this->assertStringNotContainsString('ab@cd.com', $result, 'Original short email should not be present');
    }

    /**
     * Test masking with long email addresses
     */
    public function testLongEmailMasking(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $text = 'Contact: verylongemailaddress@verylongdomainname.co.uk';
        $result = $this->modifier->handle($text);

        $this->assertStringContainsString('ve...@ve...', $result, 'Long email should be masked to 2 chars');
        $this->assertStringNotContainsString('verylongemailaddress@verylongdomainname.co.uk', $result, 'Original long email should not be present');
    }

    /**
     * Test that text without emails remains unchanged
     */
    public function testTextWithoutEmails(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $text = 'This is some text without any email addresses.';
        $result = $this->modifier->handle($text);

        $this->assertEquals($text, $result, 'Text without emails should remain unchanged');
    }

    /**
     * Test that invalid email-like strings are not masked
     */
    public function testInvalidEmailsNotMasked(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $text = 'Invalid emails: @example.com, user@, user@@example.com';
        $result = $this->modifier->handle($text);

        $this->assertEquals($text, $result, 'Invalid email-like strings should not be masked');
    }

    /**
     * Test masking with special characters in email
     */
    public function testEmailWithSpecialCharacters(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $text = 'Email: user+tag@example.com';
        $result = $this->modifier->handle($text);

        // The regex keeps the + as part of the email, so it gets masked including the special char
        $this->assertStringContainsString('...@ex...', $result, 'Email with + should be masked');
        $this->assertStringNotContainsString('user+tag@example.com', $result, 'Original email with + should not be present');
        $this->assertStringNotContainsString('example.com', $result, 'Full domain should not be present');
    }

    /**
     * Test masking with numbers in email
     */
    public function testEmailWithNumbers(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $text = 'Email: user123@domain456.com';
        $result = $this->modifier->handle($text);

        $this->assertStringContainsString('us...@do...', $result, 'Email with numbers should be masked');
        $this->assertStringNotContainsString('user123@domain456.com', $result, 'Original email with numbers should not be present');
    }

    /**
     * Test masking with underscores and dots in email
     */
    public function testEmailWithUnderscoresAndDots(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $text = 'Email: first.last_name@my-domain.co.uk';
        $result = $this->modifier->handle($text);

        $this->assertStringContainsString('fi...@my...', $result, 'Email with underscores and dots should be masked');
        $this->assertStringNotContainsString('first.last_name@my-domain.co.uk', $result, 'Original email should not be present');
    }

    /**
     * Test masking emails in HTML context
     */
    public function testEmailMaskingInHtml(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $text = '<p>Contact us at <a href="mailto:info@example.com">info@example.com</a></p>';
        $result = $this->modifier->handle($text);

        $this->assertStringContainsString('in...@ex...', $result, 'Email in HTML should be masked');
        $this->assertStringNotContainsString('info@example.com', $result, 'Original email in HTML should not be present');
    }

    /**
     * Test masking multiple identical emails
     */
    public function testMultipleIdenticalEmails(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $text = 'Email: test@example.com and again test@example.com';
        $result = $this->modifier->handle($text);

        $this->assertStringNotContainsString('test@example.com', $result, 'Identical emails should all be masked');
        // Count occurrences of masked email
        $this->assertEquals(2, substr_count($result, 'te...@ex...'), 'Both identical emails should be masked');
    }

    /**
     * Test with empty string
     */
    public function testEmptyString(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $text = '';
        $result = $this->modifier->handle($text);

        $this->assertEquals('', $result, 'Empty string should remain empty');
    }

    /**
     * Test with null value
     */
    public function testNullValue(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $text = null;
        $result = $this->modifier->handle($text);

        $this->assertEquals(null, $result, 'Null should remain null');
    }

    /**
     * Test that the masking preserves surrounding text
     */
    public function testMaskingPreservesSurroundingText(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $text = 'Before text user@example.com after text';
        $result = $this->modifier->handle($text);

        $this->assertStringStartsWith('Before text', $result, 'Text before email should be preserved');
        $this->assertStringEndsWith('after text', $result, 'Text after email should be preserved');
        $this->assertStringContainsString('us...@ex...', $result, 'Email should be masked');
    }

    /**
     * Test email at the beginning of text
     */
    public function testEmailAtBeginning(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $text = 'user@example.com is my email';
        $result = $this->modifier->handle($text);

        $this->assertStringStartsWith('us...@ex...', $result, 'Email at beginning should be masked');
    }

    /**
     * Test email at the end of text
     */
    public function testEmailAtEnd(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $text = 'My email is user@example.com';
        $result = $this->modifier->handle($text);

        $this->assertStringEndsWith('us...@ex...', $result, 'Email at end should be masked');
    }

    /**
     * Test email with subdomain
     */
    public function testEmailWithSubdomain(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $text = 'Email: admin@mail.example.com';
        $result = $this->modifier->handle($text);

        $this->assertStringContainsString('ad...@ma...', $result, 'Email with subdomain should be masked');
        $this->assertStringNotContainsString('admin@mail.example.com', $result, 'Original email should not be present');
    }

    /**
     * Test that masking works with different TLDs
     */
    public function testEmailsWithDifferentTlds(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $emails = [
            'user@example.com' => 'us...@ex...',
            'user@example.org' => 'us...@ex...',
            'user@example.net' => 'us...@ex...',
            'user@example.co.uk' => 'us...@ex...',
            'user@example.io' => 'us...@ex...',
        ];

        foreach ($emails as $email => $expected) {
            $result = $this->modifier->handle("Email: $email");
            $this->assertStringContainsString($expected, $result, "Email with TLD should be masked: $email");
            $this->assertStringNotContainsString($email, $result, "Original email should not be present: $email");
        }
    }

    /**
     * Test performance with large text containing many emails
     */
    public function testPerformanceWithManyEmails(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $emails = [];
        for ($i = 0; $i < 50; $i++) {
            $emails[] = "user$i@example.com";
        }

        $text = 'Emails: ' . implode(', ', $emails);
        $startTime = microtime(true);
        $result = $this->modifier->handle($text);
        $endTime = microtime(true);

        $executionTime = $endTime - $startTime;

        // Should complete in reasonable time (less than 1 second)
        $this->assertLessThan(1.0, $executionTime, 'Masking many emails should complete in reasonable time');

        // Verify all emails are masked
        foreach ($emails as $email) {
            $this->assertStringNotContainsString($email, $result, "Email $email should be masked");
        }
    }

    /**
     * Test with line breaks and special formatting
     */
    public function testEmailsWithLineBreaks(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $text = "First email:\nuser1@example.com\n\nSecond email:\nuser2@example.org";
        $result = $this->modifier->handle($text);

        $this->assertStringContainsString('us...@ex...', $result, 'Emails with line breaks should be masked');
        $this->assertStringNotContainsString('user1@example.com', $result, 'First email should be masked');
        $this->assertStringNotContainsString('user2@example.org', $result, 'Second email should be masked');
    }

    /**
     * Test case sensitivity - emails should be case-insensitive
     */
    public function testCaseSensitivity(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $text = 'Contact: User@Example.COM or ADMIN@TEST.ORG';
        $result = $this->modifier->handle($text);

        $this->assertStringContainsString('Us...@Ex...', $result, 'Mixed case email should be masked');
        $this->assertStringContainsString('AD...@TE...', $result, 'Uppercase email should be masked');
    }

    /**
     * Test that masking doesn't break when preference is not set
     */
    public function testMissingPreference(): void
    {
        unset($GLOBALS['prefs']['forum_mask_emails']);

        $text = 'Contact me at user@example.com';
        $result = $this->modifier->handle($text);

        // Should not mask when preference is missing (defaults to disabled)
        $this->assertEquals($text, $result, 'Should not mask when preference is not set');
    }

    /**
     * Test XSS prevention - ensure HTML entities are not created during masking
     */
    public function testXssPrevention(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $text = '<script>alert("XSS")</script>test@example.com';
        $result = $this->modifier->handle($text);

        // The script tag should remain as-is (not the job of this modifier to escape)
        // But the email should be masked
        $this->assertStringContainsString('te...@ex...', $result, 'Email should be masked');
        $this->assertStringNotContainsString('test@example.com', $result, 'Original email should not be present');
    }
}
