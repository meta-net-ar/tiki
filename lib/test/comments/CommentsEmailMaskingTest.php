<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Comments;

use TikiLib;
use TikiTestCase;

/**
 * Integration test for forum email masking functionality
 * Tests the interaction between forum posts and email masking
 */
class CommentsEmailMaskingTest extends TikiTestCase
{
    private $commentsLib;
    private $commentsTable;
    private $originalPrefs;
    private $forumId;

    protected function setUp(): void
    {
        parent::setUp();

        // Backup original prefs if they exist
        $this->originalPrefs = $GLOBALS['prefs'] ?? null;

        $this->commentsLib = TikiLib::lib('comments');
        $this->commentsTable = $this->commentsLib->table('tiki_comments');

        $GLOBALS['user'] = 'test_user';
        $GLOBALS['prefs'] = [
            // Core email masking feature being tested
            'forum_mask_emails' => 'n',
            // Required by comment posting system
            'feature_forum_post_index' => 'n',
            'feature_forum_parse' => 'n',
            'section_comments_parse' => 'n',
            'feature_smileys' => 'n',
            'sender_email' => 'noreply@example.com',
            // Required by parsing/plugin system
            'wikiplugin_maximum_passes' => 1,
            'feature_wikiwords' => 'n',
            // Required by user/contribution tracking
            'login_is_email' => 'n',
            'feature_actionlog' => 'n',
            'feature_contribution' => 'n',
            // Required by search indexing
            'feature_search' => 'n',
            'unified_forum_deepindexing' => 'n',
        ];

        $this->forumId = $this->commentsLib->replace_forum([
            'forumId' => 1,
            'name' => "Test Forum for Email Masking",
            'description' => "Forum for testing email masking"
        ]);

        $_SERVER['SERVER_NAME'] = 'localhost';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->commentsLib->query("DELETE FROM tiki_comments");
        $this->commentsLib->query("ALTER TABLE tiki_comments AUTO_INCREMENT = 1");

        // Restore original prefs or unset if there weren't any
        if ($this->originalPrefs !== null) {
            $GLOBALS['prefs'] = $this->originalPrefs;
        } else {
            unset($GLOBALS['prefs']);
        }

        unset($GLOBALS['user']);
        unset($_SERVER['SERVER_NAME']);
    }

    /**
     * Test that email masking works when posting a new forum comment
     */
    public function testPostCommentWithEmailWhenMaskingEnabled(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $messageId = uniqid();
        $contentWithEmail = 'Please contact me at john.doe@example.com for more information.';

        $threadId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            0,
            $GLOBALS['user'],
            "Test Post with Email",
            $contentWithEmail,
            $messageId
        );

        $this->assertGreaterThan(0, $threadId, 'Comment should be created');

        // Get the comment - it should already be masked by get_comment()
        $comment = $this->commentsLib->get_comment($threadId);

        $this->assertNotNull($comment, 'Comment should exist');
        $this->assertStringContainsString('jo...@ex...', $comment['data'], 'Email should be masked when retrieved');
        $this->assertStringNotContainsString('john.doe@example.com', $comment['data'], 'Original email should not appear');

        // Verify raw database storage is unmasked (for search functionality)
        $rawComment = $this->commentsTable->fetchFullRow(['threadId' => $threadId]);
        $this->assertStringContainsString('john.doe@example.com', $rawComment['data'], 'Email should be stored unmasked in database');
    }

    /**
     * Test that emails are not masked when feature is disabled
     */
    public function testPostCommentWithEmailWhenMaskingDisabled(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'n';

        $messageId = uniqid();
        $contentWithEmail = 'Contact me at jane.smith@example.org';

        $threadId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            0,
            $GLOBALS['user'],
            "Test Post No Masking",
            $contentWithEmail,
            $messageId
        );

        $comment = $this->commentsLib->get_comment($threadId);

        // When feature is disabled, get_comment should return unmasked data
        $this->assertEquals($contentWithEmail, $comment['data'], 'Email should not be masked when feature is disabled');
        $this->assertStringContainsString('jane.smith@example.org', $comment['data'], 'Original email should appear');
    }

    /**
     * Test email masking in forum replies
     */
    public function testEmailMaskingInForumReplies(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        // Create parent post
        $parentMessageId = uniqid();
        $parentThreadId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            0,
            $GLOBALS['user'],
            "Parent Post",
            "Original post content",
            $parentMessageId
        );

        // Create reply with email
        $replyMessageId = uniqid();
        $replyContentWithEmail = 'Reply with email: support@company.com';

        $replyThreadId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            $parentThreadId,
            $GLOBALS['user'],
            "Reply with Email",
            $replyContentWithEmail,
            $replyMessageId
        );

        $this->assertGreaterThan(0, $replyThreadId, 'Reply should be created');

        $reply = $this->commentsLib->get_comment($replyThreadId);

        $this->assertEquals($parentThreadId, $reply['parentId'], 'Reply should reference parent');
        $this->assertStringContainsString('su...@co...', $reply['data'], 'Email in reply should be masked');
        $this->assertStringNotContainsString('support@company.com', $reply['data'], 'Original email should not appear');

        // Verify raw database storage is unmasked
        $rawReply = $this->commentsTable->fetchFullRow(['threadId' => $replyThreadId]);
        $this->assertStringContainsString('support@company.com', $rawReply['data'], 'Email should be stored unmasked in database');
    }

    /**
     * Test email masking with multiple emails in a single post
     */
    public function testMultipleEmailsInForumPost(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $messageId = uniqid();
        $contentWithMultipleEmails = 'Contact sales at sales@company.com or support at support@company.com';

        $threadId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            0,
            $GLOBALS['user'],
            "Multiple Emails",
            $contentWithMultipleEmails,
            $messageId
        );

        $comment = $this->commentsLib->get_comment($threadId);

        // get_comment already applies masking
        $this->assertStringContainsString('sa...@co...', $comment['data'], 'First email should be masked');
        $this->assertStringContainsString('su...@co...', $comment['data'], 'Second email should be masked');
        $this->assertStringNotContainsString('sales@company.com', $comment['data'], 'First original email should not appear');
        $this->assertStringNotContainsString('support@company.com', $comment['data'], 'Second original email should not appear');
    }

    /**
     * Test updating a forum post with email addresses
     */
    public function testUpdateForumPostWithEmail(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $messageId = uniqid();
        $originalContent = 'Original content without email';

        $threadId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            0,
            $GLOBALS['user'],
            "Original Title",
            $originalContent,
            $messageId
        );

        // Update with content containing email
        $updatedContent = 'Updated content with email: admin@example.com';
        $this->commentsLib->update_comment(
            $threadId,
            "Updated Title",
            0,
            $updatedContent,
            'n',
            "Updated summary",
            '',
            '',
            ''
        );

        $comment = $this->commentsLib->get_comment($threadId);

        // get_comment already applies masking
        $this->assertStringContainsString('ad...@ex...', $comment['data'], 'Email in updated post should be masked');
        $this->assertStringNotContainsString('admin@example.com', $comment['data'], 'Original email should not appear');

        // Verify raw database storage is unmasked
        $rawComment = $this->commentsTable->fetchFullRow(['threadId' => $threadId]);
        $this->assertStringContainsString('admin@example.com', $rawComment['data'], 'Email should be stored unmasked in database');
    }

    /**
     * Test email masking preserves forum post formatting
     */
    public function testEmailMaskingPreservesFormatting(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $messageId = uniqid();
        $contentWithFormatting = "Hello,\n\nPlease reach out to:\n- Primary: first@example.com\n- Secondary: second@example.org\n\nThanks!";

        $threadId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            0,
            $GLOBALS['user'],
            "Post with Formatting",
            $contentWithFormatting,
            $messageId
        );

        $comment = $this->commentsLib->get_comment($threadId);

        // get_comment already applies masking
        // Check that formatting is preserved
        $this->assertStringContainsString("Hello,", $comment['data'], 'Greeting should be preserved');
        $this->assertStringContainsString("Please reach out to:", $comment['data'], 'Text should be preserved');
        $this->assertStringContainsString("Thanks!", $comment['data'], 'Closing should be preserved');
        $this->assertStringContainsString("\n", $comment['data'], 'Line breaks should be preserved');

        // Check that emails are masked
        $this->assertStringContainsString('fi...@ex...', $comment['data'], 'First email should be masked');
        $this->assertStringContainsString('se...@ex...', $comment['data'], 'Second email should be masked');
    }

    /**
     * Test that forum posts without emails are not affected
     */
    public function testForumPostWithoutEmail(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $messageId = uniqid();
        $contentWithoutEmail = 'This is a regular forum post without any email addresses.';

        $threadId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            0,
            $GLOBALS['user'],
            "Regular Post",
            $contentWithoutEmail,
            $messageId
        );

        $comment = $this->commentsLib->get_comment($threadId);

        // get_comment already applies masking (though nothing to mask here)
        $this->assertEquals($contentWithoutEmail, $comment['data'], 'Content without email should remain unchanged');
    }

    /**
     * Test email masking with special characters in forum post
     */
    public function testForumPostWithSpecialEmailCharacters(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $messageId = uniqid();
        $contentWithSpecialEmail = 'Contact us at user+tag@example.com or user.name@sub.example.org';

        $threadId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            0,
            $GLOBALS['user'],
            "Post with Special Email",
            $contentWithSpecialEmail,
            $messageId
        );

        $comment = $this->commentsLib->get_comment($threadId);

        // get_comment already applies masking
        // The regex keeps special chars, so masking pattern may vary
        $this->assertStringContainsString('...@ex...', $comment['data'], 'Email with + should be masked');
        $this->assertStringContainsString('...@su...', $comment['data'], 'Email with subdomain should be masked');
        $this->assertStringNotContainsString('user+tag@example.com', $comment['data'], 'Original email with + should not appear');
        $this->assertStringNotContainsString('user.name@sub.example.org', $comment['data'], 'Original email with subdomain should not appear');
    }

    /**
     * Test email masking in forum thread with multiple posts
     */
    public function testEmailMaskingInThreadWithMultiplePosts(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        // Create parent post with email
        $parentMessageId = uniqid();
        $parentContent = 'Parent post with email: parent@example.com';

        $parentThreadId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            0,
            $GLOBALS['user'],
            "Parent with Email",
            $parentContent,
            $parentMessageId
        );

        // Create first reply with different email
        $reply1MessageId = uniqid();
        $reply1Content = 'First reply with email: reply1@example.org';

        $reply1ThreadId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            $parentThreadId,
            $GLOBALS['user'],
            "Reply 1",
            $reply1Content,
            $reply1MessageId
        );

        // Create second reply with another email
        $reply2MessageId = uniqid();
        $reply2Content = 'Second reply with email: reply2@example.net';

        $reply2ThreadId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            $parentThreadId,
            $GLOBALS['user'],
            "Reply 2",
            $reply2Content,
            $reply2MessageId
        );

        // Verify all posts are created
        $this->assertGreaterThan(0, $parentThreadId, 'Parent should be created');
        $this->assertGreaterThan(0, $reply1ThreadId, 'First reply should be created');
        $this->assertGreaterThan(0, $reply2ThreadId, 'Second reply should be created');

        // Get all comments - get_comment already applies masking
        $parent = $this->commentsLib->get_comment($parentThreadId);
        $this->assertStringContainsString('pa...@ex...', $parent['data'], 'Parent email should be masked');

        $reply1 = $this->commentsLib->get_comment($reply1ThreadId);
        $this->assertStringContainsString('re...@ex...', $reply1['data'], 'First reply email should be masked');

        $reply2 = $this->commentsLib->get_comment($reply2ThreadId);
        $this->assertStringContainsString('re...@ex...', $reply2['data'], 'Second reply email should be masked');
    }

    /**
     * Test that email masking doesn't interfere with forum search
     */
    public function testEmailStoredUnmaskedForSearch(): void
    {
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';

        $messageId = uniqid();
        $contentWithEmail = 'For inquiries contact info@company.com';

        $threadId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            0,
            $GLOBALS['user'],
            "Contact Post",
            $contentWithEmail,
            $messageId
        );

        // Verify email is stored unmasked in raw database (for search purposes)
        $rawComment = $this->commentsTable->fetchFullRow(['threadId' => $threadId]);
        $this->assertStringContainsString('info@company.com', $rawComment['data'], 'Email should be stored unmasked in database for search');

        // But when retrieved via get_comment, it should be masked
        $comment = $this->commentsLib->get_comment($threadId);
        $this->assertStringContainsString('in...@co...', $comment['data'], 'Email should be masked when retrieved');
        $this->assertStringNotContainsString('info@company.com', $comment['data'], 'Original email should not appear when retrieved');
    }

    /**
     * Test toggling the masking feature on and off
     */
    public function testTogglingMaskingFeature(): void
    {
        // First create post with masking disabled
        $GLOBALS['prefs']['forum_mask_emails'] = 'n';

        $messageId = uniqid();
        $contentWithEmail = 'Contact: toggle@example.com';

        $threadId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            0,
            $GLOBALS['user'],
            "Toggle Test",
            $contentWithEmail,
            $messageId
        );

        // Verify unmasked when disabled
        $commentUnmasked = $this->commentsLib->get_comment($threadId);
        $this->assertStringContainsString('toggle@example.com', $commentUnmasked['data'], 'Email should not be masked when disabled');

        // Enable masking and retrieve again
        $GLOBALS['prefs']['forum_mask_emails'] = 'y';
        $commentMasked = $this->commentsLib->get_comment($threadId);
        $this->assertStringContainsString('to...@ex...', $commentMasked['data'], 'Email should be masked when enabled');
        $this->assertStringNotContainsString('toggle@example.com', $commentMasked['data'], 'Original email should not appear when masked');
    }
}
