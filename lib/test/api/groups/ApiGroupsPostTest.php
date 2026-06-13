<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Groups;

use TikiLib;

/**
 * Tests for Groups API POST endpoints
 *
 * @group api-integration-test
 * @group api-groups
 */
class ApiGroupsPostTest extends ApiBaseGroupsTest
{
    public function testApiCreateGroupWithoutPermissions()
    {
        $groupName = 'API_Test_Group_POST_NoPerms';
        $data = [
            'name' => $groupName,
            'desc' => 'Test group created via POST without permissions',
        ];
        $response = $this->makeApiRequest(
            'POST',
            '/groups',
            null, // Anonymous user
            $data,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');

        // Verify group was not created
        $this->assertFalse($this->groupExists($groupName), 'Group should not exist after creation');
    }

    public function testApiCreateGroup()
    {
        $groupName = 'API_Test_Group_POST_Create';
        $data = [
            'name' => $groupName,
            'desc' => 'Test group created via POST',
        ];
        $response = $this->makeApiRequest('POST', '/groups', 'Admins', $data, 'application/x-www-form-urlencoded');

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidGroupCreateUpdateResponse($body);

        // Verify group was created
        $this->assertTrue($this->groupExists($groupName), 'Group should exist after creation');

        // Track for cleanup
        static::$testGroups[] = $groupName;

        // Verify group details
        $userlib = TikiLib::lib('user');
        $info = $userlib->get_group_info($groupName);
        $this->assertEquals($groupName, $info['groupName'], 'Group name should match');
        $this->assertEquals('Test group created via POST', $info['groupDesc'], 'Group description should match');
        $this->assertEquals('Success', $body['feedback']['action'][0]['title'], 'Title should indicate success');
        $this->assertEquals(["Group {$groupName} (ID {$info['id']}) successfully created"], $body['feedback']['action'][0]['mes'], 'Message should indicate successful creation');
    }

    public function testApiCreateGroupDuplicate()
    {
        $groupName = 'API_Test_Group_Duplicate';
        // Create first group
        $this->createGroup($groupName, 'First group');

        // Try to create duplicate
        $data = [
            'name' => $groupName,
            'desc' => 'Duplicate group',
        ];
        $response = $this->makeApiRequest('POST', '/groups', 'Admins', $data, 'application/x-www-form-urlencoded');

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidGroupCreateUpdateResponse($body);
        $this->assertEquals('Error', $body['feedback']['action'][0]['title'], 'Title should indicate Error');
        $this->assertEquals(["Group {$groupName} already exists"], $body['feedback']['action'][0]['mes'], 'Message should indicate duplicate group');
    }

    public function testApiCreateGroupWithoutName()
    {
        $response = $this->makeApiRequest('POST', '/groups', 'Admins', [
            'desc' => 'Group without name',
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidGroupCreateUpdateResponse($body);
        $this->assertEquals('Error', $body['feedback']['action'][0]['title'], 'Title should indicate Error');
        $this->assertEquals(["Group name cannot be empty"], $body['feedback']['action'][0]['mes'], 'Message should indicate missing name');
    }

    public function testApiUpdateGroupWithoutPermissions()
    {
        $uid     = uniqid();
        $oldName = 'API_Test_Group_POST_Update_NoPerms_Old_' . $uid;
        $newName = 'API_Test_Group_POST_Update_NoPerms_New_' . $uid;
        $this->createGroup($oldName, 'Group to update without permissions');

        $data = [
            'name' => $newName,
            'desc' => 'Updated group',
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/groups/{$oldName}",
            null, // Anonymous user
            $data,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');

        // Verify old name still exists
        $this->assertTrue($this->groupExists($oldName), 'Old group name should still exist');

        // Verify new name does not exist
        $this->assertFalse($this->groupExists($newName), 'New group name should not exist');
    }

    public function testApiUpdateGroupName()
    {
        $uid     = uniqid();
        $oldName = 'API_Test_Group_POST_Update_Old_' . $uid;
        $newName = 'API_Test_Group_POST_Update_New_' . $uid;
        $this->createGroup($oldName, 'Group to update');

        $data = [
            'name' => $newName,
            'desc' => 'Updated group',
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/groups/{$oldName}",
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidGroupCreateUpdateResponse($body);

        // Verify old name doesn't exist
        $this->assertFalse($this->groupExists($oldName), 'Old group name should not exist');

        // Verify new name exists
        $this->assertTrue($this->groupExists($newName), 'New group name should exist');

        // Track for cleanup
        static::$testGroups[] = $newName;

        $userlib = TikiLib::lib('user');
        $info = $userlib->get_group_info($newName);
        $this->assertEquals('Updated group', $info['groupDesc'], 'Group description should be updated');
        $this->assertEquals('Success', $body['feedback']['action'][0]['title'], 'Title should indicate success');
        $this->assertEquals(["Group {$newName} successfully modified"], $body['feedback']['action'][0]['mes'], 'Message should indicate successful update');
    }

    public function testApiUpdateGroupWithoutName()
    {
        $groupName = 'API_Test_Group_POST_Update_NoName';
        $this->createGroup($groupName, 'Group to update without name');
        $response = $this->makeApiRequest(
            'POST',
            "/groups/{$groupName}",
            'Admins',
            ['desc' => 'Updated description without name'],
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidGroupCreateUpdateResponse($body);
        $this->assertTrue($this->groupExists($groupName), 'Group name should still exist');
    }

    public function testApiAddUsersToGroupWithoutPermissions()
    {
        $groupName = 'API_Test_Group_POST_AddUsers_NoPerms';
        $username = 'api_test_user_no_perms';

        // Create group and user
        $this->createGroup($groupName, 'Group for adding users without permissions');
        $this->createUser($username);

        $data = [
            'group' => $groupName,
            'items' => [$username],
        ];
        $response = $this->makeApiRequest(
            'POST',
            '/groups/add_users',
            null, // Anonymous user
            $data,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');

        // Verify user is not in group
        $users = $this->getGroupUsers($groupName);
        $this->assertNotContains($username, $users, 'User should not be in group');
    }

    public function testApiAddUsersToGroup()
    {
        $groupName = 'API_Test_Group_POST_AddUsers';
        $username = 'api_test_user_add';

        // Create group and user
        $this->createGroup($groupName, 'Group for adding users');
        $this->createUser($username);

        $data = [
            'group' => $groupName,
            'items' => [$username],
        ];
        $response = $this->makeApiRequest(
            'POST',
            '/groups/add_users',
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidGroupAddBanDelUsersResponse($body);

        // Verify user is in group
        $users = $this->getGroupUsers($groupName);
        $this->assertContains($username, $users, 'User should be in group');
        $this->assertEquals('Success', $body['feedback']['action'][0]['title'], 'Title should indicate success');
        $this->assertEquals(["The following user was added to group {$groupName}:"], $body['feedback']['action'][0]['mes'], 'Message should indicate successful addition');
        $this->assertEquals([$username], $body['feedback']['action'][0]['items'], 'Items should list added user');
    }

    public function testApiBanUsersFromGroupWithoutPermissions()
    {
        $groupName = 'API_Test_Group_POST_BanUsers_NoPerms';
        $username = 'api_test_user_ban_no_perms';

        $this->createGroup($groupName, 'Group for banning users without permissions');
        $this->createUser($username);

        $data = [
            'group' => $groupName,
            'items' => [$username],
        ];
        $response = $this->makeApiRequest(
            'POST',
            '/groups/ban_users',
            null, // Anonymous user
            $data,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');

        // Verify user is not banned from group
        $userlib = TikiLib::lib('user');
        $isBanned = $userlib->is_user_banned_from_group($username, $groupName);
        $this->assertFalse($isBanned, 'User should not be banned from group');
    }

    public function testApiBanUsersFromGroup()
    {
        $groupName = 'API_Test_Group_POST_BanUsers';
        $username = 'api_test_user_ban';

        $this->createGroup($groupName, 'Group for banning users');
        $this->createUser($username);

        $data = [
            'group' => $groupName,
            'items' => [$username],
        ];
        $response = $this->makeApiRequest(
            'POST',
            '/groups/ban_users',
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidGroupAddBanDelUsersResponse($body);

        // Verify user is banned from group
        $userlib = TikiLib::lib('user');
        $isBanned = $userlib->is_user_banned_from_group($username, $groupName);
        $this->assertTrue($isBanned, 'User should be banned from group');

        $this->assertEquals('Success', $body['feedback']['action'][0]['title'], 'Title should indicate success');
        $this->assertEquals(["The following user was banned from group {$groupName}:"], $body['feedback']['action'][0]['mes'], 'Message should indicate successful ban');
        $this->assertEquals([$username], $body['feedback']['action'][0]['items'], 'Items should list banned user');
    }
}
