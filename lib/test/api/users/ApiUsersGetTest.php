<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Users;

/**
 * Tests for Users API GET endpoints
 *
 * Covers:
 *   GET /users
 *   GET /users/{username}
 *   GET /message-count
 *
 * @group api-integration-test
 * @group api-users
 */
class ApiUsersGetTest extends ApiBaseUsersTest
{
    public function testApiListUsers()
    {
        $response = $this->makeApiRequest('GET', '/users', 'Admins');

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidUserListResponse($body);

        $logins = array_column($body['result'], 'login');
        $this->assertContains('admin', $logins, 'admin user should appear in the users list');
        $this->assertContains(static::$defaultTestUser, $logins, 'Newly created test user should appear in the users list');
    }

    public function testApiListUsersAsAnonymous()
    {
        $response = $this->makeApiRequest('GET', '/users', null);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertArrayHasKey('result', $body, 'Response should always have a result key');
        $this->assertArrayHasKey('count', $body, 'Response should always have a count key');
        $this->assertNull($body['result'], 'result should be null for anonymous users');
        $this->assertNull($body['count'], 'count should be null for anonymous users');
    }

    public function testApiGetUserInfo()
    {
        $username = static::$defaultTestUser;
        $this->assertNotNull($username, 'Default test user must exist');

        $response = $this->makeApiRequest('GET', "/users/{$username}", 'Admins');

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidUserInfoResponse($body);
        $this->assertEquals($username, $body['other_user'], 'Response should reflect the requested username');
    }

    public function testApiGetUserInfoNonExistent()
    {
        $response = $this->makeApiRequest('GET', '/users/this_user_does_not_exist_' . uniqid(), 'Admins');

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'User does not exist. (404)');
    }

    public function testApiGetUserInfoAsAnonymous()
    {
        $username = static::$defaultTestUser;
        $this->assertNotNull($username, 'Default test user must exist');

        $response = $this->makeApiRequest('GET', "/users/{$username}", null);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidUserInfoResponse($body);
        $this->assertEquals($username, $body['other_user'], 'Response should reflect the requested username');
    }

    public function testApiGetMessageCount()
    {
        $response = $this->makeApiRequest('GET', '/message-count', 'Admins');

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidMessageCountResponse($body);
    }

    public function testApiGetMessageCountUnreadOnly()
    {
        $response = $this->makeApiRequest('GET', '/message-count', 'Admins', [
            'unread' => '1',
        ], 'application/x-www-form-urlencoded');

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidMessageCountResponse($body);
    }

    public function testApiGetMessageCountAsAnonymous()
    {
        $response = $this->makeApiRequest('GET', '/message-count', null);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidMessageCountResponse($body);
        $this->assertEquals(0, $body['count'], 'Anonymous user should have 0 messages');
    }
}
