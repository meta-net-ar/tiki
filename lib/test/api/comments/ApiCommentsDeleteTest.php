<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Comments;

/**
 * Integration tests for Comments API DELETE endpoint
 *
 * @group api-integration-test
 * @group api-comments
 */
class ApiCommentsDeleteTest extends ApiBaseCommentsTest
{
    public function testApiDeleteCommentWithoutReplies()
    {
        $pageName = static::$defaultComments['wiki page']['objectId'];
        $commentData = [
            'type' => 'wiki page',
            'objectId' => $pageName,
            'title' => 'Comment to Delete',
            'data' => 'This comment will be deleted',
        ];

        $createResponse = $this->makeApiRequest(
            'POST',
            '/comments',
            'Admins',
            $commentData,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($createResponse);
        $threadId = $body['threadId'];

        // Delete the comment
        $response = $this->makeApiRequest(
            'DELETE',
            '/comments/' . $threadId,
            'Admins'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidDeleteCommentResponse($body);

        // Verify comment is deleted by trying to update it
        $data = [
            'title' => 'Updated Title',
            'data' => 'Updated data',
        ];
        $getResponse = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId,
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($getResponse);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiDeleteCommentWithReplies()
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

        $body = $this->getResponseBody($parentResponse);
        $parentThreadId = $body['threadId'];

        // Create reply comment
        $replyData = [
            'type' => 'wiki page',
            'objectId' => $pageName,
            'parentId' => $parentThreadId,
            'title' => 'Reply Comment',
            'data' => 'This is a reply',
        ];

        $replyResponse = $this->makeApiRequest(
            'POST',
            '/comments',
            'Admins',
            $replyData,
            'application/x-www-form-urlencoded'
        );

        $bodyReply = $this->getResponseBody($replyResponse);
        $replyThreadId = $bodyReply['threadId'];

        // Delete parent comment
        $response = $this->makeApiRequest(
            'DELETE',
            '/comments/' . $parentThreadId,
            'Admins'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidDeleteCommentResponse($body);

        // verify reply is also deleted by trying to update it
        $data = [
            'title' => 'Updated Reply Title',
            'data' => 'Updated reply data',
        ];
        $getResponse = $this->makeApiRequest(
            'POST',
            '/comments/' . $replyThreadId,
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );
        $body = $this->getResponseBody($getResponse);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiDeleteNonExistentComment()
    {
        $response = $this->makeApiRequest(
            'DELETE',
            '/comments/99999',
            'Admins'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiDeleteCommentWithoutPermission()
    {
        $pageName = static::$defaultComments['wiki page']['objectId'];

        $commentData = [
            'type' => 'wiki page',
            'objectId' => $pageName,
            'title' => 'Protected Comment',
            'data' => 'Cannot be deleted by anonymous',
        ];

        $createResponse = $this->makeApiRequest(
            'POST',
            '/comments',
            'Admins',
            $commentData,
        );

        $body = $this->getResponseBody($createResponse);
        $threadId = $body['threadId'];

        // Try to delete as anonymous
        $response = $this->makeApiRequest(
            'DELETE',
            '/comments/' . $threadId,
            null // Anonymous user
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');

        // Comment should still exist - verify by updating it
        $data = [
            'title' => 'Updated Title',
            'data' => 'Updated data',
        ];
        $getResponse = $this->makeApiRequest(
            'POST',
            '/comments/' . $threadId,
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );
        $this->assertResponseStatus(200, $getResponse);
        $body = $this->getResponseBody($getResponse);
        $this->assertValidUpdateCommentResponse($body);

        $this->assertEquals('Updated Title', $body['comment']['title']);
        $this->assertEquals('Updated data', $body['comment']['data']);

        // Track for cleanup
        static::$testComments[] = $threadId;
    }

    public function testApiDeleteCommentsOnDifferentObjectTypes()
    {
        $objectTypes = [
            ['type' => 'wiki page', 'createMethod' => 'createWikiPage', 'objectName' => 'DELETE_MultiTypeWikiPage'],
            ['type' => 'file gallery', 'createMethod' => 'createFileGallery', 'objectName' => 'DELETE_MultiTypeGallery'],
            ['type' => 'poll', 'createMethod' => 'createPoll', 'objectName' => 'DELETE_MultiTypePoll'],
            ['type' => 'faq', 'createMethod' => 'createFaq', 'objectName' => 'DELETE_MultiTypeFaq'],
            ['type' => 'blog post', 'createMethod' => 'createBlogPost', 'objectName' => 'DELETE_MultiTypeBlogPost'],
            ['type' => 'trackeritem', 'createMethod' => 'createTrackerItem', 'objectName' => 'DELETE_MultiTypeTracker',],
            ['type' => 'article', 'createMethod' => 'createArticle', 'objectName' => 'DELETE_MultiTypeArticle'],
        ];

        foreach ($objectTypes as $typeInfo) {
            $objectId = static::{$typeInfo['createMethod']}($typeInfo['objectName']);

            if (! $objectId) {
                $this->markTestSkipped("Could not create {$typeInfo['type']} object");
                continue;
            }

            // Create comment
            $commentData = [
                'type' => $typeInfo['type'],
                'objectId' => $objectId,
                'title' => "Comment on {$typeInfo['type']}",
                'data' => "Comment to be deleted",
            ];

            $createResponse = $this->makeApiRequest(
                'POST',
                '/comments',
                'Admins',
                $commentData,
                'application/x-www-form-urlencoded'
            );

            $body = $this->getResponseBody($createResponse);
            $threadId = $body['threadId'];

            // Delete comment
            $response = $this->makeApiRequest(
                'DELETE',
                '/comments/' . $threadId,
                'Admins'
            );

            $this->assertResponseStatus(200, $response, "Deleting comment on {$typeInfo['type']}");

            $body = $this->getResponseBody($response);
            $this->assertValidDeleteCommentResponse($body);
        }
    }
}
