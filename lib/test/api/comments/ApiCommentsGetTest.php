<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Comments;

/**
 * Integration tests for Comments API GET endpoints
 *
 * @group api-integration-test
 * @group api-comments
 */
class ApiCommentsGetTest extends ApiBaseCommentsTest
{
    public function testApiGetCommentsOnWikiPageWithoutPermission()
    {
        // Use the default wiki page created in setup
        $pageName = static::$defaultComments['wiki page']['objectId'];
        $data = [
            'type' => 'wiki page',
            'objectId' => $pageName,
        ];

        $response = $this->makeApiRequest(
            'GET',
            '/comments',
            null, // user without view permission
            $data,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'You do not have permission to view comments.');
    }

    public function testApiGetCommentsOnWikiPage()
    {
        // Use the default wiki page created in setup
        $pageName = static::$defaultComments['wiki page']['objectId'];
        $data = [
            'type' => 'wiki page',
            'objectId' => $pageName,
        ];

        $response = $this->makeApiRequest(
            'GET',
            '/comments',
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidCommentListResponse($body);

        $this->assertEquals('wiki page', $body['type']);
        $this->assertEquals($pageName, $body['objectId']);
        $this->assertGreaterThanOrEqual(1, $body['count'], 'Should have at least the default comment');
    }

    public function testApiGetCommentsWithPagination()
    {
        // Create a wiki page with multiple comments
        $pageName = 'GET_PaginationTestPage';
        static::createWikiPage($pageName);

        // Create 5 comments
        for ($i = 1; $i <= 5; $i++) {
            $threadId = static::createComment('wiki page', $pageName, "Test Comment $i", "Comment content $i");
            // add reply to each comment to test threading in the response
            static::createComment('wiki page', $pageName, "Reply to Comment $i", "Reply content $i", $threadId);
        }

        $data1 = [
            'type' => 'wiki page',
            'objectId' => $pageName,
            'offset' => 0,
            'maxRecords' => 2,
        ];
        // Test pagination - first page
        $response1 = $this->makeApiRequest(
            'GET',
            '/comments',
            'Admins',
            $data1,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response1);
        $body1 = $this->getResponseBody($response1);
        $this->assertValidCommentListResponse($body1);

        $this->assertLessThanOrEqual(2, count($body1['comments']), 'First page should have at most 2 comments');

        $data2 = [
            'type' => 'wiki page',
            'objectId' => $pageName,
            'offset' => 2,
            'maxRecords' => 2,
        ];
        // Test pagination - second page
        $response2 = $this->makeApiRequest(
            'GET',
            '/comments',
            'Admins',
            $data2,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response2);
        $body2 = $this->getResponseBody($response2);
        $this->assertValidCommentListResponse($body2);

        $this->assertLessThanOrEqual(2, count($body2['comments']), 'Second page should have at most 2 comments');
    }

    public function testApiGetCommentsOnDifferentObjectTypes()
    {
        $objectTypes = [
            'wiki page',
            'file gallery',
            'poll',
            'faq',
            'blog post',
            'trackeritem',
            'article',
        ];

        foreach ($objectTypes as $type) {
            if (! isset(static::$defaultComments[$type])) {
                $this->markTestSkipped("Default comment for $type not available");
                continue;
            }

            $objectId = static::$defaultComments[$type]['objectId'];

            $data = [
                'type' => $type,
                'objectId' => $objectId,
            ];
            $response = $this->makeApiRequest(
                'GET',
                '/comments',
                'Admins',
                $data,
                'application/x-www-form-urlencoded'
            );

            $this->assertResponseStatus(200, $response, "Get comments for $type");

            $body = $this->getResponseBody($response);
            $this->assertValidCommentListResponse($body);
            $this->assertEquals($type, $body['type']);
            $this->assertGreaterThanOrEqual(1, $body['count'], "Should have at least one comment for $type");
        }
    }

    public function testApiGetCommentsWithoutRequiredParameters()
    {
        $data = [
            'type' => 'wiki page',
        ];
        // Missing objectId
        $response = $this->makeApiRequest(
            'GET',
            '/comments',
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Invalid wiki page ID: ');
    }

    public function testApiGetCommentsForNonExistentObject()
    {
        $data = [
            'type' => 'wiki page',
            'objectId' => 'NonExistentPage999',
        ];
        $response = $this->makeApiRequest(
            'GET',
            '/comments',
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Invalid wiki page ID: NonExistentPage999');
    }

    public function testApiGetCommentsWithThreading()
    {
        // Create a wiki page
        $pageName = 'GET_ThreadingTestPage';
        static::createWikiPage($pageName);

        // Create parent comment
        $parentThreadId = static::createComment('wiki page', $pageName, 'Parent Comment', 'This is the parent comment');
        // create reply comment
        $replyThreadId = static::createComment('wiki page', $pageName, 'Reply Comment', 'This is a reply', $parentThreadId);

        // Get comments
        $data = [
            'type' => 'wiki page',
            'objectId' => $pageName,
        ];
        $response = $this->makeApiRequest(
            'GET',
            '/comments',
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidCommentListResponse($body, 'Get threaded comments');

        $this->assertEquals($parentThreadId, $body['comments'][0]['threadId'], 'Parent comment should have correct threadId');
        $this->assertEquals(0, $body['comments'][0]['parentId'], 'Parent comment should have parentId 0');
        $this->assertEquals($pageName, $body['comments'][0]['object'], 'Parent comment should have correct object');

        $this->assertEquals($replyThreadId, $body['comments'][0]['replies_info']['replies'][0]['threadId'], 'Reply should have correct threadId');
        $this->assertEquals($parentThreadId, $body['comments'][0]['replies_info']['replies'][0]['parentId'], 'Reply should have correct parentId');
        $this->assertEquals($pageName, $body['comments'][0]['replies_info']['replies'][0]['object'], 'Reply should have correct object');
    }
}
