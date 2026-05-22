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

        static::setTestPreferences(self::$preferences);

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
     * Delegates DB logic to ApiTestCase::createTestUser().
     */
    protected static function createUser(string $username, string $password = 'TestPass123!', string $email = ''): bool
    {
        $result = static::createTestUser($username, $password, $email);
        if ($result) {
            static::$testUsers[] = $username;
        }
        return $result;
    }

    /**
     * Create a group and register it for cleanup.
     * Delegates DB logic to ApiTestCase::createTestGroup().
     */
    protected static function createGroup(string $groupName, string $desc = ''): bool
    {
        $result = static::createTestGroup($groupName, $desc);
        if ($result) {
            static::$testGroups[] = $groupName;
        }
        return $result;
    }

    protected static function cleanupTestData(): void
    {
        $userlib = TikiLib::lib('user');

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

        static::deleteTestPreferences(self::$preferences);
    }

    /**
     * Schema for a single user entry in the GET /users result array.
     */
    protected function getUserListEntrySchema(): array
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
    protected function getUserListSchema(): array
    {
        return [
            'result' => [$this->getUserListEntrySchema()],
            'count'  => 'int',
        ];
    }

    /**
     * Schema for GET /users/{username} (action_info).
     */
    protected function getUserInfoSchema(): array
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
    protected function getMessageCountSchema(): array
    {
        return [
            'count' => 'int',
        ];
    }

    /**
     * Schema for POST /users (action_register) success response.
     */
    protected function getRegisterUserSchema(): array
    {
        return [
            'result' => 'string|null',
        ];
    }

    protected function getDeleteUserFeedbackSchema(): array
    {
        return [
            'feedback' => [
                'tpl'   => 'string',
                'mes'   => 'string',
                'items' => 'array',
            ],
        ];
    }

    protected function getManageGroupsFeedbackSchema(): array
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
