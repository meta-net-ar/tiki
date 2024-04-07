<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class CommentsTest extends TikiTestCase
{
    public function testPostCommentToObject(): void
    {
        $this->cleanDatabaseTables(['tiki_user_postings', 'tiki_comments']);

        $comment = [
            'type' => 'file gallery',
            'objectId' => 1,
            'title' => 'Comment One',
            'data' => 'This is a test comment One',
        ];

        $tikilib = TikiLib::lib('filegal');
        $tikilib->set_preference('feature_file_galleries_comments', 'y');

        $response = $this->makeHttpRequest('POST', '/comments', 'Admins', $comment);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getBody(), true);

        $this->assertNotEmpty($responseData);
        $this->assertCommentResponseHasKeys([$responseData]);

        $this->assertEquals(1, $responseData['threadId']);
        $this->assertNull($responseData['parentId']);
        $this->assertEquals($comment['type'], $responseData['type']);
        $this->assertEquals($comment['objectId'], $responseData['objectId']);
        $this->assertIsArray($responseData['feedback']);
        $this->assertEmpty($responseData['feedback']);
    }

    public function testPostCommentWithInsufficientPermissions(): void
    {
        $comment = [
            'type' => 'file gallery',
            'objectId' => 1,
            'title' => 'Comment One',
            'data' => 'This is a test comment One',
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Permission denied');

        $response = $this->makeHttpRequest('POST', '/comments', 'Anonymous', $comment);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPostCommentWithInvalidObjectId(): void
    {
        $comment = [
            'type' => 'file gallery',
            'objectId' => 0,
            'title' => 'Comment One',
            'data' => 'This is a test comment One',
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid file gallery ID: 0');

        $response = $this->makeHttpRequest('POST', '/comments', 'Admins', $comment);
        $this->assertEquals(403, $response->getStatusCode());
    }


    public function testGetCommentsByObjectId(): void
    {
        $this->cleanDatabaseTables(['tiki_user_postings', 'tiki_comments']);

        $comment = [
            'type' => 'file gallery',
            'objectId' => 1,
            'title' => 'Comment One',
            'data' => 'This is a test comment One',
        ];

        $response = $this->makeHttpRequest('POST', '/comments', 'Admins', $comment);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getBody(), true);

        $this->assertNotEmpty($responseData);
        $this->assertCommentResponseHasKeys([$responseData]);

        $response = $this->makeHttpRequest('GET', '/comments', 'Admins', ['objectId' => 1, 'type' => 'file gallery']);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getBody(), true);

        $this->assertNotEmpty($responseData);
        $this->assertCommentResponseHasKeys($responseData);
    }

    public function testPostCommentOnNotAllowedObject(): void
    {
        $comment = [
            'type' => 'file gallery',
            'objectId' => 1,
            'title' => 'Comment One',
            'data' => 'This is a test comment One',
        ];

        $tikilib = TikiLib::lib('filegal');
        $tikilib->set_preference('feature_file_galleries_comments', 'n');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Comments not allowed on this page.');

        $response = $this->makeHttpRequest('POST', '/comments', 'Admins', $comment);
        $this->assertEquals(403, $response->getStatusCode());
    }


    // Helper function to assert comments response keys
    private function assertCommentResponseHasKeys(array $responseData, array $extraKeys = []): void
    {
        $expectedKeys = [
            'threadId',
            'parentId',
            'type',
            'objectId',
            'feedback',
        ];
        if (! empty($extraKeys)) {
            $expectedKeys = array_merge($expectedKeys, $extraKeys);
        }

        foreach ($responseData as $comment) {
            if (is_array($comment)) {
                foreach ($expectedKeys as $key) {
                    $this->assertArrayHasKey($key, $comment, "Key $key not found in response data");
                }
            }
        }
    }
}