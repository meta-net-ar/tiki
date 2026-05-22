<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Users;

use TikiLib;

/**
 * Tests for Users API POST endpoints
 *
 * Covers:
 *   POST /users          — register a new user
 *   POST /users/delete   — remove one or more users
 *   POST /users/groups   — add / remove users from groups
 *   POST /users/send-message — send an internal message to a user
 *
 * @group api-integration-test
 * @group api-users
 */
class ApiUsersPostTest extends ApiBaseUsersTest
{
    public function testApiRegisterUser()
    {
        $uid      = uniqid();
        $username = 'api_reg_user_' . $uid;
        $email    = $username . '@api-test.tiki.org';

        // disable registration preferences that would interfere with testing
        static::setTestPreferences('useRegisterPasscode', 'n');
        static::setTestPreferences('validateRegistration', 'n');

        // Register the user so it gets cleaned up
        static::$testUsers[] = $username;

        $response = $this->makeApiRequest('POST', '/users', 'Admins', [
            'name'      => $username,
            'pass'      => 'TestPass123!',
            'passAgain' => 'TestPass123!',
            'email'     => $email,
        ], 'application/x-www-form-urlencoded');

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidRegisterUserResponse($body);

        // Verify the user was actually created in the database
        $userlib = TikiLib::lib('user');
        $this->assertEquals(1, $userlib->user_exists($username), 'Registered user should exist in the database');
    }

    public function testApiRegisterUserPasswordMismatch()
    {
        $uid      = uniqid();
        $username = 'api_mismatch_' . $uid;

        $response = $this->makeApiRequest('POST', '/users', 'Admins', [
            'name'      => $username,
            'pass'      => 'TestPass123!',
            'passAgain' => 'DifferentPass456!',
            'email'     => $username . '@api-test.tiki.org',
        ], 'application/x-www-form-urlencoded');

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 422, 'Passwords do not match');
    }

    public function testApiRegisterUserWithExistingUsername()
    {
        // Attempt to register using a username that is already taken
        $username = static::$defaultTestUser;
        $this->assertNotNull($username, 'Default test user must exist');

        $response = $this->makeApiRequest('POST', '/users', 'Admins', [
            'name'      => $username,
            'pass'      => 'TestPass123!',
            'passAgain' => 'TestPass123!',
            'email'     => 'other@api-test.tiki.org',
        ], 'application/x-www-form-urlencoded');

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 422, 'User already exists');
    }

    public function testApiRegisterUserWithoutCredentials()
    {
        // Missing name, pass, and passAgain — registration validation should fail
        $response = $this->makeApiRequest('POST', '/users', 'Admins', [
            'email' => 'nobody@api-test.tiki.org',
        ], 'application/x-www-form-urlencoded');

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 422, 'required');
    }

    public function testApiDeleteUserWithoutPermission()
    {
        $username = static::$defaultTestUser;
        $this->assertNotNull($username, 'Default test user must exist');

        $response = $this->makeApiRequest('POST', '/users/delete', null, [
            'items' => [$username],
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');

        // Verify the user still exists
        $userlib = TikiLib::lib('user');
        $this->assertEquals(1, $userlib->user_exists($username), 'User should still exist after failed delete attempt');
    }

    public function testApiDeleteUser()
    {
        $uid      = uniqid();
        $username = 'api_del_user_' . $uid;
        static::createUser($username);

        $response = $this->makeApiRequest('POST', '/users/delete', 'Admins', [
            'items' => [$username],
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidDeleteUserResponse($body);
        $this->assertContains($username, $body['feedback']['items'], 'Deleted username should appear in feedback items');

        // Verify the user was actually removed
        $userlib = TikiLib::lib('user');
        $this->assertEquals(0, $userlib->user_exists($username), 'User should no longer exist after deletion');

        // Remove from cleanup list since the API already deleted it
        $key = array_search($username, static::$testUsers);
        if ($key !== false) {
            unset(static::$testUsers[$key]);
        }
    }

    public function testApiDeleteMultipleUsers()
    {
        $uid   = uniqid();
        $user1 = 'api_del_multi1_' . $uid;
        $user2 = 'api_del_multi2_' . $uid;
        static::createUser($user1);
        static::createUser($user2);

        $response = $this->makeApiRequest('POST', '/users/delete', 'Admins', [
            'items' => [$user1, $user2],
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidDeleteUserResponse($body);
        $this->assertContains($user1, $body['feedback']['items'], 'First deleted username should appear in feedback items');
        $this->assertContains($user2, $body['feedback']['items'], 'Second deleted username should appear in feedback items');

        $userlib = TikiLib::lib('user');
        $this->assertEquals(0, $userlib->user_exists($user1), 'First user should be deleted');
        $this->assertEquals(0, $userlib->user_exists($user2), 'Second user should be deleted');

        foreach ([$user1, $user2] as $u) {
            $key = array_search($u, static::$testUsers);
            if ($key !== false) {
                unset(static::$testUsers[$key]);
            }
        }
    }

    public function testApiManageGroupsWithoutPermission()
    {
        $username  = static::$defaultTestUser;
        $groupName = static::$defaultTestGroup;
        $this->assertNotNull($username, 'Default test user must exist');
        $this->assertNotNull($groupName, 'Default test group must exist');

        $response = $this->makeApiRequest('POST', '/users/groups', null, [
            'items'          => [$username],
            'add_remove'     => 'add',
            'checked_groups' => [$groupName],
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiManageGroupsAddUserToGroup()
    {
        $uid       = uniqid();
        $username  = 'api_grp_add_' . $uid;
        $groupName = static::$defaultTestGroup;
        $this->assertNotNull($groupName, 'Default test group must exist');

        static::createUser($username);

        $response = $this->makeApiRequest('POST', '/users/groups', 'Admins', [
            'items'          => [$username],
            'add_remove'     => 'add',
            'checked_groups' => [$groupName],
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidManageGroupsResponse($body);
        $this->assertEquals('The following user:', $body['feedback']['mes'], 'Feedback message should indicate the user being modified');
        $this->assertEquals('Has been added to the following group:', $body['feedback']['toMsg'], 'Feedback message should indicate the group being added to');
        $this->assertContains($groupName, $body['feedback']['toList'], 'Feedback toList should contain the group name');

        // Verify the user is now a member of the group
        $userlib = TikiLib::lib('user');

        // Clear any cached groups for this user before querying
        // The API subprocess invalidates cache in its own process, but the main test process
        // has a separate cache that needs to be cleared as well
        $userlib->invalidate_usergroups_cache($username);
        TikiLib::lib('tiki')->invalidate_usergroups_cache($username);

        $groups  = $userlib->get_user_groups($username);
        $this->assertContains($groupName, $groups, 'User should be a member of the group after add operation');
    }

    public function testApiManageGroupsRemoveUserFromGroup()
    {
        $uid       = uniqid();
        $username  = 'api_grp_rm_' . $uid;
        $groupName = static::$defaultTestGroup;
        $this->assertNotNull($groupName, 'Default test group must exist');

        static::createUser($username);

        // Pre-assign the user to the group directly
        $userlib = TikiLib::lib('user');
        $userlib->assign_user_to_group($username, $groupName);

        $response = $this->makeApiRequest('POST', '/users/groups', 'Admins', [
            'items'          => [$username],
            'add_remove'     => 'remove',
            'checked_groups' => [$groupName],
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidManageGroupsResponse($body);
        $this->assertEquals('The following user:', $body['feedback']['mes'], 'Feedback message should indicate the user being modified');
        $this->assertEquals('Has been removed from the following group:', $body['feedback']['toMsg'], 'Feedback message should indicate the group being removed from');
        $this->assertContains($groupName, $body['feedback']['toList'], 'Feedback toList should contain the group name');

        // Clear any cached groups for this user before querying
        // The API subprocess invalidates cache in its own process, but the main test process
        // has a separate cache that needs to be cleared as well
        $userlib->invalidate_usergroups_cache($username);
        TikiLib::lib('tiki')->invalidate_usergroups_cache($username);

        $groups = $userlib->get_user_groups($username);
        $this->assertNotContains($groupName, $groups, 'User should no longer be in the group after remove operation');
    }

    public function testApiManageGroupsWithNoGroupSelected()
    {
        $username = static::$defaultTestUser;
        $this->assertNotNull($username, 'Default test user must exist');

        $response = $this->makeApiRequest('POST', '/users/groups', 'Admins', [
            'items'      => [$username],
            'add_remove' => 'add',
            // no checked_groups provided
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, null, 'No groups were selected');
    }

    public function testApiSendMessageWithoutRecipient()
    {
        $response = $this->makeApiRequest('POST', '/users/send-message', 'Admins', [
            'subject' => 'Hello',
            'body'    => 'Test message body',
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiSendMessageToNonExistentUser()
    {
        $response = $this->makeApiRequest('POST', '/users/send-message', 'Admins', [
            'to'      => 'nonexistent_recipient_' . uniqid(),
            'subject' => 'Hello',
            'body'    => 'Test message body',
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'The selected user does not exist.');
    }

    public function testApiSendMessageToValidUser()
    {
        $username = static::$defaultTestUser;
        $this->assertNotNull($username, 'Default test user must exist');

        $response = $this->makeApiRequest('POST', '/users/send-message', 'Admins', [
            'to'      => $username,
            'subject' => 'API Test Message',
            'body'    => 'This message was sent by an API integration test.',
        ]);

        $body = $this->getResponseBody($response);
        $this->assertEquals("Your message was successfully sent to $username,", $body['feedback'], 'Success feedback should confirm the recipient username');
    }
}
