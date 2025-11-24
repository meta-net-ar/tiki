<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once(dirname(__FILE__) . '/ApiBaseCommentsTest.php');

/**
 * Integration tests for Comments API POST endpoints
 *
 * @group api-integration-test
 * @group api-comments
 */
class ApiCommentsPostTest extends ApiBaseCommentsTest
{
    public function testUpdateCommentWithoutPermission()
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

    public function testCreateCommentWithoutPermission()
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

    public function testCreateCommentOnWikiPage()
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

    public function testCreateReplyComment()
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

    public function testCreateCommentOnDifferentObjectTypes()
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

    public function testCreateCommentWithoutRequiredFields()
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

    public function testUpdateComment()
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

    public function testUpdateNonExistentComment()
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
}
