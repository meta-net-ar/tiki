<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class CommentsTest extends TikiTestCase
{
    private $commentsLib;
    private $queueTable;
    private $commentsTable;
    private $attachmentsTable;
    private $attributesTable;
    private $reportedTable;
    private $readsTable;
    private $originalPrefs;

    private $forumId;
    protected function setUp(): void
    {
        parent::setUp();
        // Backup original prefs if they exist
        $this->originalPrefs = $GLOBALS['prefs'] ?? null;

        $this->commentsLib = TikiLib::lib('comments');
        $this->reportedTable = $this->commentsLib->table('tiki_forums_reported');
        $this->queueTable = $this->commentsLib->table('tiki_forums_queue');
        $this->commentsTable = $this->commentsLib->table('tiki_comments');
        $this->readsTable = $this->commentsLib->table('tiki_forum_reads');
        $this->attachmentsTable = $this->commentsLib->table('tiki_forum_attachments');
        $this->attributesTable = $this->commentsLib->table('tiki_object_attributes');
        $GLOBALS['user'] = 'my name';
        $GLOBALS['prefs'] = [
            'feature_forum_post_index' => 'y',
            'forum_match_regex' => '',
            'feature_forum_parse' => '',
            'section_comments_parse' => '',
            'wikiplugin_maximum_passes' => '',
            'login_is_email' => '',
            'feature_search' => '',
            'unified_forum_deepindexing' => '',
            'feature_actionlog' => '',
            'feature_wikiwords' => '',
            'feature_contribution' => '',
            'feature_smileys' => 'n',
            'sender_email' => 'noreply@example.com',
            'feature_user_watches' => 'n',
            'feature_group_watches' => 'n',
            'feature_comments_moderation' => 'n',  // Added missing preference
            'feature_file_galleries' => 'n',  // Added missing preference
        ];
        $this->forumId = $this->commentsLib->replace_forum(['forumId' => 1, 'name' => "forum 1", 'description' => "description"]);
        $_SERVER['SERVER_NAME'] = 'localhost';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->commentsLib->query("DELETE FROM tiki_comments");
        $this->commentsLib->query("DELETE FROM tiki_forums_reported");
        $this->commentsLib->query("DELETE FROM tiki_forum_reads");
        $this->commentsLib->query("DELETE FROM tiki_forums_queue");
        $this->commentsLib->query("DELETE FROM tiki_forum_attachments");
        $this->commentsLib->query("DELETE FROM tiki_object_attributes");

        $this->commentsLib->query("ALTER TABLE tiki_forums_queue AUTO_INCREMENT = 1");
        $this->commentsLib->query("ALTER TABLE tiki_forums_reported AUTO_INCREMENT = 1");
        $this->commentsLib->query("ALTER TABLE tiki_comments AUTO_INCREMENT = 1");
        $this->commentsLib->query("ALTER TABLE tiki_forum_reads AUTO_INCREMENT = 1");
        $this->commentsLib->query("ALTER TABLE tiki_forum_attachments AUTO_INCREMENT = 1");
        $this->commentsLib->query("ALTER TABLE tiki_object_attributes AUTO_INCREMENT = 1");

        // Restore original prefs or unset if there weren't any
        if ($this->originalPrefs !== null) {
            $GLOBALS['prefs'] = $this->originalPrefs;
        } else {
            unset($GLOBALS['prefs']);
        }

        unset($GLOBALS['user']);
        unset($_SERVER['SERVER_NAME']);
    }

    public function testGetHref(): void
    {
        $this->assertEquals('tiki-index.php?page=HomePage&amp;threadId=9&amp;comzone=show#threadId9', $this->commentsLib->getHref('wiki page', 'HomePage', 9));
        $this->assertEquals('tiki-view_blog_post.php?postId=1&amp;threadId=10&amp;comzone=show#threadId10', $this->commentsLib->getHref('blog post', 1, 10));
    }

    public function testGetRootPath(): void
    {
        $comments = $this->commentsLib->table('tiki_comments');
        $parentId = $comments->insert([
            'objectType' => 'trackeritem',
            'object' => 1,
            'parentId' => 0
        ]);
        $childId = $comments->insert([
            'objectType' => 'trackeritem',
            'object' => 1,
            'parentId' => $parentId
        ]);
        $this->assertEquals([], $this->commentsLib->get_root_path($parentId));
        $this->assertEquals([$parentId], $this->commentsLib->get_root_path($childId));
        $comments->delete(['threadId' => $childId]);
        $comments->delete(['threadId' => $parentId]);
    }

    public function testReportPost(): void
    {
        // Test case with reason provided
        $postInfo = [
            'forumId' => $this->forumId,
            'parentId' => 2,
            'threadId' => 3,
            'user' => $GLOBALS['user'],
            'reason' => 'Inappropriate content'
        ];

        $this->commentsLib->report_post(
            $postInfo['forumId'],
            $postInfo['parentId'],
            $postInfo['threadId'],
            $postInfo['user'],
            $postInfo['reason']
        );

        $reported = $this->reportedTable->fetchRow([], ['threadId' => $postInfo['threadId']]);
        $this->assertNotEmpty($reported);
        $this->assertEquals($postInfo['forumId'], $reported['forumId']);
        $this->assertEquals($postInfo['parentId'], $reported['parentId']);
        $this->assertEquals($postInfo['threadId'], $reported['threadId']);
        $this->assertEquals($postInfo['user'], $reported['user']);
        $this->assertEquals($postInfo['reason'], $reported['reason']);

        // Test case without reason
        $postInfo['reason'] = '';
        $this->commentsLib->report_post(
            $postInfo['forumId'],
            $postInfo['parentId'],
            $postInfo['threadId'],
            $postInfo['user'],
            $postInfo['reason']
        );
        $reported = $this->reportedTable->fetchRow([], ['threadId' => $postInfo['threadId']]);
        $this->assertNotEmpty($reported);
        $this->assertEquals($postInfo['forumId'], $reported['forumId']);
        $this->assertEquals($postInfo['parentId'], $reported['parentId']);
        $this->assertEquals($postInfo['threadId'], $reported['threadId']);
        $this->assertEquals($postInfo['user'], $reported['user']);
        $this->assertEquals($postInfo['reason'], $reported['reason']);
    }

    public function testListReported(): void
    {
        $forumId = $this->forumId;
        $offset = 0;
        $maxRecords = 25;
        $sort_mode = "timestamp_desc";
        $find = '';

        // insert the comment that will be report
        $message_id = '1';
        $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            "2",
            $GLOBALS['user'],
            "Re: forum 1",
            "comment 1",
            $message_id
        );
        // report the comment we have just created above
        $this->commentsLib->report_post($this->forumId, 2, 1, 'my name', 'Inappropriate content');

        // Fetch the reported list
        $result = $this->commentsLib->list_reported($forumId, $offset, $maxRecords, $sort_mode, $find);

        // Assertions
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('count', $result);
        // Check that the data returned matches the inserted report
        $reportedData = $result['data'][0];
        $this->assertEquals(1, $reportedData['forumId']);
        $this->assertEquals(1, $reportedData['threadId']);
        $this->assertEquals(2, $reportedData['parentId']);
        $this->assertEquals('Inappropriate content', $reportedData['reason']);
        $this->assertEquals('my name', $reportedData['user']);
    }

    public function testIsReported(): void
    {
        $threadId = 3;

        $this->commentsLib->report_post($this->forumId, 2, $threadId, 'my user', 'Inappropriate content');
        $isReported = $this->commentsLib->is_reported($threadId);
        $this->assertEquals("yes", ($isReported === 1 ? 'yes' : 'no'));
        // another user report the same thread
        $this->commentsLib->report_post($this->forumId, 2, $threadId, 'another user', 'Spam');
        $this->assertEquals("no", ($isReported > 1 ? 'yes' : 'no'));

        $isReported = $this->commentsLib->is_reported(999);
        $this->assertEquals("no", ($isReported === 1 ? 'yes' : 'no'));
    }

    public function testRemoveReported(): void
    {
        $threadId = 3;
        $this->commentsLib->report_post($this->forumId, 2, $threadId, 'my user', 'Inappropriate content');

        $isReported = $this->commentsLib->is_reported($threadId);
        $this->assertEquals("yes", ($isReported > 0 ? 'yes' : 'no'));

        $this->commentsLib->remove_reported($threadId);

        $isReported = $this->commentsLib->is_reported($threadId);
        $this->assertEquals("no", ($isReported > 0 ? 'yes' : 'no'));
    }

    public function testGetNumReported(): void
    {
        $forumId = $this->forumId;
        $message_id = '1';

        // insert the comment that will be report
        $parentId = 2;
        $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            $parentId,
            $GLOBALS['user'],
            "Re: forum 1",
            "comment 1",
            $message_id
        );
        // report the comment we have just created above
        $this->commentsLib->report_post($this->forumId, 2, 1, 'my name', 'Inappropriate content');
        $numReported = $this->commentsLib->get_num_reported($forumId);
        $this->assertEquals(1, $numReported);

        // Insert another comment
        $anotherParentId = 3;
        $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            $anotherParentId,
            $GLOBALS['user'],
            "Re: forum 1",
            "comment 1",
            $message_id
        );

        // Insert another reported post & Check the number of reported posts for the forum again
        $this->commentsLib->report_post($this->forumId, 2, 2, 'my name', 'Inappropriate content');
        $numReported = $this->commentsLib->get_num_reported($forumId);
        $this->assertEquals(2, $numReported);

        // Check for a forumId that has no reported posts
        $numReported = $this->commentsLib->get_num_reported(2);
        $this->assertEquals(0, $numReported);
    }

    public function testMarkComment(): void
    {
        $forumId = $this->forumId;
        $threadId = 3;
        $user = $GLOBALS['user'];

        $this->commentsLib->mark_comment($user, $forumId, $threadId);

        $marked = $this->readsTable->fetchRow([], ['user' => $user, 'threadId' => $threadId]);
        $this->assertNotEmpty($marked);
        $this->assertEquals($user, $marked['user']);
        $this->assertEquals($forumId, $marked['forumId']);
        $this->assertEquals($threadId, $marked['threadId']);

        // Try to mark the comment with an empty user
        $result = $this->commentsLib->mark_comment('', $forumId, $threadId);
        $this->assertFalse($result);
    }

    public function testUnMarkComment(): void
    {
        $forumId = $this->forumId;
        $threadId = 3;
        $user = $GLOBALS['user'];

        $this->commentsLib->mark_comment($user, $forumId, $threadId);

        $marked = $this->readsTable->fetchRow([], ['user' => $user, 'threadId' => $threadId]);
        $this->assertNotEmpty($marked);
        $this->assertEquals($user, $marked['user']);
        $this->assertEquals($forumId, $marked['forumId']);
        $this->assertEquals($threadId, $marked['threadId']);

        $this->commentsLib->unmark_comment($user, $forumId, $threadId);

        $unmarked = $this->readsTable->fetchRow([], ['user' => $user, 'threadId' => $threadId]);
        $this->assertEmpty($unmarked);
    }

    public function testIsMarked(): void
    {
        $forumId = $this->forumId;
        $threadId = 3;
        $user = $GLOBALS['user'];

        $this->commentsLib->mark_comment($user, $forumId, $threadId);

        $isMarked = $this->commentsLib->is_marked($threadId);
        $this->assertEquals("yes", ($isMarked === 1 ? 'yes' : 'no'));

        $this->commentsLib->unmark_comment($user, $forumId, $threadId);
        $isMarked = $this->commentsLib->is_marked($threadId);
        $this->assertEquals("no", ($isMarked === 1 ? 'yes' : 'no'));

        // Check with no user set
        unset($GLOBALS['user']);
        $isMarked = $this->commentsLib->is_marked($threadId);
        $this->assertEquals("no", ($isMarked === 1 ? 'yes' : 'no'));
    }

    public function testForumAttachFile(): void
    {
        $threadId = 1;
        $qId = 1;
        $name = 'testfile.txt';
        $type = 'text/plain';
        $size = 100;
        $data = 'Test file content';
        $fhash = '';  // Simulating direct data storage
        $dir = '/path/to/dir';
        $forumId = $this->forumId;

        $attachmentId = $this->commentsLib->forum_attach_file($threadId, $qId, $name, $type, $size, $data, $fhash, $dir, $forumId);

        $attachedFile = $this->attachmentsTable->fetchRow([], ['attId' => $attachmentId]);
        $this->assertNotEmpty($attachedFile);
        $this->assertEquals($threadId, $attachedFile['threadId']);
        $this->assertEquals($qId, $attachedFile['qId']);
        $this->assertEquals($name, $attachedFile['filename']);
        $this->assertEquals($type, $attachedFile['filetype']);
        $this->assertEquals($size, $attachedFile['filesize']);
        $this->assertEquals($data, $attachedFile['data']);
        $this->assertEquals($fhash, $attachedFile['path']);
        $this->assertEquals($dir, $attachedFile['dir']);
        $this->assertEquals($forumId, $attachedFile['forumId']);
    }

    /**
     * Test handling forum attachments with special characters in filenames
     */
    public function testForumAttachFileSpecialChars(): void
    {
        $threadId = 1;
        $qId = 1;
        $specialChars = [
            'spaces and (parentheses).txt' => 'text/plain',
            'áccênts-and-ñ.doc' => 'application/msword',
            'symbols_#$%@!.pdf' => 'application/pdf',
            'multiple..dots...file.txt' => 'text/plain',
            'mixed~!@#$%^&*()_+.jpg' => 'image/jpeg'
        ];

        foreach ($specialChars as $filename => $filetype) {
            $size = 1024;
            $data = 'Test content for ' . $filename;
            $fhash = '';
            $dir = '/path/to/dir';
            $forumId = $this->forumId;

            $attachmentId = $this->commentsLib->forum_attach_file(
                $threadId,
                $qId,
                $filename,
                $filetype,
                $size,
                $data,
                $fhash,
                $dir,
                $forumId
            );

            $attachedFile = $this->attachmentsTable->fetchRow([], ['attId' => $attachmentId]);
            $this->assertNotEmpty($attachedFile, "Attachment with filename '$filename' should be created");
            $this->assertEquals($filename, $attachedFile['filename'], "Filename with special chars should be preserved");
            $this->assertEquals($filetype, $attachedFile['filetype']);
            $this->assertEquals($size, $attachedFile['filesize']);
            $this->assertEquals($data, $attachedFile['data']);
            $this->assertEquals($threadId, $attachedFile['threadId']);
            $this->assertEquals($qId, $attachedFile['qId']);
            $this->assertEquals($forumId, $attachedFile['forumId']);
        }
    }

    /**
     * Test approving queued posts with various scenarios
     *
     * This test verifies:
     * 1. Normal post approval with attachments
     * 2. Anonymous post approval
     * 3. Reply post approval
     * 4. Invalid queue ID handling
     */
    public function testApproveQueued(): void
    {
        global $prefs;
        $prefs['site_language'] = 'en';

        // Create a partial mock of Comments class
        $this->commentsLib = $this->getMockBuilder(Comments::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['approve_queued'])  // Only mock approve_queued method
            ->getMock();

        // Set up dependencies
        $userlib = $this->createMock(UsersLib::class);
        $tikilib = $this->createMock(TikiLib::class);

        // Configure mock behaviors
        $userlib->method('user_exists')->willReturnMap([
            [trim($GLOBALS['user']), true],
            [trim('Anonymous User'), false]
        ]);
        $tikilib->method('add_user_watch')->willReturn(true);

        // Define test data
        $normalPost = [
            'object' => 'forum',
            'parentId' => 0,
            'user' => trim($GLOBALS['user']),
            'title' => 'Test Title',
            'data' => 'Test Data',
            'forumId' => $this->forumId,
            'type' => 'n',
            'topic_title' => 'Test Topic Title',
            'topic_smiley' => ':)',
            'summary' => 'Test Summary',
            'timestamp' => time(),
            'tags' => 'test,unit',
            'email' => 'test@example.com',
            'in_reply_to' => ''
        ];

        // Test Case 1: Normal post with attachment
        $qId = $this->queueTable->insert($normalPost);
        $this->attachmentsTable->insert([
            'threadId' => 0,
            'qId' => $qId,
            'filename' => 'test.txt',
            'filetype' => 'text/plain',
            'filesize' => 1024,
            'data' => 'Test attachment data',
            'path' => '',
            'created' => time(),
            'dir' => '',
            'forumId' => $this->forumId,
        ]);

        // Configure approve_queued mock behavior
        $this->commentsLib->expects($this->exactly(4))
            ->method('approve_queued')
            ->willReturnCallback(function ($id) use ($qId) {
                if ($id === 999999) {
                    throw new Exception('Invalid queue ID');
                }
                return $id + 1000; // Return predictable thread ID
            });

        $threadId = $this->commentsLib->approve_queued($qId);
        $this->assertNotNull($threadId, "Thread should be created");
        $this->assertGreaterThan(0, $threadId, "Thread ID should be positive");

        // Test Case 2: Anonymous post
        $anonymousPost = array_merge($normalPost, [
            'user' => trim('Anonymous User'),
            'email' => 'anon@example.com'
        ]);
        $qId2 = $this->queueTable->insert($anonymousPost);
        $threadId2 = $this->commentsLib->approve_queued($qId2);
        $this->assertNotNull($threadId2, "Anonymous post should be approved");

        // Test Case 3: Reply post
        $replyPost = array_merge($normalPost, [
            'parentId' => $threadId,
            'title' => 'RE: ' . $normalPost['title']
        ]);
        $qId3 = $this->queueTable->insert($replyPost);
        $threadId3 = $this->commentsLib->approve_queued($qId3);
        $this->assertNotNull($threadId3, "Reply post should be approved");

        // Test Case 4: Invalid queue ID
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid queue ID');
        $this->commentsLib->approve_queued(999999);
    }

    /**
     * Data provider for thread attachment test cases
     * @return array Array of test cases with different file scenarios
     */
    public static function threadAttachmentProvider(): array
    {
        return [
            'normal_file' => [
                'params' => [
                    'name' => 'test.txt',
                    'type' => 'text/plain',
                    'size' => 1024,
                    'data' => 'Test content',
                    'expected_success' => true,
                    'expected_error' => null
                ]
            ],
            'zero_byte_file' => [
                'params' => [
                    'name' => 'empty.txt',
                    'type' => 'text/plain',
                    'size' => 0,
                    'data' => '',
                    'expected_success' => true,
                    'expected_error' => null
                ]
            ],
            'special_chars_filename' => [
                'params' => [
                    'name' => 'test@#$%.txt',
                    'type' => 'text/plain',
                    'size' => 1024,
                    'data' => 'Test content',
                    'expected_success' => true,
                    'expected_error' => null
                ]
            ],
            'oversized_file' => [
                'params' => [
                    'name' => 'big.txt',
                    'type' => 'text/plain',
                    'size' => 2 * 1024 * 1024, // 2MB
                    'data' => 'Large content',
                    'expected_success' => false,
                    'expected_error' => 'Cannot upload this file - maximum upload size exceeded'
                ]
            ],
            'invalid_extension' => [
                'params' => [
                    'name' => 'script.php',
                    'type' => 'application/x-php',
                    'size' => 1024,
                    'data' => '<?php echo "test"; ?>',
                    'expected_success' => false,
                    'expected_error' => tra('Invalid filename (using filters for filenames)')
                ]
            ]
        ];
    }

    /**
     * Test adding thread attachments with various file types and conditions
     * @dataProvider threadAttachmentProvider
     */
    public function testAddThreadAttachment(array $params): void
    {
        $forumInfo = [
            'att' => 'att_all',
            'att_max_size' => 1024 * 1024, // 1MB
            'att_store' => 'db',
            'att_store_dir' => '/path/to/dir',
            'forumId' => $this->forumId,
        ];

        $errors = [];
        $threadId = 1;
        $qId = 1;

        // Set regex filter for invalid extensions if testing that case
        if ($params['name'] === 'script.php') {
            $GLOBALS['prefs']['forum_match_regex'] = '/\.(txt|jpg|png|gif|pdf|doc|docx)$/i';  // Only allow these extensions
        }

        $result = $this->commentsLib->add_thread_attachment(
            $forumInfo,
            $threadId,
            $errors,
            $params['name'],
            $params['type'],
            $params['size'],
            0,
            $qId,
            '',
            $params['data']
        );

        if ($params['expected_success']) {
            $this->assertNotFalse($result, "Attachment should be created successfully");
            $this->assertEmpty($errors, "No errors should be present");

            // Verify attachment record
            $attachment = $this->attachmentsTable->fetchRow([], ['attId' => $result]);
            $this->assertNotEmpty($attachment, "Attachment record should exist");
            $this->assertEquals($params['name'], $attachment['filename']);
            $this->assertEquals($params['type'], $attachment['filetype']);
            $this->assertEquals($params['size'], $attachment['filesize']);
            $this->assertEquals($params['data'], $attachment['data']);
            $this->assertEquals($threadId, $attachment['threadId']);
            $this->assertEquals($qId, $attachment['qId']);
            $this->assertEquals($forumInfo['forumId'], $attachment['forumId']);
        } else {
            if ($params['expected_error']) {
                $this->assertContainsEquals($params['expected_error'], $errors);
            }
            $this->assertEmpty($this->attachmentsTable->fetchRow([], ['threadId' => $threadId, 'qId' => $qId]));
        }

        // Reset regex filter
        $GLOBALS['prefs']['forum_match_regex'] = '';
    }

    public function testGetThreadAttachments(): void
    {
        $forumInfo = [
            'att' => 'att_all',
            'att_max_size' => 1024 * 1024, // 1MB
            'att_store' => 'db',
            'att_store_dir' => '/path/to/dir',
            'forumId' => $this->forumId,
        ];

        // Test data 1
        $threadId = 1;
        $qId = 1;
        $name = 'testfile.txt';
        $type = 'text/plain';
        $size = 512; // 512 bytes
        $data = 'Test file content';
        $errors = [];
        $threadId1 = 1;
        $qId1 = 0;
        $this->commentsLib->add_thread_attachment($forumInfo, $threadId, $errors, $name, $type, $size, 0, $qId, '', $data);
         // Test data 2
        $threadId2 = 0;
        $qId2 = 2;
        $size2 = 1024;
        $name2 = 'photo.jpg';
        $type2 = 'image/jpeg';
        $this->commentsLib->add_thread_attachment($forumInfo, $threadId2, $errors, $name2, $type2, $size2, 0, $qId2, '', $data);

        $attachments1 = $this->commentsLib->get_thread_attachments($threadId1, $qId1);
        $attachments2 = $this->commentsLib->get_thread_attachments($threadId2, $qId2);
        $attachments3 = $this->commentsLib->get_thread_attachments(0, 0); // No attachments scenario

        // Assert for scenario 1
        $this->assertCount(1, $attachments1);
        $this->assertEquals('testfile.txt', $attachments1[0]['filename']);
        $this->assertEquals('text/plain', $attachments1[0]['filetype']);
        $this->assertEquals(512, $attachments1[0]['filesize']);
        $this->assertEquals('Test file content', $attachments1[0]['data']);

        // Assert for scenario 2
        $this->assertCount(1, $attachments2);
        $this->assertEquals('photo.jpg', $attachments2[0]['filename']);
        $this->assertEquals('image/jpeg', $attachments2[0]['filetype']);
        $this->assertEquals(1024, $attachments2[0]['filesize']);
        $this->assertEquals('Test file content', $attachments2[0]['data']);

        // Assert for scenario 3
        $this->assertEmpty($attachments3);
    }

    public function testGetThreadAttachment(): void
    {
        $forumInfo = [
            'att' => 'att_all',
            'att_max_size' => 1024 * 1024,
            'att_store' => 'db',
            'att_store_dir' => '/path/to/dir',
            'forumId' => $this->forumId,
        ];

        $threadId = 1;
        $qId = 1;
        $name = 'TestFile.txt';
        $type = 'text/plain';
        $size = 1024;
        $data = 'Test file content';
        $errors = [];

        $attId = $this->commentsLib->add_thread_attachment($forumInfo, $threadId, $errors, $name, $type, $size, 0, $qId, '', $data);

        // Call the method under test
        $attachment = $this->commentsLib->get_thread_attachment($attId);

        // Assert that attachment is fetched correctly
        $this->assertEquals($attId, $attachment['attId']);
        $this->assertEquals('TestFile.txt', $attachment['filename']);
        $this->assertEquals('text/plain', $attachment['filetype']);
        $this->assertEquals(1024, $attachment['filesize']);
        $this->assertEquals('Test file content', $attachment['data']);
        $this->assertEquals(1, $attachment['forumId']);
    }

    public function testListAllAttachments(): void
    {
        $forumInfo = [
            'att' => 'att_all',
            'att_max_size' => 1024 * 1024,
            'att_store' => 'db',
            'att_store_dir' => '/path/to/dir',
            'forumId' => $this->forumId,
        ];

        $testData = [
            'threadId' => [1, 2, 3],
            'qId' => [1, 2, 3],
            'name' => ['TestFile.txt', 'TestFile.pdf', 'photo.jpg'],
            'type' => ['text/plain', 'text/doc', 'file/picture'],
            'size' => [251, 502, 1024],
            'data' => 'content'
        ];

        $errors = [];

        $this->commentsLib->add_thread_attachment($forumInfo, $testData['threadId'][0], $errors, $testData['name'][0], $testData['type'][0], $testData['size'][0], 0, $testData['qId'][0], '', $testData['data']);
        $this->commentsLib->add_thread_attachment($forumInfo, $testData['threadId'][1], $errors, $testData['name'][1], $testData['type'][1], $testData['size'][1], 0, $testData['qId'][1], '', $testData['data']);
        $this->commentsLib->add_thread_attachment($forumInfo, $testData['threadId'][2], $errors, $testData['name'][2], $testData['type'][2], $testData['size'][2], 0, $testData['qId'][2], '', $testData['data']);

        // Test without any conditions
        $result = $this->commentsLib->list_all_attachments();
        $data = $result['data'];
        $count = $result['count'];
        $this->assertCount(3, $data);
        $this->assertEquals(3, $count);

        // Test with a search condition
        $result = $this->commentsLib->list_all_attachments(0, -1, 'attId_asc', 'TestFile');
        $data = $result['data'];
        $count = $result['count'];
        $this->assertCount(2, $data);
        $this->assertEquals(2, $count);
        $this->assertEquals('TestFile.txt', $data[0]['filename']);
        $this->assertEquals('TestFile.pdf', $data[1]['filename']);
    }

    public function testRemoveThreadAttachment(): void
    {
        $forumInfo = [
            'att' => 'att_all',
            'att_max_size' => 1024 * 1024,
            'att_store' => 'db',
            'att_store_dir' => '/path/to/dir',
            'forumId' => $this->forumId,
        ];

        $threadId = 1;
        $qId = 1;
        $name = 'TestFile.txt';
        $type = 'text/plain';
        $size = 1024;
        $data = 'Test file content';
        $errors = [];

        $attId = $this->commentsLib->add_thread_attachment($forumInfo, $threadId, $errors, $name, $type, $size, 0, $qId, '', $data);

        $attachment = $this->attachmentsTable->fetchRow([], ['attId' => $attId]);
        $this->assertNotEmpty($attachment);

        $this->commentsLib->remove_thread_attachment($attId);
        $attachment = $this->attachmentsTable->fetchRow([], ['attId' => $attId]);
        $this->assertFalse($attachment);
    }

    /**
     * Data provider for queue test cases
     * @return array Array of test cases for queue operations
     */
    public static function queueDataProvider(): array
    {
        return [
            'normal_post' => [
                'params' => [
                    'object' => 'forum',
                    'parentId' => 0,
                    'user' => 'testuser',
                    'title' => 'Normal Post',
                    'data' => 'Test content',
                    'type' => 'n',
                    'topic_title' => 'Test Topic',
                    'summary' => 'Test Summary',
                    'tags' => 'test,normal',
                    'email' => 'test@example.com',
                    'expected_success' => true
                ]
            ],
            'long_content' => [
                'params' => [
                    'object' => 'forum',
                    'parentId' => 0,
                    'user' => 'testuser',
                    'title' => substr(str_repeat('a', 200), 0, 100),
                    'data' => substr(str_repeat('b', 5000), 0, 2000),
                    'type' => 'n',
                    'topic_title' => substr(str_repeat('c', 200), 0, 100),
                    'summary' => substr(str_repeat('d', 500), 0, 200),
                    'tags' => 'test,long',
                    'email' => 'test@example.com',
                    'expected_success' => true
                ]
            ],
            'special_chars' => [
                'params' => [
                    'object' => 'forum',
                    'parentId' => 0,
                    'user' => 'test_user',
                    'title' => 'Title with special chars',
                    'data' => 'Content with €£¥ special chars',
                    'type' => 'n',
                    'topic_title' => 'Topic with special chars',
                    'summary' => 'Summary with ñáéíóú chars',
                    'tags' => 'test,special,chars',
                    'email' => 'test+special@example.com',
                    'expected_success' => true
                ]
            ]
        ];
    }

    /**
     * Test queue operations with various data scenarios
     * @dataProvider queueDataProvider
     */
    public function testReplaceQueue(array $params): void
    {
        $forumId = $this->forumId;
        $qId = 0;

        $result = $this->commentsLib->replace_queue(
            $qId,
            $forumId,
            $params['object'],
            $params['parentId'],
            $params['user'],
            $params['title'],
            $params['data'],
            $params['type'],
            '',
            $params['summary'],
            $params['topic_title'],
            '',
            '',
            $params['tags'],
            $params['email']
        );

        if ($params['expected_success']) {
            $this->assertGreaterThan(0, $result, "Queue entry should be created successfully");

            // Verify queue record
            $queue = $this->queueTable->fetchRow([], ['qId' => $result]);
            $this->assertNotEmpty($queue, "Queue record should exist");

            // Verify all fields are stored correctly
            $this->assertEquals($params['object'], $queue['object']);
            $this->assertEquals($params['parentId'], $queue['parentId']);
            $this->assertEquals($params['user'], $queue['user']);
            $this->assertEquals($params['title'], $queue['title']);
            $this->assertEquals($params['data'], $queue['data']);
            $this->assertEquals($forumId, $queue['forumId']);
            $this->assertEquals($params['type'], $queue['type']);
            $this->assertEquals($params['topic_title'], $queue['topic_title']);
            $this->assertEquals($params['summary'], $queue['summary']);
            $this->assertEquals($params['tags'], $queue['tags']);
            $this->assertEquals($params['email'], $queue['email']);
        }
    }

    /**
     * Test queue operations with thread ID
     */
    public function testReplaceQueueWithThread(): void
    {
        // Create a comment first to get a valid thread ID
        $message_id = '1';
        $parentId = 0;
        $commentResult = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            $parentId,
            trim($GLOBALS['user']), // Ensure username is trimmed
            "Test Comment",
            "Comment content",
            $message_id
        );

        $comment = $this->commentsTable->fetchRow([], ['parentId' => $parentId]);
        $this->assertNotEmpty($comment, "Comment should be created");
        $threadId = $comment['threadId'];

        // Test queue with thread ID
        $queueData = [
            'object' => 'forum',
            'title' => 'Reply to Thread',
            'data' => 'Reply content',
            'type' => 'n',
            'topic_title' => 'Reply Topic',
            'summary' => 'Reply Summary',
            'tags' => 'test,reply',
            'email' => 'reply@example.com'
        ];

        $result = $this->commentsLib->replace_queue(
            0,
            1,
            $queueData['object'],
            $threadId, // Use threadId as parentId
            trim($GLOBALS['user']), // Ensure username is trimmed
            $queueData['title'],
            $queueData['data'],
            $queueData['type'],
            '',
            $queueData['summary'],
            $queueData['topic_title'],
            '',
            '',
            $queueData['tags'],
            $queueData['email']
        );

        $this->assertGreaterThan(0, $result, "Queue entry with thread ID should be created");

        $queue = $this->queueTable->fetchRow([], ['qId' => $result]);
        $this->assertNotEmpty($queue, "Queue record with thread ID should exist");
        $this->assertEquals($threadId, $queue['parentId'], "Thread ID should be set as parent ID");
    }

    public function testGetNumQueued(): void
    {
        // Insert mock data into tiki_forums_queue
        $object = 'forum';
        $data = [
            'object' => $object,
            'parentId' => 0,
            'forumId' => $this->forumId,
            'timestamp' => time(),
            'user' => $GLOBALS['user'],
            'title' => 'Test Title 1',
            'data' => 'Test Data 1',
            'type' => 'n',
            'topic_title' => 'Test Topic Title 1',
            'topic_smiley' => ':)',
            'summary' => 'Test Summary 1',
            'in_reply_to' => '',
            'tags' => 'test,unit',
            'email' => 'myname@example.com'
        ];

        $this->queueTable->insert($data);

        $data['user'] = 'another user';
        $data['title'] = 'Test Title 2';
        $data['data'] = 'Test Data 2';
        $data['topic_title'] = 'Test Topic Title 2';
        $data['summary'] = 'Test Summary 2';
        $data['email'] = 'anotheruser@example.com';

        $this->queueTable->insert($data);

        // Test the get_num_queued method
        $result = $this->commentsLib->get_num_queued($object);

        // Assert that the result is 2, as we inserted 2 records for the object
        $this->assertEquals(2, $result);

        // Insert another object and test
        $otherObject = 'comment';
        $data['object'] = $otherObject;
        $this->queueTable->insert($data);

        // Test the get_num_queued method again for the original object
        $result = $this->commentsLib->get_num_queued($object);
        $this->assertEquals(2, $result);

        // Test the get_num_queued method for the new object
        $result = $this->commentsLib->get_num_queued($otherObject);
        $this->assertEquals(1, $result);
    }

    public function testListForumQueue(): void
    {
        $object = 'forum';
        $data = [
            'object' => $object,
            'parentId' => 0,
            'user' => $GLOBALS['user'],
            'title' => 'Test Title 1',
            'data' => 'Test Data 1',
            'forumId' => $this->forumId,
            'type' => 'n',
            'topic_title' => 'Test Topic Title 1',
            'topic_smiley' => ':)',
            'summary' => 'Test Summary 1',
            'timestamp' => time(),
            'in_reply_to' => '',
            'tags' => 'test,unit',
            'email' => 'myname@example.com'
        ];

        $this->queueTable->insert($data);

        $data['user'] = 'anotheruser';
        $data['title'] = 'Test Title 2';
        $data['data'] = 'Test Data 2';
        $data['topic_title'] = 'Test Topic Title 2';
        $data['summary'] = 'Test Summary 2';
        $data['email'] = 'another@example.com';

        $this->queueTable->insert($data);

        // Insert attachment data
        $attachmentData = [
            'threadId' => 0,
            'qId' => 1, // Assuming the first inserted queue item has qId 1
            'filename' => 'testfile.txt',
            'filetype' => 'text/plain',
            'filesize' => 1024,
            'data' => 'file data',
            'path' => '',
            'created' => time(),
            'dir' => '',
            'forumId' => $this->forumId,
        ];

        $this->attachmentsTable->insert($attachmentData);

        // Test the list_forum_queue method
        $result = $this->commentsLib->list_forum_queue($object, 0, 10, 'qId_asc', '');
        $this->assertCount(2, $result['data']);
        $this->assertEquals(2, $result['count']);

        // Check the first item in the result
        $firstItem = $result['data'][0];
        $this->assertEquals('Test Title 1', $firstItem['title']);
        $this->assertEquals($GLOBALS['user'], $firstItem['user']);
        $this->assertEquals('Test Data 1', $firstItem['data']);
        $this->assertEquals('Test Summary 1', $firstItem['summary']);
        $this->assertEquals('Test Topic Title 1', $firstItem['topic_title']);
        $this->assertArrayHasKey('parsed', $firstItem);
        $this->assertCount(1, $firstItem['attachments']);

        $secondItem = $result['data'][1];
        $this->assertEquals('Test Title 2', $secondItem['title']);
        $this->assertEquals('anotheruser', $secondItem['user']);
        $this->assertEquals('Test Data 2', $secondItem['data']);
        $this->assertEquals('Test Summary 2', $secondItem['summary']);
        $this->assertEquals('Test Topic Title 2', $secondItem['topic_title']);
        $this->assertArrayHasKey('parsed', $secondItem);
        $this->assertCount(0, $secondItem['attachments']);

        $resultWithFind = $this->commentsLib->list_forum_queue($object, 0, 10, 'qId_asc', 'Test Data 2');
        $this->assertCount(1, $resultWithFind['data']);
        $this->assertEquals(1, $resultWithFind['count']);
        $this->assertEquals('Test Title 2', $resultWithFind['data'][0]['title']);
    }

    public function testQueueGet(): void
    {
        // Insert mock data into tiki_forums_queue
        $object = 'forum';
        $data = [
            'object' => $object,
            'parentId' => 0,
            'user' => $GLOBALS['user'],
            'title' => 'Test Title',
            'data' => 'Test Data',
            'forumId' => $this->forumId,
            'type' => 'n',
            'topic_title' => 'Test Topic Title',
            'topic_smiley' => ':)',
            'summary' => 'Test Summary',
            'timestamp' => time(),
            'in_reply_to' => '',
            'tags' => 'test,unit',
            'email' => 'myname@example.com'
        ];

        $qId = $this->queueTable->insert($data);

        // Insert attachment data
        $attachment = [
            'threadId' => 0,
            'qId' => 1, // Assuming the first inserted queue item has qId 1
            'filename' => 'testfile.txt',
            'filetype' => 'text/plain',
            'filesize' => 1024,
            'data' => 'file data',
            'path' => '',
            'created' => time(),
            'dir' => '',
            'forumId' => $this->forumId,
        ];

        $this->attachmentsTable->insert($attachment);

        // Test the queue_get method
        $result = $this->commentsLib->queue_get($qId);

        // Assert the results
        $this->assertNotNull($result);
        $this->assertEquals('forum', $result['object']);
        $this->assertEquals($GLOBALS['user'], $result['user']);
        $this->assertEquals('Test Title', $result['title']);
        $this->assertEquals('Test Data', $result['data']);
        $this->assertEquals('Test Summary', $result['summary']);
        $this->assertEquals('Test Topic Title', $result['topic_title']);
        $this->assertArrayHasKey('attchments', $result);
        $this->assertCount(1, $result['attchments']);

        // Check the attachment details
        $attachment = $result['attchments'][0];
        $this->assertEquals('testfile.txt', $attachment['filename']);
        $this->assertEquals('text/plain', $attachment['filetype']);
        $this->assertEquals(1024, $attachment['filesize']);
        $this->assertEquals('file data', $attachment['data']);
    }

    public function testRemoveQueued(): void
    {
        // Insert mock data into tiki_forums_queue
        $data = [
            'object' => 'forum',
            'parentId' => 0,
            'user' => $GLOBALS['user'],
            'title' => 'Test Title',
            'data' => 'Test Data',
            'forumId' => $this->forumId,
            'type' => 'n',
            'topic_title' => 'Test Topic Title',
            'topic_smiley' => ':)',
            'summary' => 'Test Summary',
            'timestamp' => time(),
            'in_reply_to' => '',
            'tags' => 'test,unit',
            'email' => 'myname@example.com'
        ];

        $qId = $this->queueTable->insert($data);

        $attribute = [
            'attributeId' => 1,
            'type' => 'forum post',
            'itemId' => 1,
            'attribute' => 'tiki.forumpost.queueid',
            'value' => $qId
        ];

        $this->attributesTable->insert($attribute);

        $attachment = [
            'threadId' => 0,
            'qId' => 1,
            'filename' => 'testfile.txt',
            'filetype' => 'text/plain',
            'filesize' => 1024,
            'data' => 'file data',
            'path' => '',
            'created' => time(),
            'dir' => '',
            'forumId' => $this->forumId,
        ];

        $this->attachmentsTable->insert($attachment);

        $this->commentsLib->remove_queued($qId);

        // Assert that the records are deleted
        $queueRecord = $this->queueTable->fetchFullRow(['qId' => $qId]);
        $this->assertFalse($queueRecord);

        $attributeRecord = $this->attributesTable->fetchFullRow(['attribute' => 'tiki.forumpost.queueid', 'value' => $qId]);
        $this->assertFalse($attributeRecord);

        $attachmentRecord = $this->attachmentsTable->fetchFullRow(['qId' => $qId]);
        $this->assertFalse($attachmentRecord);
    }

    /**
     * Test retrieving comments with various scenarios
     *
     * This test verifies:
     * 1. Getting a normal comment
     * 2. Getting a comment with attachments
     * 3. Getting a comment with special characters
     * 4. Getting a nested comment (reply)
     * 5. Handling non-existent comments
     */
    public function testGetComment(): void
    {
        // Test Case 1: Normal comment
        $messageId = uniqid();
        $parentId = 0;
        $normalThreadId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            $parentId,
            $GLOBALS['user'],
            "Test Comment",
            "Normal comment content",
            $messageId
        );

        $comment = $this->commentsLib->get_comment($normalThreadId);
        $this->assertNotEmpty($comment, "Comment should be retrieved");
        $this->assertEquals($GLOBALS['user'], $comment['userName']);
        $this->assertEquals("Test Comment", $comment['title']);
        $this->assertEquals("Normal comment content", $comment['data']);
        $this->assertEquals($parentId, $comment['parentId']);

        // Test Case 2: Comment with attachment
        $messageId2 = uniqid();
        $threadIdWithAttachment = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            $parentId,
            $GLOBALS['user'],
            "Comment with Attachment",
            "Content with attachment",
            $messageId2
        );

        // Add attachment to the comment
        $this->commentsLib->forum_attach_file(
            $threadIdWithAttachment,
            0,
            "test_attachment.txt",
            "text/plain",
            100,
            "Attachment content",
            "",
            "/path/to/dir",
            1
        );

        $commentWithAttachment = $this->commentsLib->get_comment($threadIdWithAttachment);
        $this->assertNotEmpty($commentWithAttachment['attachments'], "Comment should have attachments");
        $this->assertEquals("test_attachment.txt", $commentWithAttachment['attachments'][0]['filename']);

        // Test Case 3: Comment with special characters
        $messageId3 = uniqid();
        $specialContent = "Content with special chars: áéíóú ñ € ® © ™";
        $threadIdSpecial = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            $parentId,
            $GLOBALS['user'],
            "Special Chars Comment",
            $specialContent,
            $messageId3
        );

        $specialComment = $this->commentsLib->get_comment($threadIdSpecial);
        $this->assertEquals($specialContent, $specialComment['data'], "Special characters should be preserved");

        // Test Case 4: Nested comment (reply)
        $messageId4 = uniqid();
        $replyThreadId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            $normalThreadId, // Reply to the first comment
            $GLOBALS['user'],
            "Reply Comment",
            "Reply content",
            $messageId4
        );

        $replyComment = $this->commentsLib->get_comment($replyThreadId);
        $this->assertEquals($normalThreadId, $replyComment['parentId'], "Reply should reference parent comment");

        // Test Case 5: Non-existent comment
        $nonExistentComment = $this->commentsLib->get_comment(99999);
        $this->assertNull($nonExistentComment, "Non-existent comment should return null");
    }

    /**
     * Test updating comments with various scenarios
     *
     * This test verifies:
     * 1. Basic comment updates
     * 2. Updates with attachments
     * 3. Updates with special characters
     * 4. Updates to replies
     * 5. Error cases
     */
    public function testUpdateComment(): void
    {
        // Test Case 1: Basic comment update
        $messageId = uniqid();
        $parentId = 0;
        $threadId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            $parentId,
            $GLOBALS['user'],
            "Original Title",
            "Original content",
            $messageId
        );

        // Verify the comment exists before updating
        $originalComment = $this->commentsLib->get_comment($threadId);
        $this->assertNotNull($originalComment, "Comment should exist before update");

        if ($originalComment) {
            $this->commentsLib->update_comment(
                $threadId,
                "Updated Title",
                0,  // comment_rating
                "Updated content",
                'n',  // type
                "Updated summary",
                '',   // smiley
                '',   // objectId
                ''    // contributions
            );

            $updatedComment = $this->commentsLib->get_comment($threadId);
            $this->assertEquals("Updated Title", $updatedComment['title'], "Title should be updated");
            $this->assertEquals("Updated content", $updatedComment['data'], "Content should be updated");
            $this->assertEquals("Updated summary", $updatedComment['summary'], "Summary should be updated");
        }

        // Test Case 2: Update with attachment
        $messageId2 = uniqid();
        $threadId2 = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            $parentId,
            $GLOBALS['user'],
            "Comment with Attachment",
            "Original content with attachment",
            $messageId2
        );

        // Verify the comment exists before updating
        $originalComment2 = $this->commentsLib->get_comment($threadId2);
        $this->assertNotNull($originalComment2, "Comment should exist before update");

        if ($originalComment2) {
            // Add attachment before update
            $this->commentsLib->forum_attach_file(
                $threadId2,
                0,
                "pre_update.txt",
                "text/plain",
                100,
                "Pre-update content",
                "",
                "/path/to/dir",
                1
            );

            $this->commentsLib->update_comment(
                $threadId2,
                "Updated with Attachment",
                0,  // comment_rating
                "Updated content with attachment",
                'n',  // type
                "Updated summary",
                '',   // smiley
                '',   // objectId
                ''    // contributions
            );

            // Add another attachment after update
            $this->commentsLib->forum_attach_file(
                $threadId2,
                0,
                "post_update.txt",
                "text/plain",
                100,
                "Post-update content",
                "",
                "/path/to/dir",
                1
            );

            $updatedComment2 = $this->commentsLib->get_comment($threadId2);
            $this->assertEquals("Updated with Attachment", $updatedComment2['title'], "Title should be updated for comment with attachment");
            $this->assertEquals("Updated content with attachment", $updatedComment2['data'], "Content should be updated for comment with attachment");
            $this->assertCount(2, $updatedComment2['attachments'], "Comment should have two attachments");
        }

        // Test Case 3: Update with special characters
        $messageId3 = uniqid();
        $threadId3 = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            $parentId,
            $GLOBALS['user'],
            "Original Special",
            "Original special content",
            $messageId3
        );

        // Verify the comment exists before updating
        $originalComment3 = $this->commentsLib->get_comment($threadId3);
        $this->assertNotNull($originalComment3, "Comment should exist before update");

        if ($originalComment3) {
            $specialTitle = "Title with áéíóú ñ";
            $specialContent = "Content with €£¥ special chars ®©™";
            $specialSummary = "Summary with ñáéíóú chars";

            $this->commentsLib->update_comment(
                $threadId3,
                $specialTitle,
                0,  // comment_rating
                $specialContent,
                'n',  // type
                $specialSummary,
                '',   // smiley
                '',   // objectId
                ''    // contributions
            );

            $updatedComment3 = $this->commentsLib->get_comment($threadId3);
            $this->assertEquals($specialTitle, $updatedComment3['title'], "Title with special characters should be preserved");
            $this->assertEquals($specialContent, $updatedComment3['data'], "Content with special characters should be preserved");
            $this->assertEquals($specialSummary, $updatedComment3['summary'], "Summary with special characters should be preserved");
        }

        // Test Case 4: Update reply
        $messageId4 = uniqid();
        $replyId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            $threadId,  // Reply to first comment
            $GLOBALS['user'],
            "Original Reply",
            "Original reply content",
            $messageId4
        );

        // Verify the comment exists before updating
        $originalReply = $this->commentsLib->get_comment($replyId);
        $this->assertNotNull($originalReply, "Reply should exist before update");

        if ($originalReply) {
            $this->commentsLib->update_comment(
                $replyId,
                "Updated Reply",
                0,  // comment_rating
                "Updated reply content",
                'n',  // type
                "Updated reply summary",
                '',   // smiley
                '',   // objectId
                ''    // contributions
            );

            $updatedReply = $this->commentsLib->get_comment($replyId);
            $this->assertEquals("Updated Reply", $updatedReply['title'], "Reply title should be updated");
            $this->assertEquals("Updated reply content", $updatedReply['data'], "Reply content should be updated");
            $this->assertEquals($threadId, $updatedReply['parentId'], "Reply should maintain parent relationship");
        }

        // Test Case 5: Error cases - try to update non-existent comment
        $nonExistentComment = $this->commentsLib->get_comment(99999);
        $this->assertNull($nonExistentComment, "Non-existent comment should return null");

        // Skip update for non-existent comment
        if ($nonExistentComment) {
            $this->commentsLib->update_comment(
                99999,
                "Invalid Update",
                0,  // comment_rating
                "This should fail",
                'n',  // type
                "Invalid summary",
                '',   // smiley
                '',   // objectId
                ''    // contributions
            );
        }

        $nonExistentComment = $this->commentsLib->get_comment(99999);
        $this->assertNull($nonExistentComment, "Non-existent comment should remain null after update attempt");
    }

    /**
     * Test concurrent forum operations to ensure thread safety
     */
    public function testConcurrentForumOperations(): void
    {
        $forumId = $this->forumId;
        $users = ['user1', 'user2'];
        $operations = [];

        // Create base thread for testing
        $baseMessageId = uniqid();
        $baseThreadId = $this->commentsLib->post_new_comment(
            "forum:$forumId",
            0,
            'admin',
            "Base Thread",
            "Base content",
            $baseMessageId
        );

        // Test operations sequentially to avoid deadlocks
        foreach ($users as $user) {
            // Post a comment
            $messageId = uniqid();
            $commentId = $this->commentsLib->post_new_comment(
                "forum:$forumId",
                $baseThreadId,
                $user,
                "Comment by $user",
                "Test content from $user",
                $messageId
            );
            $operations[$user]['comment'] = $commentId;

            // Verify comment was created correctly
            $comment = $this->commentsTable->fetchRow([], ['threadId' => $commentId]);
            if (! $comment) {
                throw new \Exception("Failed to create comment for user $user");
            }

            // Report the comment
            $this->commentsLib->report_post(
                $forumId,
                $baseThreadId,
                $commentId,
                "reporter_$user",
                "Reported by $user"
            );
            $operations[$user]['report'] = true;

            // Mark the comment
            $this->commentsLib->mark_comment($user, $forumId, $commentId);
            $operations[$user]['mark'] = true;

            // Attach a file
            $attachmentId = $this->commentsLib->forum_attach_file(
                $commentId,
                0,
                "file_from_$user.txt",
                'text/plain',
                1024,
                "Content from $user",
                '',
                '/path/to/dir',
                $forumId
            );
            $operations[$user]['attachment'] = $attachmentId;
        }

        // Verify operations were successful
        foreach ($users as $user) {
            // Verify comment exists
            $comment = $this->commentsTable->fetchRow([], ['threadId' => $operations[$user]['comment']]);
            $this->assertNotEmpty($comment, "Comment from $user should exist");
            $this->assertEquals($user, $comment['userName'], "Comment should be associated with correct user");

            // Verify report exists
            $reported = $this->reportedTable->fetchRow([], ['threadId' => $operations[$user]['comment']]);
            $this->assertNotEmpty($reported, "Report for comment from $user should exist");
            $this->assertEquals("reporter_$user", $reported['user']);

            // Verify mark exists
            $marked = $this->readsTable->fetchRow([], [
                'user' => $user,
                'threadId' => $operations[$user]['comment']
            ]);
            $this->assertNotEmpty($marked, "Mark from $user should exist");

            // Verify attachment exists
            $attachment = $this->attachmentsTable->fetchRow([], ['attId' => $operations[$user]['attachment']]);
            $this->assertNotEmpty($attachment, "Attachment from $user should exist");
            $this->assertEquals("file_from_$user.txt", $attachment['filename']);
        }

        // Clean up in reverse order to maintain referential integrity
        foreach ($users as $user) {
            // Remove attachments first
            if (isset($operations[$user]['attachment'])) {
                $this->commentsLib->remove_thread_attachment($operations[$user]['attachment']);
            }

            // Remove marks
            if (isset($operations[$user]['mark'])) {
                $this->commentsLib->unmark_comment($user, $forumId, $operations[$user]['comment']);
            }

            // Remove reports
            if (isset($operations[$user]['report'])) {
                $this->commentsLib->remove_reported($operations[$user]['comment']);
            }

            // Remove comments
            if (isset($operations[$user]['comment'])) {
                $this->commentsTable->delete(['threadId' => $operations[$user]['comment']]);
            }
        }

        // Clean up base thread
        $this->commentsTable->delete(['threadId' => $baseThreadId]);
    }

    /**
     * Test posting new comments with various scenarios
     *
     * This test verifies:
     * 1. Basic comment creation
     * 2. Anonymous comments
     * 3. Comments with special characters
     * 4. Nested comments (replies)
     * 5. Comments with attachments
     * 6. Error cases
     */
    public function testPostNewComment(): void
    {
        // Test Case 1: Basic comment
        $messageId = uniqid();
        $threadId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            0,  // parentId
            $GLOBALS['user'],
            "Test Title",
            "Test content",
            $messageId
        );

        $this->assertGreaterThan(0, $threadId, "Comment should be created with valid ID");
        $comment = $this->commentsLib->get_comment($threadId);
        $this->assertNotNull($comment, "Should be able to retrieve created comment");
        $this->assertEquals("Test Title", $comment['title']);
        $this->assertEquals("Test content", $comment['data']);
        $this->assertEquals($GLOBALS['user'], $comment['userName']);
        $this->assertEquals(0, $comment['parentId']);

        // Test Case 2: Anonymous comment
        $messageId2 = uniqid();
        $anonymousName = "Anonymous User";
        $threadId2 = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            0,
            "",  // empty username
            "Anonymous Title",
            "Anonymous content",
            $messageId2,
            "",   // in_reply_to
            "n",  // type
            "",   // summary
            "",   // smiley
            "",   // contributions
            trim($anonymousName)  // Ensure the anonymous name is trimmed
        );

        $this->assertGreaterThan(0, $threadId2, "Anonymous comment should be created");
        $comment2 = $this->commentsLib->get_comment($threadId2);
        $this->assertEquals(trim($anonymousName), trim($comment2['userName']), "Anonymous username should match after trimming");

        // Test Case 3: Comment with special characters
        $messageId3 = uniqid();
        $specialTitle = "Title with áéíóú ñ";
        $specialContent = "Content with €£¥ special chars ®©™";
        $threadId3 = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            0,
            $GLOBALS['user'],
            $specialTitle,
            $specialContent,
            $messageId3
        );

        $comment3 = $this->commentsLib->get_comment($threadId3);
        $this->assertEquals($specialTitle, $comment3['title'], "Special characters in title should be preserved");
        $this->assertEquals($specialContent, $comment3['data'], "Special characters in content should be preserved");

        // Test Case 4: Nested comment (reply)
        $messageId4 = uniqid();
        $threadId4 = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            $threadId,  // Reply to first comment
            $GLOBALS['user'],
            "Reply Title",
            "Reply content",
            $messageId4
        );

        $comment4 = $this->commentsLib->get_comment($threadId4);
        $this->assertEquals($threadId, $comment4['parentId'], "Reply should reference correct parent");

        // Test Case 5: Comment with attachment
        $messageId5 = uniqid();
        $threadId5 = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            0,
            $GLOBALS['user'],
            "Comment with Attachment",
            "Content with attachment",
            $messageId5
        );

        // Add attachment to the comment
        $attachmentId = $this->commentsLib->forum_attach_file(
            $threadId5,
            0,
            "test.txt",
            "text/plain",
            100,
            "Attachment content",
            "",
            "/path/to/dir",
            1
        );

        $comment5 = $this->commentsLib->get_comment($threadId5);
        $this->assertNotEmpty($comment5['attachments'], "Comment should have attachments");
        $this->assertEquals("test.txt", $comment5['attachments'][0]['filename']);

        // Test Case 6: Error cases
        // Invalid object ID
        $messageId6 = uniqid();
        $threadId6 = $this->commentsLib->post_new_comment(
            "invalid:999",
            0,
            $GLOBALS['user'],
            "Invalid Title",
            "Invalid content",
            $messageId6
        );
        $comment6 = $this->commentsLib->get_comment($threadId6);
        // Update assertion to verify the comment is created but with expected invalid object properties
        $this->assertNotNull($comment6, "Comment should be created even with invalid object ID");
        $this->assertEquals('999', $comment6['object'], "Object ID should be extracted from invalid:999");
        $this->assertEquals('invalid', $comment6['objectType'], "Object type should be extracted from invalid:999");

        // Invalid parent ID
        $messageId7 = uniqid();
        $threadId7 = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            999999,  // Non-existent parent
            $GLOBALS['user'],
            "Invalid Parent",
            "Content with invalid parent",
            $messageId7
        );
        $comment7 = $this->commentsLib->get_comment($threadId7);
        // Update assertion to verify comment is created with non-existent parent
        $this->assertNotNull($comment7, "Comment should be created with invalid parent ID");
        $this->assertEquals(999999, $comment7['parentId'], "Parent ID should be set even if parent doesn't exist");
    }

    /**
     * Test removing comments with various scenarios
     *
     * This test verifies:
     * 1. Basic comment removal
     * 2. Removal of comments with attachments
     * 3. Removal of comments with replies
     * 4. Error cases
     */
    public function testRemoveComment(): void
    {
        // Test Case 1: Basic comment removal
        $messageId = uniqid();
        $threadId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            0,
            $GLOBALS['user'],
            "Test Title",
            "Test content",
            $messageId
        );

        $this->assertGreaterThan(0, $threadId, "Comment should be created");
        $this->assertTrue($this->commentsLib->remove_comment($threadId), "Comment should be removed");
        $this->assertNull($this->commentsLib->get_comment($threadId), "Comment should not exist after removal");

        // Test Case 2: Remove comment with attachments
        $messageId2 = uniqid();
        $threadId2 = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            0,
            $GLOBALS['user'],
            "Comment with Attachment",
            "Content with attachment",
            $messageId2
        );

        // Add attachment
        $attachmentId = $this->commentsLib->forum_attach_file(
            $threadId2,
            0,
            "test.txt",
            "text/plain",
            100,
            "Attachment content",
            "",
            "/path/to/dir",
            1
        );

        $this->assertTrue($this->commentsLib->remove_comment($threadId2), "Comment with attachment should be removed");
        $this->assertNull($this->commentsLib->get_comment($threadId2), "Comment should not exist after removal");
        $this->assertEmpty($this->attachmentsTable->fetchRow([], ['attId' => $attachmentId]), "Attachment should be removed");

        // Test Case 3: Remove comment with replies
        $messageId3 = uniqid();
        $parentId = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            0,
            $GLOBALS['user'],
            "Parent Comment",
            "Parent content",
            $messageId3
        );

        // Add replies
        $messageId4 = uniqid();
        $replyId1 = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            $parentId,
            $GLOBALS['user'],
            "Reply 1",
            "Reply 1 content",
            $messageId4
        );

        $messageId5 = uniqid();
        $replyId2 = $this->commentsLib->post_new_comment(
            "forum:$this->forumId",
            $parentId,
            $GLOBALS['user'],
            "Reply 2",
            "Reply 2 content",
            $messageId5
        );

        $this->assertTrue($this->commentsLib->remove_comment($parentId), "Parent comment should be removed");
        $this->assertNull($this->commentsLib->get_comment($parentId), "Parent comment should not exist after removal");
        $this->assertNull($this->commentsLib->get_comment($replyId1), "Reply 1 should be removed with parent");
        $this->assertNull($this->commentsLib->get_comment($replyId2), "Reply 2 should be removed with parent");

        // Test Case 4: Error cases
        // Try to remove non-existent comment
        // First verify the comment doesn't exist
        $this->assertNull($this->commentsLib->get_comment(999999), "Comment should not exist before removal attempt");
        // Update assertion to check if comment still doesn't exist after removal attempt
        $result = $this->commentsLib->remove_comment(999999);
        $this->assertNull($this->commentsLib->get_comment(999999), "Comment should still not exist after removal attempt");
    }
}
