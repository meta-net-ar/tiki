<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Groups;

use Tiki\Lib\Test\Api\ApiSchemaLoader;
use Tiki\Lib\Test\Api\ApiTestCase;
use TikiLib;
use Exception;

/**
 * Base class for Groups API integration tests
 * Provides common setup, teardown, and helper methods
 *
 * @group api-integration-test
 */
abstract class ApiBaseGroupsTest extends ApiTestCase
{
    /**
     * Array to track test group names for cleanup
     * @var array
     */
    protected static $testGroups = [];

    /**
     * Array to track test user names for cleanup
     * @var array
     */
    protected static $testUsers = [];

    /**
     * Default groups created for testing
     * @var array
     */
    protected static $defaultGroups = [];

    /**
     * Preferences to enable required features
     * @var array
     */
    private static $preferences = [
        'unified_engine' => 'n',  // FIXME: Disable unified search - causes fatal error on group creation due to index corruption in test environment
    ];

    /**
     * Setup before class - enable features and create default test groups
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::setTestPreferences(self::$preferences);

        // Create default test groups
        static::createDefaultTestGroups();
    }

    /**
     * Teardown after class - cleanup all test data
     */
    public static function tearDownAfterClass(): void
    {
        static::cleanupTestData();
        parent::tearDownAfterClass();
    }

    /**
     * Create default test groups for all tests
     */
    protected static function createDefaultTestGroups()
    {
        // Create a default test group
        $defaultGroupName = 'API_Test_Group_Default';
        $defaultGroupId = static::createGroup($defaultGroupName, 'Default test group');
        if ($defaultGroupId) {
            static::$defaultGroups['default'] = [
                'id' => $defaultGroupId,
                'groupName' => $defaultGroupName
            ];
        }

        // Create a role group
        $roleGroupName = 'API_Test_Role_Group';
        $roleGroupId = static::createGroup($roleGroupName, 'Role test group', ['isRole' => 'y']);
        if ($roleGroupId) {
            static::$defaultGroups['role'] = [
                'id' => $roleGroupId,
                'groupName' => $roleGroupName
            ];
        }

        // Create a template group
        $tplGroupName = 'API_Test_Template_Group';
        $tplGroupId = static::createGroup($tplGroupName, 'Template test group', ['isTplGroup' => 'y']);
        if ($tplGroupId) {
            static::$defaultGroups['template'] = [
                'id' => $tplGroupId,
                'groupName' => $tplGroupName
            ];
        }
    }

    /**
     * Create a group for testing
     * @param string $name Group name
     * @param string $desc Group description
     * @param array $params Additional parameters (isRole, isTplGroup, home, theme, etc.)
     * @return int|null The group ID or null on failure
     */
    protected static function createGroup($name, $desc = '', $params = [])
    {
        $userlib = TikiLib::lib('user');

        // Check if group already exists
        if ($userlib->group_exists($name)) {
            $info = $userlib->get_group_info($name);
            $groupId = $info['id'] ?? null;
            if ($groupId) {
                static::$testGroups[] = $name;
            }
            return $groupId;
        }

        // Prepare parameters with defaults
        $home = $params['home'] ?? '';
        $userstracker = $params['userstracker'] ?? 0;
        $groupstracker = $params['groupstracker'] ?? 0;
        $registrationUsersFieldIds = $params['registrationUsersFieldIds'] ?? '';
        $userChoice = $params['userChoice'] ?? '';
        $defcat = $params['defcat'] ?? 0;
        $theme = $params['theme'] ?? '';
        $usersfield = $params['usersfield'] ?? 0;
        $groupfield = $params['groupfield'] ?? 0;
        $expireAfter = $params['expireAfter'] ?? 0;
        $emailPattern = $params['emailPattern'] ?? '';
        $anniversary = $params['anniversary'] ?? '';
        $prorateInterval = $params['prorateInterval'] ?? '';
        $color = $params['color'] ?? '';
        $isRole = $params['isRole'] ?? '';
        $isTplGroup = $params['isTplGroup'] ?? '';

        $groupId = $userlib->add_group(
            $name,
            $desc,
            $home,
            $userstracker,
            $groupstracker,
            $registrationUsersFieldIds,
            $userChoice,
            $defcat,
            $theme,
            $usersfield,
            $groupfield,
            'n', // isExternal
            $expireAfter,
            $emailPattern,
            $anniversary,
            $prorateInterval,
            $color,
            $isRole,
            $isTplGroup
        );

        if ($groupId) {
            static::$testGroups[] = $name;
        }

        return $groupId ?: null;
    }

    /**
     * Create a test user for testing
     * @param string $username Username
     * @param string $email Email
     * @param string $password Password
     * @return bool True on success
     */
    protected static function createUser($username, $email = null, $password = 'testpass123')
    {
        $email  = $email ?? $username . '@test.tiki.org';
        $result = static::createTestUser($username, $password, $email);
        if ($result) {
            static::$testUsers[] = $username;
        }
        return $result;
    }

    /**
     * Add user to group
     * @param string $username Username
     * @param string $groupName Group name
     * @return bool True on success
     */
    protected static function addUserToGroup($username, $groupName)
    {
        $userlib = TikiLib::lib('user');
        return $userlib->assign_user_to_group($username, $groupName);
    }

    /**
     * Cleanup all test data
     */
    protected static function cleanupTestData()
    {
        $userlib = TikiLib::lib('user');

        // Remove users from all test groups first
        foreach (static::$testUsers as $username) {
            foreach (static::$testGroups as $groupName) {
                try {
                    $userlib->remove_user_from_group($username, $groupName);
                } catch (Exception) {
                    // Ignore errors during cleanup
                }
            }
        }

        // Remove all test users
        foreach (static::$testUsers as $username) {
            try {
                if ($userlib->user_exists($username)) {
                    $userlib->remove_user($username);
                }
            } catch (Exception) {
                // Ignore errors during cleanup
            }
        }

        // Remove all test groups (in reverse order)
        foreach (array_reverse(static::$testGroups) as $groupName) {
            try {
                if ($userlib->group_exists($groupName)) {
                    $userlib->remove_group($groupName);
                }
            } catch (Exception) {
                // Ignore errors during cleanup
            }
        }

        static::deleteTestPreferences(self::$preferences);
    }

    protected function getGroupListResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('GroupListResponse.yaml');
    }

    protected function getGroupActionResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('GroupActionResponse.yaml');
    }

    protected function getGroupCreateUpdateResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('GroupActionFeedbackResponse.yaml');
    }

    protected function getGroupAddBanDelUsersResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('GroupUserActionFeedbackResponse.yaml');
    }

    protected function assertValidGroupListResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getGroupListResponseSchema());
    }

    protected function assertValidGroupCreateUpdateResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getGroupCreateUpdateResponseSchema());
    }

    protected function assertValidGroupAddBanDelUsersResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getGroupAddBanDelUsersResponseSchema());
    }

    /**
     * Check if a group exists
     * @param string $groupName
     * @return bool
     */
    protected function groupExists($groupName)
    {
        $userlib = TikiLib::lib('user');
        return $userlib->group_exists($groupName);
    }

    /**
     * Get users in a group
     * @param string $groupName
     * @return array
     */
    protected function getGroupUsers($groupName)
    {
        $userlib = TikiLib::lib('user');
        return $userlib->get_group_users($groupName);
    }
}
