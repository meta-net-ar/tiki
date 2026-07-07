<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Groups;

/**
 * Tests for Groups API DELETE endpoints
 *
 * @group api-integration-test
 * @group api-groups
 */
class ApiGroupsDeleteTest extends ApiBaseGroupsTest
{
    public function testApiDeleteGroupWithoutPermissions()
    {
        $groupName = 'API_Test_Group_DELETE_NoPerms';
        $this->createGroup($groupName, 'Group to delete without permissions');

        $this->assertTrue($this->groupExists($groupName), 'Group should exist before deletion attempt');

        $response = $this->makeApiRequest('POST', '/groups/delete', null, ['items' => [$groupName]]);
        $body = $this->getResponseBody($response);

        $this->assertValidErrorResponse($body, 403, 'Permission denied');
        $this->assertTrue($this->groupExists($groupName), 'Group should still exist after failed deletion attempt');
    }

    public function testApiDeleteGroup()
    {
        $groupName = 'API_Test_Group_DELETE_Single';
        $this->createGroup($groupName, 'Group to delete');

        $this->assertTrue($this->groupExists($groupName), 'Group should exist before deletion');

        $response = $this->makeApiRequest('POST', '/groups/delete', 'Admins', ['items' => [$groupName]]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidGroupAddBanDelUsersResponse($body);
        $this->assertFalse($this->groupExists($groupName), 'Group should not exist after deletion');
    }

    public function testApiDeleteMultipleGroups()
    {
        $group1 = 'API_Test_Group_DELETE_Multi1';
        $group2 = 'API_Test_Group_DELETE_Multi2';

        $this->createGroup($group1, 'First group to delete');
        $this->createGroup($group2, 'Second group to delete');

        $this->assertTrue($this->groupExists($group1), 'First group should exist');
        $this->assertTrue($this->groupExists($group2), 'Second group should exist');

        $data = [
            'items' => [$group1, $group2]
        ];
        $response = $this->makeApiRequest('POST', '/groups/delete', 'Admins', $data);
        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidGroupAddBanDelUsersResponse($body);
        $this->assertFalse($this->groupExists($group1), 'First group should not exist after deletion');
        $this->assertFalse($this->groupExists($group2), 'Second group should not exist after deletion');
    }

    public function testApiDeleteProtectedGroups()
    {
        $data = [
            'items' => ['Admins', 'Anonymous', 'Registered'],
        ];
        $response = $this->makeApiRequest('POST', '/groups/delete', 'Admins', $data);
        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidGroupAddBanDelUsersResponse($body);

        $this->assertEquals('Error', $body['feedback']['action'][0]['title'], 'Title should indicate Error');
        $this->assertEquals(['The following groups cannot be deleted:'], $body['feedback']['action'][0]['mes'], 'Message should indicate protected group');
        $this->assertEquals(['Admins', 'Anonymous', 'Registered'], array_values($body['feedback']['action'][0]['items']), 'Items should list all the protected groups');

        $this->assertTrue($this->groupExists('Admins'), 'Admins group should still exist');
        $this->assertTrue($this->groupExists('Anonymous'), 'Anonymous group should still exist');
        $this->assertTrue($this->groupExists('Registered'), 'Registered group should still exist');
    }

    public function testApiDeleteNonExistentGroup()
    {
        $groupName = 'API_Test_Group_DELETE_NonExistent';
        $this->assertFalse($this->groupExists($groupName), 'Group should not exist');

        $data = ['items' => [$groupName]];
        $response = $this->makeApiRequest('POST', '/groups/delete', 'Admins', $data);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidGroupAddBanDelUsersResponse($body);

        $this->assertEquals('Error', $body['feedback']['action'][0]['title'], 'Title should indicate Error');
        $this->assertEquals(['The following group cannot be deleted:'], $body['feedback']['action'][0]['mes']);
        $this->assertEquals([$groupName], array_values($body['feedback']['action'][0]['items']));
        $this->assertFalse($this->groupExists($groupName), 'Group should still not exist after deletion attempt');
    }

    public function testApiDeleteGroupWithoutItems()
    {
        $response = $this->makeApiRequest('POST', '/groups/delete', 'Admins', ['items' => []]);
        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertEquals(false, $body['feedback'], 'feedback should be false when no items are provided');
    }
}
