<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Tiki\Profile;

use Tiki_Profile;
use TikiLib;

/**
 * Test Category handler basic functionality
 *
 * Tests the Tiki_Profile_InstallHandler_Category functionality
 * to ensure category creation and management works correctly.
 */
class CategoryHandlerTest extends AbstractProfilesTestCase
{
    /**
     * Test Category handler basic functionality
     *
     * Validates that the Tiki_Profile_InstallHandler_Category can create
     * categories with basic metadata.
     */
    public function testCategoryHandlerInterface()
    {
        $uniqueCategory = 'TestCategory_' . uniqid() . '_' . mt_rand(1000, 9999);

        $profileYaml = [
            'profile' => [
                'name' => 'Test Category Profile',
                'description' => 'Test profile for category handler validation'
            ],
            'objects' => [
                'category' => [
                    $uniqueCategory => [
                        'name' => $uniqueCategory,
                        'description' => 'Test category created by profile installation',
                        'parent' => 0
                    ]
                ]
            ]
        ];

        $profileContent = $this->createProfileContent($profileYaml);
        $profile = Tiki_Profile::fromString($profileContent, 'TestCategoryProfile_' . uniqid());

        // Test that profile can be created without errors
        $this->assertInstanceOf('Tiki_Profile', $profile);

        // Test that the profile can be installed
        $result = $this->installer->install($profile, 'all');

        // Verify the profile can be installed successfully
        $this->assertTrue($result, 'Category profile should install successfully');

        // Verify the category was actually created
        $this->assertCategoryExists($uniqueCategory);

        // Clean up: remove the created category
        $this->cleanupCategory($uniqueCategory);
    }

    /**
     * Test Category handler with hierarchical data
     *
     * Validates that the Tiki_Profile_InstallHandler_Category can create
     * hierarchical category structures with parent-child relationships.
     */
    public function testCategoryHandlerWithHierarchy()
    {
        $parentCategory = 'ParentCategory_' . uniqid() . '_' . mt_rand(1000, 9999);
        $childCategory = 'ChildCategory_' . uniqid() . '_' . mt_rand(1000, 9999);

        $profileYaml = [
            'profile' => [
                'name' => 'Hierarchical Category Profile',
                'description' => 'Test profile with category hierarchy'
            ],
            'objects' => [
                'category' => [
                    $parentCategory => [
                        'name' => $parentCategory,
                        'description' => 'Parent category',
                        'parent' => 0
                    ],
                    $childCategory => [
                        'name' => $childCategory,
                        'description' => 'Child category',
                        'parent' => $parentCategory
                    ]
                ]
            ]
        ];

        $profileContent = $this->createProfileContent($profileYaml);
        $profile = Tiki_Profile::fromString($profileContent, 'TestCategoryProfile_' . uniqid());

        // Test that the profile can be installed
        $result = $this->installer->install($profile, 'all');

        // Verify the profile can be installed successfully
        $this->assertTrue($result, 'Hierarchical Category profile should install successfully');

        // Verify both categories were actually created
        $this->assertCategoryExists($parentCategory);
        $this->assertCategoryExists($childCategory);

        // Clean up: remove the created categories (child first, then parent)
        $this->cleanupCategory($childCategory);
        $this->cleanupCategory($parentCategory);
    }

    /**
     * Test loading Category profile from disk
     *
     * Validates that category profiles can be loaded from disk files
     * and installed successfully.
     */
    public function testLoadCategoryProfileFromDisk()
    {
        $uniqueCategory = 'DiskCategory_' . uniqid() . '_' . mt_rand(1000, 9999);

        $profileData = [
            'profile' => [
                'name' => 'Disk Category Profile',
                'description' => 'Test profile loaded from disk'
            ],
            'objects' => [
                'category' => [
                    $uniqueCategory => [
                        'name' => $uniqueCategory,
                        'description' => 'Test category loaded from disk',
                        'parent' => 0
                    ]
                ]
            ]
        ];

        // Create sample profile file
        $filename = 'category_profile.yml';
        $this->createRawYamlProfileFile($filename, $profileData);

        // Load profile from disk
        $profile = $this->loadSampleProfileFromDisk($filename);

        // Test that the profile can be installed
        $result = $this->installer->install($profile, 'all');

        // Verify the profile can be installed successfully
        $this->assertTrue($result, 'Category profile from disk should install successfully');

        // Verify the category was actually created
        $this->assertCategoryExists($uniqueCategory);

        // Clean up: remove the created category
        $this->cleanupCategory($uniqueCategory);
    }
}
