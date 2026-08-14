<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Tiki\Profile;

use Tiki_Profile;
use Exception;
use Symfony\Component\Yaml\Yaml;
use TikiLib;

/**
 * Profile Interface Smoke Tests
 *
 * These tests focus on the core profile installation interface to catch breaking changes.
 * They validate basic functionality without deep integration, ensuring interface stability
 * across the profile system.
 */
class InstallInterfaceTest extends AbstractProfilesTestCase
{
    /**
     * Test basic profile installation workflow
     *
     * Validates that the core profile installation interface works correctly
     * with minimal valid profiles, ensuring interface stability.
     */
    public function testBasicProfileInstallationWorkflow()
    {
        // Create a minimal valid profile
        $profileYaml = [
            'profile' => [
                'name' => 'Test Profile',
                'description' => 'Test profile for interface validation'
            ],
            'preferences' => [
                'feature_wiki' => 'y'
            ]
        ];

        $profileContent = $this->createProfileContent($profileYaml);
        $profile = Tiki_Profile::fromString($profileContent, 'TestInterfaceProfile_' . uniqid());

        // Test that profile can be created without errors
        $this->assertInstanceOf('Tiki_Profile', $profile);

        // Test that installer can process the profile
        $this->assertTrue(method_exists($this->installer, 'install'));

        // Test actual installation
        $result = $this->installer->install($profile, 'all');

        // Verify the profile can be installed successfully
        $this->assertTrue($result, 'Profile installation interface should work with valid profiles');
    }

    /**
     * Test YAML profile parsing
     *
     * Validates that YAML content is parsed correctly and profile data
     * is accessible through the getData() method.
     */
    public function testYamlProfileParsing()
    {
        $profileYaml = [
            'profile' => [
                'name' => 'YAML Test Profile',
                'description' => 'Test YAML parsing'
            ],
            'preferences' => [
                'feature_wiki' => 'y',
                'feature_articles' => 'n'
            ]
        ];

        $profileContent = $this->createProfileContent($profileYaml);
        $profile = Tiki_Profile::fromString($profileContent, 'TestInterfaceProfile_' . uniqid());

        // Test that YAML parsing works
        $this->assertInstanceOf('Tiki_Profile', $profile);

        // Test that profile data is accessible
        $data = $profile->getData();
        $this->assertIsArray($data);
    }

    /**
     * Test profile validation catches errors
     *
     * Validates that Tiki_Profile handles invalid YAML gracefully by
     * creating an empty profile instead of throwing exceptions.
     */
    public function testProfileValidationCatchesErrors()
    {
        // Create an invalid profile (malformed YAML without proper wrapping)
        $invalidProfileContent = "invalid: yaml: content: [";

        // Tiki_Profile should handle invalid YAML gracefully by creating an empty profile
        $profile = Tiki_Profile::fromString($invalidProfileContent);

        // Verify the profile was created successfully (graceful error handling)
        $this->assertInstanceOf('Tiki_Profile', $profile);

        // Verify that the profile data is empty (no valid YAML was parsed)
        $data = $profile->getData();
        $this->assertIsArray($data, 'Profile data should be an array');
        $this->assertEmpty($data, 'Profile data should be empty for invalid YAML without proper wrapping');
    }

    /**
     * Test graceful error handling with invalid profiles
     *
     * Validates that Tiki_Profile handles malformed YAML gracefully by
     * storing error information in the profile data instead of throwing exceptions.
     */
    public function testGracefulErrorHandlingWithInvalidProfiles()
    {
        // Test with malformed YAML that will cause parsing errors
        $malformedYaml = "{CODE(caption=\"YAML\")}\nprofile:\n  name: Test\n  description: Test\ninvalid: [\n{CODE}";

        // Tiki_Profile handles errors gracefully by storing error info, not throwing exceptions
        $profile = Tiki_Profile::fromString($malformedYaml);

        // Verify that the profile handles the error gracefully
        $this->assertInstanceOf('Tiki_Profile', $profile);

        // Check that the profile contains an error message for malformed YAML
        $data = $profile->getData();
        $this->assertArrayHasKey('error', $data, 'Profile should contain error information for malformed YAML');
        $this->assertStringContainsString('Could not parse YAML', $data['error'], 'Error message should indicate YAML parsing failure');

        // Verify that the profile can still be used (graceful degradation)
        $this->assertIsArray($data, 'Profile data should be an array even with errors');
    }

    /**
     * Test installer error handling for invalid profile operations
     *
     * Validates that the installer handles profiles with invalid object
     * structures gracefully and provides appropriate feedback.
     */
    public function testInstallerThrowsExceptionsForInvalidOperations()
    {
        // Create a profile with invalid object structure that will cause installer errors
        $invalidProfileData = [
            'profile' => [
                'name' => 'Invalid Profile',
                'description' => 'Profile with invalid object structure'
            ],
            'objects' => [
                [
                    'type' => 'user',
                    // Missing 'data' field - this should cause an error
                ]
            ]
        ];

        $profileContent = $this->createProfileContent($invalidProfileData);
        $profile = Tiki_Profile::fromString($profileContent, 'InvalidProfile_' . uniqid());

        // The installer should handle this gracefully, but let's test the feedback
        $result = $this->installer->install($profile, 'all');

        // Installation should fail gracefully
        $this->assertFalse($result, 'Installation should fail for invalid profile structure');

        // Check that installer provides feedback about the error
        $feedback = $this->installer->getFeedback();
        $this->assertNotEmpty($feedback, 'Installer should provide feedback for invalid profiles');
        $this->assertStringContainsString('No handler found', implode(' ', $feedback), 'Feedback should mention handler error');
    }

    /**
     * Test features and permissions interface
     *
     * Validates that the profile installer correctly sets features and
     * permissions, and that they are actually applied to the system.
     */
    public function testFeaturesAndPermissionsInterface()
    {
        global $prefs;

        $profileYaml = [
            'profile' => [
                'name' => 'Features Test Profile',
                'description' => 'Test features and permissions'
            ],
            'preferences' => [
                'feature_wiki' => 'y',
                'feature_articles' => 'y',
                'feature_blogs' => 'n'
            ],
            'permissions' => [
                'Registered' => [
                    'tiki_p_view' => 'y',
                    'tiki_p_edit' => 'y'
                ]
            ]
        ];

        $profileContent = $this->createProfileContent($profileYaml);
        $profile = Tiki_Profile::fromString($profileContent, 'TestInterfaceProfile_' . uniqid());

        // Test that profile can be created
        $this->assertInstanceOf('Tiki_Profile', $profile);

        // Test that installer can process features and permissions
        $result = $this->installer->install($profile, 'all');

        // Verify the profile can be installed successfully
        $this->assertTrue($result, 'Features and permissions should install successfully');

        // Verify that features are actually set
        $this->assertEquals('y', $prefs['feature_wiki'], 'Wiki feature should be enabled');
        $this->assertEquals('y', $prefs['feature_articles'], 'Articles feature should be enabled');
        $this->assertEquals('n', $prefs['feature_blogs'], 'Blogs feature should be disabled');

        // Verify that permissions are actually set
        $userlib = TikiLib::lib('user');
        $permissions = $userlib->get_group_permissions('Registered');

        $this->assertContains('tiki_p_view', $permissions, 'Registered group should have tiki_p_view permission');
        $this->assertContains('tiki_p_edit', $permissions, 'Registered group should have tiki_p_edit permission');
    }

    /**
     * Test profile installation with valid cache option
     *
     * Validates that profile installation works correctly with the
     * valid cache option 'all'.
     */
    public function testProfileInstallationWithValidCacheOption()
    {
        $profileYaml = [
            'profile' => [
                'name' => 'Cache Test Profile',
                'description' => 'Test valid cache option'
            ],
            'preferences' => [
                'feature_wiki' => 'y'
            ]
        ];

        $profileContent = $this->createProfileContent($profileYaml);
        $profile = Tiki_Profile::fromString($profileContent, 'TestInterfaceProfile_' . uniqid());

        // Test with valid cache option
        $result = $this->installer->install($profile, 'all');

        // Verify the profile can be installed successfully
        $this->assertTrue($result, 'Profile installation with cache=all should work');

        // Verify that the installer provides feedback
        $feedback = $this->installer->getFeedback();
        $this->assertNotEmpty($feedback, 'Installer should provide feedback for cache=all option');
    }

    /**
     * Test profile installation with invalid cache option
     *
     * Validates that profile installation handles invalid cache options
     * gracefully and still succeeds.
     */
    public function testProfileInstallationWithInvalidCacheOption()
    {
        $profileYaml = [
            'profile' => [
                'name' => 'Cache Test Profile 2',
                'description' => 'Test invalid cache option'
            ],
            'preferences' => [
                'feature_articles' => 'y'
            ]
        ];

        $profileContent = $this->createProfileContent($profileYaml);
        $profile = Tiki_Profile::fromString($profileContent, 'TestInterfaceProfile2_' . uniqid());

        // Test with invalid cache option
        // The installer should handle invalid cache options gracefully
        $result = $this->installer->install($profile, 'none');

        // The installer should still succeed even with invalid cache option
        $this->assertTrue($result, 'Profile installation should succeed even with invalid cache option');

        // Check that the installer provides appropriate feedback
        $feedback = $this->installer->getFeedback();
        $this->assertNotEmpty($feedback, 'Installer should provide feedback for invalid cache option');
    }

    /**
     * Test profile with invalid handler types
     *
     * Validates that the installer handles profiles with invalid
     * handler types gracefully without crashing.
     */
    public function testProfileWithInvalidHandlerTypes()
    {
        $profileYaml = [
            'profile' => [
                'name' => 'Invalid Handler Test',
                'description' => 'Test with invalid handler types'
            ],
            'objects' => [
                'invalid_handler' => [
                    'test_object' => [
                        'name' => 'Test Object',
                        'description' => 'This should be handled gracefully'
                    ]
                ]
            ]
        ];

        $profileContent = $this->createProfileContent($profileYaml);
        $profile = Tiki_Profile::fromString($profileContent, 'TestInterfaceProfile_' . uniqid());

        // Test that profile can be created even with invalid handlers
        $this->assertInstanceOf('Tiki_Profile', $profile);

        // Installation should handle invalid handlers gracefully
        $result = $this->installProfile($profile);
        $this->assertIsBool($result, 'Invalid handlers should be handled gracefully');
    }

    /**
     * Test profile with missing required fields
     *
     * Validates that minimal profiles with missing optional fields
     * can still be installed successfully.
     */
    public function testProfileWithMissingRequiredFields()
    {
        // Create a profile with minimal data
        $profileYaml = [
            'profile' => [
                'name' => 'Minimal Profile'
                // Missing description - should still work
            ]
        ];

        $profileContent = $this->createProfileContent($profileYaml);
        $profile = Tiki_Profile::fromString($profileContent, 'TestInterfaceProfile_' . uniqid());

        // Test that minimal profiles can be created
        $this->assertInstanceOf('Tiki_Profile', $profile);

        // Test that minimal profiles can be installed
        $result = $this->installer->install($profile, 'all');
        $this->assertTrue($result, 'Minimal profiles should install successfully');
    }

    /**
     * Test loading sample profile from disk
     *
     * Validates that profiles can be loaded from disk files and
     * installed successfully.
     */
    public function testLoadSampleProfileFromDisk()
    {
        $profileData = [
            'profile' => [
                'name' => 'Disk Sample Profile',
                'description' => 'Test profile loaded from disk'
            ],
            'preferences' => [
                'feature_wiki' => 'y',
                'feature_articles' => 'y'
            ]
        ];

        // Create sample profile file
        $filename = 'sample_profile.yml';
        $this->createRawYamlProfileFile($filename, $profileData);

        // Load profile from disk
        $profile = $this->loadSampleProfileFromDisk($filename);

        // Test that profile can be loaded from disk
        $this->assertInstanceOf('Tiki_Profile', $profile);

        // Test that profile from disk can be installed
        $result = $this->installer->install($profile, 'all');
        $this->assertTrue($result, 'Profile loaded from disk should install successfully');
    }

    /**
     * Test loading basic wiki profile from shipped sample
     *
     * Validates that the basic wiki sample profile can be loaded using
     * Tiki_Profile::fromFile() and installed successfully.
     */
    public function testLoadBasicWikiProfileFromShippedSample()
    {
        $profilePath = __DIR__ . '/Fixtures/basic_wiki_profile.yml';

        // Verify the sample file exists
        $this->assertFileExists($profilePath, 'Basic wiki sample profile should exist');

        // Load profile directly from the shipped sample file using Tiki_Profile::fromFile
        $profile = Tiki_Profile::fromFile(__DIR__ . '/Fixtures', 'basic_wiki_profile');

        // Verify the profile was loaded successfully
        $this->assertInstanceOf('Tiki_Profile', $profile);

        // Verify objects exist
        $data = $profile->getData();
        $this->assertNotEmpty($data['objects'], 'Profile should have objects');

        // Check if the profile is already installed and forget it if necessary
        if ($this->installer->isInstalled($profile)) {
            $this->installer->forget($profile);
        }

        // Test profile installation
        $result = $this->installer->install($profile, 'all');
        $this->assertTrue($result, 'Basic wiki profile from sample should install successfully');

        // Verify the page was actually created
        $this->assertWikiPageExists('TestSamplePage');

        // Clean up: remove the created page
        $this->cleanupWikiPage('TestSamplePage');
    }

    /**
     * Test loading user management profile from shipped sample
     *
     * Validates that the user management sample profile can be loaded using
     * Tiki_Profile::fromFile() and installed successfully.
     */
    public function testLoadUserManagementProfileFromShippedSample()
    {
        $profilePath = __DIR__ . '/Fixtures/user_management_profile.yml';

        // Verify the sample file exists
        $this->assertFileExists($profilePath, 'User management sample profile should exist');

        // Load profile directly from the shipped sample file using Tiki_Profile::fromFile
        $profile = Tiki_Profile::fromFile(__DIR__ . '/Fixtures', 'user_management_profile');

        // Check if the profile is already installed and forget it if necessary
        if ($this->installer->isInstalled($profile)) {
            $this->installer->forget($profile);
        }

        // Test profile installation
        $result = $this->installer->install($profile, 'all');
        $this->assertTrue($result, 'User management profile from sample should install successfully');

        // Verify the user was actually created
        $this->assertUserExists('testuser_profile_sample');

        // Clean up: remove the created user
        $this->cleanupUser('testuser_profile_sample');
    }

    /**
     * Test loading comprehensive profile from shipped sample
     *
     * Validates that the comprehensive sample profile can be loaded using
     * Tiki_Profile::fromFile() and installed successfully.
     */
    public function testLoadComprehensiveProfileFromShippedSample()
    {
        $profilePath = __DIR__ . '/Fixtures/comprehensive_profile.yml';

        // Verify the sample file exists
        $this->assertFileExists($profilePath, 'Comprehensive sample profile should exist');

        // Load profile directly from the shipped sample file using Tiki_Profile::fromFile
        $profile = Tiki_Profile::fromFile(__DIR__ . '/Fixtures', 'comprehensive_profile');

        // Check if the profile is already installed and forget it if necessary
        if ($this->installer->isInstalled($profile)) {
            $this->installer->forget($profile);
        }

        // Test profile installation
        $result = $this->installer->install($profile, 'all');
        $this->assertTrue($result, 'Comprehensive profile from sample should install successfully');

        // Verify all objects were actually created
        $this->assertCategoryExists('TestSampleCategory');
        $this->assertTrackerExists('TestSampleTracker');
        $this->assertWikiPageExists('TestSampleComprehensivePage');

        // Clean up: remove the created objects (in reverse order)
        $this->cleanupWikiPage('TestSampleComprehensivePage');
        $this->cleanupTracker('TestSampleTracker');
        $this->cleanupCategory('TestSampleCategory');
    }
}
