<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Tiki\Profile;

use Tiki_Profile;

/**
 * Test WikiPage handler basic functionality
 *
 * Tests the Tiki_Profile_InstallHandler_WikiPage functionality
 * to ensure wiki page creation and management works correctly.
 */
class WikiPageHandlerTest extends AbstractProfilesTestCase
{
    /**
     * Test WikiPage handler basic functionality
     *
     * Validates that the Tiki_Profile_InstallHandler_WikiPage can create
     * wiki pages with basic content and metadata.
     */
    public function testWikiPageHandlerInterface()
    {
        $uniquePage = $this->createUniqueTestPage();

        $profileYaml = [
            'profile' => [
                'name' => 'Test WikiPage Profile',
                'description' => 'Test profile for wiki page handler validation'
            ],
            'objects' => [
                'wiki_page' => [
                    $uniquePage => [
                        'name' => $uniquePage,
                        'content' => 'This is a test wiki page created by profile installation.',
                        'description' => 'Test page description'
                    ]
                ]
            ]
        ];

        $profileContent = $this->createProfileContent($profileYaml);
        $profile = Tiki_Profile::fromString($profileContent, 'TestWikiPageProfile_' . uniqid());

        // Test that profile can be created without errors
        $this->assertInstanceOf('Tiki_Profile', $profile);

        // Test that the profile can be installed
        $result = $this->installer->install($profile, 'all');

        // Verify the profile can be installed successfully
        $this->assertTrue($result, 'WikiPage profile should install successfully');

        // Verify the page was actually created
        $this->assertWikiPageExists($uniquePage);

        // Clean up: remove the created page
        $this->cleanupWikiPage($uniquePage);
    }

    /**
     * Test WikiPage handler with complex data
     *
     * Validates that the Tiki_Profile_InstallHandler_WikiPage can create
     * wiki pages with complex content including formatting and categories.
     */
    public function testWikiPageHandlerWithComplexData()
    {
        $uniquePage = $this->createUniqueTestPage();

        $profileYaml = [
            'profile' => [
                'name' => 'Complex WikiPage Profile',
                'description' => 'Test profile with complex wiki page data'
            ],
            'objects' => [
                'wiki_page' => [
                    $uniquePage => [
                        'name' => $uniquePage,
                        'content' => 'This is a complex test wiki page with **bold text** and *italic text*.',
                        'description' => 'Complex test page description',
                        'keywords' => 'test, profile, wiki',
                        'categories' => ['Test Category']
                    ]
                ]
            ]
        ];

        $profileContent = $this->createProfileContent($profileYaml);
        $profile = Tiki_Profile::fromString($profileContent, 'TestWikiPageProfile_' . uniqid());

        // Test that the profile can be installed
        $result = $this->installer->install($profile, 'all');
        $this->assertTrue($result, 'Complex WikiPage profile should install successfully');

        // Verify the page was actually created
        $this->assertWikiPageExists($uniquePage);

        // Clean up: remove the created page
        $this->cleanupWikiPage($uniquePage);
    }

    /**
     * Test loading WikiPage profile from disk
     *
     * Validates that wiki page profiles can be loaded from disk files
     * and installed successfully.
     */
    public function testLoadWikiPageProfileFromDisk()
    {
        $uniquePage = $this->createUniqueTestPage();

        $profileData = [
            'profile' => [
                'name' => 'Disk WikiPage Profile',
                'description' => 'Test profile loaded from disk'
            ],
            'objects' => [
                'wiki_page' => [
                    $uniquePage => [
                        'name' => $uniquePage,
                        'content' => 'This is a test wiki page loaded from disk.',
                        'description' => 'Disk-loaded test page'
                    ]
                ]
            ]
        ];

        // Create sample profile file
        $filename = 'wikipage_profile.yml';
        $this->createRawYamlProfileFile($filename, $profileData);

        // Load profile from disk
        $profile = $this->loadSampleProfileFromDisk($filename);

        // Test that the profile can be installed
        $result = $this->installer->install($profile, 'all');
        $this->assertTrue($result, 'WikiPage profile from disk should install successfully');

        // Verify the page was actually created
        $this->assertWikiPageExists($uniquePage);

        // Clean up: remove the created page
        $this->cleanupWikiPage($uniquePage);
    }
}
