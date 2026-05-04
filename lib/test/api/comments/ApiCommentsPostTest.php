<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Comments;

use TikiLib;

/**
 * Integration tests for Comments API POST endpoints
 *
 * @group api-integration-test
 * @group api-comments
 */
class ApiCommentsPostTest extends ApiBaseCommentsTest
{
    public function testApiUpdateCommentWithoutPermission()
    {
        $pageName = 'POST_UpdatePermTestPage';
        static::createWikiPage($pageName);

        $createData = [
            'type' => 'wiki page',
            'objectId' => $pageName,
            'title' => 'Original Title',
            'data' => 'Original data',
        ];

        $createResponse = $this->makeApiRequest(
            'POST',
            '/comments',
            'Admins',
            $createData,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($createResponse);
        $threadId = $body['threadId'];

        // Try to update as anonymous
        $updateData = [
            'title' => 'Hacked Title',
            'data' => 'Hacked data',
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId,
            null, // Anonymous user
            $updateData,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiCreateCommentWithoutPermission()
    {
        $pageName = 'POST_TestPermPage';
        static::createWikiPage($pageName);

        $data = [
            'type' => 'wiki page',
            'objectId' => $pageName,
            'title' => 'Test Comment',
            'data' => 'This should fail',
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/comments',
            null, // Anonymous user
            $data,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied.');
    }

    public function testApiCreateCommentOnWikiPage()
    {
        $pageName = static::$defaultComments['wiki page']['objectId'];
        $data = [
            'type' => 'wiki page',
            'objectId' => $pageName,
            'title' => 'Test Comment',
            'data' => 'This is a test comment on wiki page',
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/comments',
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidCommentResponse($body);

        // Track for cleanup
        static::$testComments[] = $body['threadId'];
    }

    public function testApiCreateReplyComment()
    {
        $pageName = static::$defaultComments['wiki page']['objectId'];

        // Create parent comment
        $parentData = [
            'type' => 'wiki page',
            'objectId' => $pageName,
            'title' => 'Parent Comment',
            'data' => 'This is the parent comment',
        ];

        $parentResponse = $this->makeApiRequest(
            'POST',
            '/comments',
            'Admins',
            $parentData,
            'application/x-www-form-urlencoded'
        );

        $bodyParent = $this->getResponseBody($parentResponse);
        $parentThreadId = $bodyParent['threadId'];

        // Create reply comment
        $replyData = [
            'type' => 'wiki page',
            'objectId' => $pageName,
            'parentId' => $parentThreadId,
            'title' => 'Reply Comment',
            'data' => 'This is a reply to the parent comment',
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/comments',
            'Admins',
            $replyData,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidCommentResponse($body);
        $this->assertEquals($parentThreadId, $body['parentId'], 'Reply should have correct parentId');

        // Track for cleanup
        static::$testComments[] = $parentThreadId;
        static::$testComments[] = $body['threadId'];
    }

    public function testApiCreateCommentOnDifferentObjectTypes()
    {
        $objectTypes = [
            ['type' => 'wiki page', 'createMethod' => 'createWikiPage', 'objectName' => 'POST_MultiTypeWikiPage'],
            ['type' => 'file gallery', 'createMethod' => 'createFileGallery', 'objectName' => 'POST_MultiTypeGallery'],
            ['type' => 'poll', 'createMethod' => 'createPoll', 'objectName' => 'POST_MultiTypePoll'],
            ['type' => 'faq', 'createMethod' => 'createFaq', 'objectName' => 'POST_MultiTypeFAQ'],
            ['type' => 'blog post', 'createMethod' => 'createBlogPost', 'objectName' => 'POST_MultiTypeBlogPost'],
            ['type' => 'trackeritem', 'createMethod' => 'createTrackerItem', 'objectName' => 'POST_MultiTypeTrackerItem'],
            ['type' => 'article', 'createMethod' => 'createArticle', 'objectName' => 'POST_MultiTypeArticle'],
        ];

        foreach ($objectTypes as $typeInfo) {
            $objectId = static::{$typeInfo['createMethod']}($typeInfo['objectName']);

            if (! $objectId) {
                $this->markTestSkipped("Could not create {$typeInfo['type']} object");
                continue;
            }

            $data = [
                'type' => $typeInfo['type'],
                'objectId' => $objectId,
                'title' => "Test comment on {$typeInfo['type']}",
                'data' => "Comment content for {$typeInfo['type']}",
            ];

            $response = $this->makeApiRequest(
                'POST',
                '/comments',
                'Admins',
                $data,
                'application/x-www-form-urlencoded'
            );

            $this->assertResponseStatus(200, $response, "Creating comment on {$typeInfo['type']}");

            $body = $this->getResponseBody($response);
            $this->assertValidCommentResponse($body);
            $this->assertEquals($typeInfo['type'], $body['type']);

            // Track for cleanup
            static::$testComments[] = $body['threadId'];
        }
    }

    public function testApiCreateCommentWithoutRequiredFields()
    {
        // Missing type
        $data = [
            'objectId' => 'TestPage',
            'title' => 'Test',
            'data' => 'Test data',
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/comments',
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Comments not allowed on this page.');
    }

    public function testApiUpdateComment()
    {
        // Create a wiki page and comment
        $pageName = 'POST_UpdateTestPage';
        static::createWikiPage($pageName);

        $createData = [
            'type' => 'wiki page',
            'objectId' => $pageName,
            'title' => 'Original Title',
            'data' => 'Original comment data',
        ];

        $createResponse = $this->makeApiRequest(
            'POST',
            '/comments',
            'Admins',
            $createData,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($createResponse);
        $threadId = $body['threadId'];

        // Update the comment
        $updateData = [
            'title' => 'Updated Title',
            'data' => 'Updated comment data',
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId,
            'Admins',
            $updateData,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidUpdateCommentResponse($body);
        $this->assertEquals('Updated Title', $body['comment']['title']);
        $this->assertStringContainsString('Updated comment data', $body['comment']['data']);

        // Track for cleanup
        static::$testComments[] = $threadId;
    }

    public function testApiUpdateNonExistentComment()
    {
        $updateData = [
            'title' => 'Updated Title',
            'data' => 'Updated data',
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/comments/99999',
            'Admins',
            $updateData,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiLockCommentWithoutPermission()
    {
        $threadId = static::$defaultComments['wiki page']['threadId'];
        // Try to lock as anonymous
        $data = [
            'type' => 'wiki page',
            'objectId' => static::$defaultComments['wiki page']['objectId'],
        ];
        $response = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId . '/lock',
            null, // Anonymous user
            $data,
            'application/x-www-form-urlencoded'
        );
        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permissions denied.');
    }

    public function testApiLockComment()
    {
        // createComment
        $threadId = static::createComment(
            'wiki page',
            static::$defaultComments['wiki page']['objectId'],
            'Lock Test Comment',
            'This comment will be locked.'
        );
        $data = [
            'type' => 'wiki page',
            'objectId' => static::$defaultComments['wiki page']['objectId'],
        ];
        $response = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId . '/lock',
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );
        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidLockCommentResponse($body);
        $this->assertTrue($body['status'] == 'DONE', 'Status should be DONE after locking the comment');
        $this->assertStringContainsString('Lock comments', $body['title'], 'Title should indicate lock action');
    }

    public function testApiLockAlreadyLockedComment()
    {
        $threadId = static::createComment(
            'wiki page',
            static::$defaultComments['wiki page']['objectId'],
            'Lock Test Comment 2',
            'This comment will be locked twice.'
        );
        $data = [
            'type' => 'wiki page',
            'objectId' => static::$defaultComments['wiki page']['objectId'],
        ];

        // Lock the comment via the API so the subprocess (which reads fresh DB prefs) sets the state.
        $this->makeApiRequest('POST', '/comments/' . $threadId . '/lock', 'Admins', $data, 'application/x-www-form-urlencoded');

        // Locking an already-locked comment should be rejected
        $response = $this->makeApiRequest('POST', '/comments/' . $threadId . '/lock', 'Admins', $data, 'application/x-www-form-urlencoded');
        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Comments already locked.');
    }

    public function testApiLockCommentWithMissingParameters()
    {
        $threadId = static::$defaultComments['wiki page']['threadId'];
        // Missing objectId
        $data = [
            'type' => 'wiki page',
        ];
        $response = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId . '/lock',
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );
        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 409, '<!--field[objectId]-->Field Required');
    }

    public function testApiUnlockCommentWithoutPermission()
    {
        $threadId = static::$defaultComments['wiki page']['threadId'];
        // Try to unlock as anonymous
        $data = [
            'type' => 'wiki page',
            'objectId' => static::$defaultComments['wiki page']['objectId'],
        ];
        $response = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId . '/unlock',
            null, // Anonymous user
            $data,
            'application/x-www-form-urlencoded'
        );
        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permissions denied.');
    }

    public function testApiUnlockComment()
    {
        $pageName = 'POST_UnlockTestPage_' . uniqid();
        static::createWikiPage($pageName);

        $threadId = static::createComment(
            'wiki page',
            $pageName,
            'Unlock Test Comment',
            'This comment will be locked and then unlocked.'
        );
        $data = [
            'type' => 'wiki page',
            'objectId' => $pageName,
        ];

        // Lock via the API so the subprocess sets the locked state using fresh DB prefs.
        $lockResponse = $this->makeApiRequest('POST', '/comments/' . $threadId . '/lock', 'Admins', $data, 'application/x-www-form-urlencoded');
        $this->assertResponseStatus(200, $lockResponse, 'Setup lock step should succeed before testing unlock');

        // Unlock the comment
        $response = $this->makeApiRequest('POST', '/comments/' . $threadId . '/unlock', 'Admins', $data, 'application/x-www-form-urlencoded');
        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidLockCommentResponse($body);
        $this->assertTrue($body['status'] == 'DONE', 'Status should be DONE after unlocking the comment');
        $this->assertStringContainsString('Unlock comments', $body['title'], 'Title should indicate unlock action');
    }

    public function testApiUnlockAlreadyUnlockedComment()
    {
        $pageName = 'POST_UnlockAlreadyUnlocked_' . uniqid();
        static::createWikiPage($pageName);

        $threadId = static::createComment('wiki page', $pageName, 'Unlock Test Comment 2', 'This comment will be unlocked twice.');
        $data = [
            'type' => 'wiki page',
            'objectId' => $pageName,
        ];

        // A fresh page's object thread is unlocked — trying to unlock should be rejected
        $response = $this->makeApiRequest('POST', '/comments/' . $threadId . '/unlock', 'Admins', $data, 'application/x-www-form-urlencoded');
        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Comments already unlocked.');
    }

    public function testApiUnlockCommentWithMissingParameters()
    {
        $threadId = static::$defaultComments['wiki page']['threadId'];
        // Missing objectId
        $data = [
            'type' => 'wiki page',
        ];
        $response = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId . '/unlock',
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );
        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 409, '<!--field[objectId]-->Field Required');
    }

    public function testApiApproveCommentWithoutPermission()
    {
        $threadId = static::createComment(
            'wiki page',
            static::$defaultComments['wiki page']['objectId'],
            'Approve Test Comment',
            'This comment will be approved.'
        );
        // The comment might already be approved (default state), so we start by rejecting it to ensure a clean state.
        $commentslib = TikiLib::lib('comments');
        $commentslib->reject_comment($threadId);

        $response = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId . '/approve',
            null, // Anonymous user
        );
        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied.');
    }

    public function testApiApproveComment()
    {
        $threadId = static::createComment(
            'wiki page',
            static::$defaultComments['wiki page']['objectId'],
            'Approve Test Comment 2',
            'This comment will be approved.'
        );
        // The comment might already be approved (default state), so we start by rejecting it to ensure a clean state.
        $commentslib = TikiLib::lib('comments');
        $commentslib->reject_comment($threadId);

        $response = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId . '/approve',
            'Admins',
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidModerateCommentResponse($body);
        $this->assertTrue($body['status'] == 'DONE', 'Status should be DONE after approving the comment');
        $this->assertTrue($body['do'] == 'approve', 'Do should be APPROVE after approving the comment');
        $this->assertEquals($threadId, $body['threadId'], 'threadId should match the approved comment');
    }

    public function testApiApproveNonExistentComment()
    {
        $response = $this->makeApiRequest(
            'POST',
            '/comments/99999/approve',
            'Admins',
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Comment not found. (404)');
    }

    public function testApiApproveAlreadyApprovedComment()
    {
        $threadId = static::createComment(
            'wiki page',
            static::$defaultComments['wiki page']['objectId'],
            'Approve Test Comment 3',
            'This comment will be approved twice.'
        );
        // Ensure the comment is approved
        $commentslib = TikiLib::lib('comments');
        $commentslib->approve_comment($threadId);

        $response = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId . '/approve',
            'Admins',
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Comment already approved.');
    }

    public function testApiRejectCommentWithoutPermission()
    {
        $threadId = static::createComment(
            'wiki page',
            static::$defaultComments['wiki page']['objectId'],
            'Reject Test Comment',
            'This comment will be rejected.'
        );
        $response = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId . '/reject',
            null, // Anonymous user
        );
        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied.');
    }

    public function testApiRejectComment()
    {
        $threadId = static::createComment(
            'wiki page',
            static::$defaultComments['wiki page']['objectId'],
            'Reject Test Comment 2',
            'This comment will be rejected.'
        );
        $response = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId . '/reject',
            'Admins',
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidModerateCommentResponse($body);
        $this->assertTrue($body['status'] == 'DONE', 'Status should be DONE after rejecting the comment');
        $this->assertTrue($body['do'] == 'reject', 'Do should be REJECT after rejecting the comment');
        $this->assertEquals($threadId, $body['threadId'], 'threadId should match the rejected comment');
    }

    public function testApiRejectNonExistentComment()
    {
        $response = $this->makeApiRequest(
            'POST',
            '/comments/99999/reject',
            'Admins',
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Comment not found. (404)');
    }

    public function testApiRejectAlreadyRejectedComment()
    {
        $threadId = static::createComment(
            'wiki page',
            static::$defaultComments['wiki page']['objectId'],
            'Reject Test Comment 3',
            'This comment will be rejected twice.'
        );
        // Ensure the comment is rejected
        $commentslib = TikiLib::lib('comments');
        $commentslib->reject_comment($threadId);

        $response = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId . '/reject',
            'Admins',
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Comment already rejected.');
    }

    public function testApiArchiveCommentWithoutPermission()
    {
        $threadId = static::createComment(
            'wiki page',
            static::$defaultComments['wiki page']['objectId'],
            'Archive Test Comment',
            'This comment will be archived.'
        );
        $response = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId . '/archive',
            null, // Anonymous user
        );
        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied.');
    }

    public function testApiArchiveComment()
    {
        $threadId = static::createComment(
            'wiki page',
            static::$defaultComments['wiki page']['objectId'],
            'Archive Test Comment 2',
            'This comment will be archived.'
        );
        $response = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId . '/archive',
            'Admins',
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidModerateCommentResponse($body);
        $this->assertTrue($body['status'] == 'DONE', 'Status should be DONE after archiving the comment');
        $this->assertTrue($body['do'] == 'archive', 'Do should be ARCHIVE after archiving the comment');
        $this->assertEquals($threadId, $body['threadId'], 'threadId should match the archived comment');
    }

    public function testApiArchiveNonExistentComment()
    {
        $response = $this->makeApiRequest(
            'POST',
            '/comments/99999/archive',
            'Admins',
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Comment not found. (404)');
    }

    public function testApiArchiveAlreadyArchivedComment()
    {
        $threadId = static::createComment(
            'wiki page',
            static::$defaultComments['wiki page']['objectId'],
            'Archive Test Comment 3',
            'This comment will be archived twice.'
        );
        // First archive the comment
        $commentslib = TikiLib::lib('comments');
        $commentslib->archive_thread($threadId);

        $response = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId . '/archive',
            'Admins',
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Comment already archived.');
    }

    public function testApiUnarchiveCommentWithoutPermission()
    {
        $threadId = static::createComment(
            'wiki page',
            static::$defaultComments['wiki page']['objectId'],
            'Unarchive Test Comment',
            'This comment will be unarchived.'
        );
        // First archive the comment
        $commentslib = TikiLib::lib('comments');
        $commentslib->archive_thread($threadId);
        $response = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId . '/unarchive',
            null, // Anonymous user
        );
        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied.');
    }

    public function testApiUnarchiveComment()
    {
        $threadId = static::createComment(
            'wiki page',
            static::$defaultComments['wiki page']['objectId'],
            'Unarchive Test Comment 2',
            'This comment will be unarchived.'
        );
        // First archive the comment
        $commentslib = TikiLib::lib('comments');
        $commentslib->archive_thread($threadId);

        $response = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId . '/unarchive',
            'Admins',
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidModerateCommentResponse($body);
        $this->assertTrue($body['status'] == 'DONE', 'Status should be DONE after unarchiving the comment');
        $this->assertTrue($body['do'] == 'unarchive', 'Do should be UNARCHIVE after unarchiving the comment');
        $this->assertEquals($threadId, $body['threadId'], 'threadId should match the unarchived comment');
    }

    public function testApiUnarchiveNonExistentComment()
    {
        $response = $this->makeApiRequest(
            'POST',
            '/comments/99999/unarchive',
            'Admins',
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Comment not found. (404)');
    }

    public function testApiUnarchiveAlreadyUnarchivedComment()
    {
        $threadId = static::createComment(
            'wiki page',
            static::$defaultComments['wiki page']['objectId'],
            'Unarchive Test Comment 3',
            'This comment will be unarchived twice.'
        );
        // Ensure the comment is unarchived
        $commentslib = TikiLib::lib('comments');
        $commentslib->unarchive_thread($threadId);

        $response = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId . '/unarchive',
            'Admins',
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Comment already unarchived.');
    }
}
