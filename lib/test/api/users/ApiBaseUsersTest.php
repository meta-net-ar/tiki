<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Users;

use Tiki\Lib\Test\Api\ApiTestCase;
use TikiLib;

/**
 * Base class for Users API integration tests
 * Provides common setup, teardown, schema definitions, and assertion helpers
 *
 * @group api-integration-test
 */
abstract class ApiBaseUsersTest extends ApiTestCase
{
    /**
     * Usernames created during the test run (for cleanup)
     * @var array
     */
    protected static $testUsers = [];

    /**
     * Group names created during the test run (for cleanup)
     * @var array
     */
    protected static $testGroups = [];

    /**
     * Default test user available for read/info tests
     * @var string|null
     */
    protected static $defaultTestUser = null;

    /**
     * Default test group available for group management tests
     * @var string|null
     */
    protected static $defaultTestGroup = null;

    /**
     * Preferences disabled during test run to avoid side-effects
     */
    private static $preferences = [
        // Unified search triggers index updates on user creation which fail in the
        // headless subprocess environment when no search index is configured.
        'unified_engine' => 'n',
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        foreach (self::$preferences as $pref => $value) {
            static::setTestPreference($pref, $value);
        }

        static::createDefaultTestData();
    }

    public static function tearDownAfterClass(): void
    {
        static::cleanupTestData();
        parent::tearDownAfterClass();
    }

    private static function createDefaultTestData(): void
    {
        $uid = substr(md5(uniqid('', true)), 0, 8);

        $username  = 'api_test_user_' . $uid;
        $groupName = 'API_Test_Users_Group_' . $uid;

        if (static::createUser($username)) {
            static::$defaultTestUser = $username;
        }

        if (static::createGroup($groupName)) {
            static::$defaultTestGroup = $groupName;
        }
    }

    /**
     * Create a user and register it for cleanup.
     */
    protected static function createUser(string $username, string $password = 'TestPass123!', string $email = ''): bool
    {
        $userlib = TikiLib::lib('user');

        if ($userlib->user_exists($username)) {
            static::$testUsers[] = $username;
            return true;
        }

        $email  = $email ?: $username . '@api-test.tiki.org';
        $result = $userlib->add_user($username, $password, $email, '', false);

        if ($result) {
            static::$testUsers[] = $username;
            return true;
        }
        return false;
    }

    /**
     * Create a group and register it for cleanup.
     */
    protected static function createGroup(string $groupName, string $desc = ''): bool
    {
        $userlib = TikiLib::lib('user');

        if ($userlib->group_exists($groupName)) {
            static::$testGroups[] = $groupName;
            return true;
        }

        $groupId = $userlib->add_group($groupName, $desc ?: "API integration test group: $groupName");

        if ($groupId) {
            static::$testGroups[] = $groupName;
            return true;
        }
        return false;
    }

    protected static function cleanupTestData(): void
    {
        $userlib = TikiLib::lib('user');
        $tikilib = TikiLib::lib('tiki');

        foreach (static::$testUsers as $username) {
            try {
                if ($userlib->user_exists($username)) {
                    $userlib->remove_user($username);
                }
            } catch (\Exception) {
                // Ignore cleanup errors
            }
        }
        static::$testUsers = [];

        foreach (array_reverse(static::$testGroups) as $groupName) {
            try {
                if ($userlib->group_exists($groupName)) {
                    $userlib->remove_group($groupName);
                }
            } catch (\Exception) {
                // Ignore cleanup errors
            }
        }
        static::$testGroups = [];

        static::$defaultTestUser  = null;
        static::$defaultTestGroup = null;

        foreach (array_keys(self::$preferences) as $pref) {
            $tikilib->delete_preference($pref);
        }
    }

    /**
     * Schema for a single user entry in the GET /users result array.
     */
    private function getUserListEntrySchema(): array
    {
        return [
            'userId'           => 'int|string',
            'login'            => 'string',
            'default_group'    => 'string|null',
            'lastLogin'        => 'int|null',
            'currentLogin'     => 'int|null',
            'registrationDate' => 'int|string',
            'pass_confirm'     => 'int|null',
            'email_confirm'    => 'int|null',
            'created'          => 'int|string',
            'valid'            => 'string|null',
            'unsuccessful_logins' => 'int',
            'waiting'          => 'string|null',
            'user'             => 'string',
            'groups'           => 'array',
            'age'              => 'int|string',
            'editable'         => 'bool',
            'disposable_email' => 'bool',
            'avatar'           => 'string|null',
        ];
    }

    /**
     * Top-level schema for GET /users (action_list_users).
     */
    private function getUserListSchema(): array
    {
        return [
            'result' => [$this->getUserListEntrySchema()],
            'count'  => 'int',
        ];
    }

    /**
     * Schema for GET /users/{username} (action_info).
     */
    private function getUserInfoSchema(): array
    {
        return [
            'fullname'      => 'string',
            'gender'        => 'string',
            'starHtml'      => 'string',
            'country'       => 'string',
            'distance'      => 'string',
            'email'         => 'string',
            'lastSeen'      => 'int|string|null',
            'avatarHtml'    => 'string',
            'error'         => 'string',
            'shared_groups' => 'string',
            'other_user'    => 'string|null',
            'add_friend_button' => 'string|null',
            'friendship'    => 'array',
        ];
    }

    /**
     * Schema for GET /message-count (action_get_message_count).
     */
    private function getMessageCountSchema(): array
    {
        return [
            'count' => 'int',
        ];
    }

    /**
     * Schema for POST /users (action_register) success response.
     */
    private function getRegisterUserSchema(): array
    {
        return [
            'result' => 'string|array',
        ];
    }

    private function getDeleteUserFeedbackSchema(): array
    {
        return [
            'feedback' => [
                'tpl'   => 'string',
                'mes'   => 'string',
                'items' => 'array',
            ],
        ];
    }

    private function getManageGroupsFeedbackSchema(): array
    {
        return [
            'feedback' => [
                'tpl'    => 'string',
                'mes'    => 'string',
                'items'  => 'array',
                'toMsg'  => 'string',
                'toList' => 'array',
            ],
        ];
    }

    protected function assertValidUserListResponse(array $body): void
    {
        $this->assertMatchesSchema($body, $this->getUserListSchema());
        $this->assertIsArray($body['result'], 'result should be an array of users');
        $this->assertNotEmpty($body['result'], 'Result array should not be empty');
        $this->assertIsInt($body['count'], 'count should be an integer');
        $this->assertGreaterThan(0, $body['count'], 'User list should have at least one user (admin)');
    }

    protected function assertValidUserInfoResponse(array $body): void
    {
        $this->assertMatchesSchema($body, $this->getUserInfoSchema());
    }

    protected function assertValidMessageCountResponse(array $body): void
    {
        $this->assertMatchesSchema($body, $this->getMessageCountSchema());
        $this->assertGreaterThanOrEqual(0, $body['count'], 'Message count should not be negative');
    }

    protected function assertValidRegisterUserResponse(array $body): void
    {
        $this->assertMatchesSchema($body, $this->getRegisterUserSchema());
    }

    protected function assertValidDeleteUserResponse(array $body): void
    {
        $this->assertMatchesSchema($body, $this->getDeleteUserFeedbackSchema());
    }

    protected function assertValidManageGroupsResponse(array $body): void
    {
        $this->assertMatchesSchema($body, $this->getManageGroupsFeedbackSchema());
    }
}
