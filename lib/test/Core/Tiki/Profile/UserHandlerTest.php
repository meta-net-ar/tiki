<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Tiki\Profile;

use Tiki_Profile;

/**
 * Test User handler basic functionality
 *
 * Tests the Tiki_Profile_InstallHandler_User functionality
 * to ensure user creation and management works correctly.
 */
class UserHandlerTest extends AbstractProfilesTestCase
{
    /**
     * Test User handler basic functionality
     *
     * Validates that the Tiki_Profile_InstallHandler_User can create
     * users with basic data and group assignments.
     */
    public function testUserHandlerInterface()
    {
        $uniqueUser = $this->createUniqueTestUser();

        $profileYaml = [
            'profile' => [
                'name' => 'Test User Profile',
                'description' => 'Test profile for user handler validation'
            ],
            'preferences' => [
                'feature_wiki' => 'y',
                'userTracker' => 'n',
                'feature_user_watches' => 'y',
                'user_register_prettytracker' => 'n',
                'allowRegister' => 'y',
                'validateUsers' => 'n'
            ],
            'objects' => [
                'user' => [
                    $uniqueUser => [
                        'name' => $uniqueUser,
                        'email' => $uniqueUser . '@example.com',
                        'pass' => 'testpass123',
                        'groups' => ['Registered'],
                        'preferences' => [
                            'realName' => 'Test User from Profile'
                        ]
                    ]
                ]
            ]
        ];

        $profileContent = $this->createProfileContent($profileYaml);
        $profile = Tiki_Profile::fromString($profileContent, 'TestUserProfile_' . uniqid());

        // Test that profile can be created without errors
        $this->assertInstanceOf('Tiki_Profile', $profile);

        // Test that the profile can be installed
        $result = $this->installer->install($profile, 'all');

        // Verify the profile can be installed successfully
        $this->assertTrue($result, 'User profile should install successfully');

        // Verify the user was actually created by the profile installation
        $this->assertUserExists($uniqueUser);

        // Clean up: remove the created user
        $this->cleanupUser($uniqueUser);
    }

    /**
     * Test User handler with complex data
     *
     * Validates that the Tiki_Profile_InstallHandler_User can create
     * users with complex data including multiple groups and preferences.
     */
    public function testUserHandlerWithComplexData()
    {
        $uniqueUser = $this->createUniqueTestUser();
        $uniqueGroup = $this->createUniqueTestGroup();

        // Create the group first
        $this->createTestGroup($uniqueGroup);

        $profileYaml = [
            'profile' => [
                'name' => 'Complex User Profile',
                'description' => 'Test profile with complex user data'
            ],
            'objects' => [
                'user' => [
                    $uniqueUser => [
                        'name' => $uniqueUser,
                        'email' => $uniqueUser . '@example.com',
                        'pass' => 'testpass123',
                        'groups' => ['Registered', $uniqueGroup],
                        'preferences' => [
                            'realName' => 'Test User Real Name',
                            'homePage' => 'http://example.com'
                        ]
                    ]
                ]
            ]
        ];

        $profileContent = $this->createProfileContent($profileYaml);
        $profile = Tiki_Profile::fromString($profileContent, 'TestUserProfile_' . uniqid());

        // Test that the profile can be installed
        $result = $this->installer->install($profile, 'all');
        $this->assertTrue($result, 'Complex user profile should install successfully');

        // Verify the user was actually created
        $this->assertUserExists($uniqueUser);

        // Clean up: remove the created user
        $this->cleanupUser($uniqueUser);
    }

    /**
     * Test loading user profile from disk
     *
     * Validates that user profiles can be loaded from disk files
     * and installed successfully.
     */
    public function testLoadUserProfileFromDisk()
    {
        $uniqueUser = $this->createUniqueTestUser();

        $profileData = [
            'profile' => [
                'name' => 'Disk User Profile',
                'description' => 'Test profile loaded from disk'
            ],
            'objects' => [
                'user' => [
                    $uniqueUser => [
                        'name' => $uniqueUser,
                        'email' => $uniqueUser . '@example.com',
                        'pass' => 'testpass123',
                        'groups' => ['Registered']
                    ]
                ]
            ]
        ];

        // Create sample profile file
        $filename = 'user_profile.yml';
        $this->createRawYamlProfileFile($filename, $profileData);

        // Load profile from disk
        $profile = $this->loadSampleProfileFromDisk($filename);

        // Test that the profile can be installed
        $result = $this->installer->install($profile, 'all');
        $this->assertTrue($result, 'User profile from disk should install successfully');

        // Verify the user was actually created
        $this->assertUserExists($uniqueUser);

        // Clean up: remove the created user
        $this->cleanupUser($uniqueUser);
    }
}
