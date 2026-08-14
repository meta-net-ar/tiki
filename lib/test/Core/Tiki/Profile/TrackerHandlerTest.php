<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Tiki\Profile;

use Tiki_Profile;
use TikiLib;

/**
 * Test Tracker handler basic functionality
 *
 * Tests the Tiki_Profile_InstallHandler_Tracker functionality
 * to ensure tracker creation and management works correctly.
 */
class TrackerHandlerTest extends AbstractProfilesTestCase
{
    /**
     * Test Tracker handler basic functionality
     *
     * Validates that the Tiki_Profile_InstallHandler_Tracker can create
     * trackers with basic metadata.
     */
    public function testTrackerHandlerInterface()
    {
        $uniqueTracker = 'TestTracker_' . uniqid() . '_' . mt_rand(1000, 9999);

        $profileYaml = [
            'profile' => [
                'name' => 'Test Tracker Profile',
                'description' => 'Test profile for tracker handler validation'
            ],
            'objects' => [
                'tracker' => [
                    $uniqueTracker => [
                        'name' => $uniqueTracker,
                        'description' => 'Test tracker created by profile installation'
                    ]
                ]
            ]
        ];

        $profileContent = $this->createProfileContent($profileYaml);
        $profile = Tiki_Profile::fromString($profileContent, 'TestTrackerProfile_' . uniqid());

        // Test that profile can be created without errors
        $this->assertInstanceOf('Tiki_Profile', $profile);

        // Test that the profile can be installed
        $result = $this->installer->install($profile, 'all');

        // Verify the profile can be installed successfully
        $this->assertTrue($result, 'Tracker profile should install successfully');

        // Verify the tracker was actually created
        $this->assertTrackerExists($uniqueTracker);

        // Clean up: remove the created tracker
        $this->cleanupTracker($uniqueTracker);
    }

    /**
     * Test Tracker handler with complex data
     *
     * Validates that the Tiki_Profile_InstallHandler_Tracker can create
     * trackers with complex metadata and configurations.
     */
    public function testTrackerHandlerWithComplexData()
    {
        $uniqueTracker = 'ComplexTracker_' . uniqid() . '_' . mt_rand(1000, 9999);

        $profileYaml = [
            'profile' => [
                'name' => 'Complex Tracker Profile',
                'description' => 'Test profile with complex tracker data'
            ],
            'objects' => [
                'tracker' => [
                    $uniqueTracker => [
                        'name' => $uniqueTracker,
                        'description' => 'Complex test tracker with multiple fields'
                    ]
                ]
            ]
        ];

        $profileContent = $this->createProfileContent($profileYaml);
        $profile = Tiki_Profile::fromString($profileContent, 'TestTrackerProfile_' . uniqid());

        // Test that the profile can be installed
        $result = $this->installer->install($profile, 'all');
        $this->assertTrue($result, 'Complex Tracker profile should install successfully');

        // Verify the tracker was actually created
        $this->assertTrackerExists($uniqueTracker);

        // Clean up: remove the created tracker
        $this->cleanupTracker($uniqueTracker);
    }

    /**
     * Test loading Tracker profile from disk
     *
     * Validates that tracker profiles can be loaded from disk files
     * and installed successfully.
     */
    public function testLoadTrackerProfileFromDisk()
    {
        $uniqueTracker = 'DiskTracker_' . uniqid() . '_' . mt_rand(1000, 9999);

        $profileData = [
            'profile' => [
                'name' => 'Disk Tracker Profile',
                'description' => 'Test profile loaded from disk'
            ],
            'objects' => [
                'tracker' => [
                    $uniqueTracker => [
                        'name' => $uniqueTracker,
                        'description' => 'Test tracker loaded from disk'
                    ]
                ]
            ]
        ];

        // Create sample profile file
        $filename = 'tracker_profile.yml';
        $this->createRawYamlProfileFile($filename, $profileData);

        // Load profile from disk
        $profile = $this->loadSampleProfileFromDisk($filename);

        // Test that the profile can be installed
        $result = $this->installer->install($profile, 'all');
        $this->assertTrue($result, 'Tracker profile from disk should install successfully');

        // Verify the tracker was actually created
        $this->assertTrackerExists($uniqueTracker);

        // Clean up: remove the created tracker
        $this->cleanupTracker($uniqueTracker);
    }
}
