<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Tiki\Profile;

use PHPUnit\Framework\TestCase;
use Tiki_Profile;
use Tiki_Profile_Installer;
use Exception;
use TikiLib;
use Symfony\Component\Yaml\Yaml;

/**
 * Base test case for Profile-related tests
 *
 * Provides common functionality for testing Tiki Profile installation
 * and validation. This base class reduces code duplication across
 * different profile handler tests.
 */
abstract class AbstractProfilesTestCase extends TestCase
{
    protected $installer;
    protected $testProfilesDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->installer = new Tiki_Profile_Installer();

        // Set up $_SERVER variables that might be needed by TikiLib functions
        if (! isset($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = '/tiki-index.php';
        }

        if (! isset($_SERVER['SERVER_NAME'])) {
            $_SERVER['SERVER_NAME'] = 'localhost';
        }

        // Use Tiki temp directory
        $this->testProfilesDir = TEMP_PATH . '/tiki-profile-tests-' . uniqid();

        // Create test profiles directory if it doesn't exist
        if (! is_dir($this->testProfilesDir)) {
            mkdir($this->testProfilesDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test profiles directory
        if (is_dir($this->testProfilesDir)) {
            $this->removeDirectory($this->testProfilesDir);
        }
        parent::tearDown();
    }

    /**
     * Create profile content from YAML array using Tiki's built-in YAML generation
     *
     * @param array $profileData The profile data array
     * @param bool $rawYaml If true, returns raw YAML; if false, wraps in {CODE} format
     * @return string The formatted profile content
     */
    protected function createProfileContent(array $profileData, bool $rawYaml = false): string
    {
        // Convert objects to the array format expected by handlers
        if (isset($profileData['objects'])) {
            $convertedObjects = [];
            foreach ($profileData['objects'] as $objectType => $objects) {
                foreach ($objects as $objectName => $objectData) {
                    $convertedObjects[] = [
                        'type' => $objectType,
                        'data' => $objectData
                    ];
                }
            }
            $profileData['objects'] = $convertedObjects;
        }

        // Use Tiki's built-in YAML generation with proper formatting
        $yaml = Yaml::dump($profileData, 20, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

        if ($rawYaml) {
            return $yaml;
        }

        // Wrap in the proper wiki plugin format that Tiki_Profile expects
        return "{CODE(caption=\"YAML\")}\n$yaml{CODE}";
    }

    /**
     * Create a unique test user to avoid conflicts
     */
    protected function createUniqueTestUser(): string
    {
        return 'testuser_' . uniqid() . '_' . mt_rand(1000, 9999);
    }

    /**
     * Create a unique test group to avoid conflicts
     */
    protected function createUniqueTestGroup(): string
    {
        return 'testgroup_' . uniqid() . '_' . mt_rand(1000, 9999);
    }

    /**
     * Create a test group
     */
    protected function createTestGroup(string $groupName): void
    {
        $userlib = TikiLib::lib('user');
        $userlib->add_group($groupName, 'Test group for profile testing');
    }

    /**
     * Create a unique test page name to avoid conflicts
     */
    protected function createUniqueTestPage(): string
    {
        return 'TestPage_' . uniqid() . '_' . mt_rand(1000, 9999);
    }

    /**
     * Install a profile and return the result
     */
    protected function installProfile(Tiki_Profile $profile, string $emptyCache = 'all'): bool
    {
        return $this->installer->install($profile, $emptyCache);
    }

    /**
     * Recursively remove directory and all contents
     */
    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /**
     * Load a sample profile from disk using Tiki_Profile::fromFile
     * This properly tests disk loading as used by the actual profile installer
     */
    protected function loadSampleProfileFromDisk(string $filename): Tiki_Profile
    {
        $profileName = basename($filename, '.yml') . '_' . uniqid();

        // Tiki_Profile::fromFile expects raw YAML content
        $profilePath = $this->testProfilesDir . '/' . $filename;

        if (! file_exists($profilePath)) {
            throw new Exception("Sample profile file not found: $profilePath");
        }

        // Copy to the expected filename for fromFile
        $newPath = $this->testProfilesDir . '/' . $profileName . '.yml';
        copy($profilePath, $newPath);

        $profile = Tiki_Profile::fromFile($this->testProfilesDir, $profileName);

        // Clean up the temporary file
        unlink($newPath);

        return $profile;
    }

    /**
     * Create a sample profile file on disk
     */
    protected function createSampleProfileFile(string $filename, array $profileData): string
    {
        $profilePath = $this->testProfilesDir . '/' . $filename;
        $content = $this->createProfileContent($profileData);
        file_put_contents($profilePath, $content);
        return $profilePath;
    }

    /**
     * Create a raw YAML profile file on disk (for use with Tiki_Profile::fromFile)
     */
    protected function createRawYamlProfileFile(string $filename, array $profileData): string
    {
        $profilePath = $this->testProfilesDir . '/' . $filename;
        $content = $this->createProfileContent($profileData, true); // raw YAML
        file_put_contents($profilePath, $content);
        return $profilePath;
    }

    /**
     * Verify that a user exists in the system
     */
    protected function assertUserExists(string $username): void
    {
        $userlib = TikiLib::lib('user');
        $exists = $userlib->user_exists($username);
        $this->assertNotFalse($exists, "User '$username' should exist after profile installation");
        $this->assertGreaterThan(0, $exists, "User '$username' should have a valid user ID");
    }

    /**
     * Clean up a created user
     */
    protected function cleanupUser(string $username): void
    {
        $userlib = TikiLib::lib('user');
        if ($userlib->user_exists($username)) {
            $userlib->remove_user($username);
        }
    }

    /**
     * Verify that a wiki page exists in the system
     */
    protected function assertWikiPageExists(string $pageName): void
    {
        $wikilib = TikiLib::lib('wiki');
        $exists = $wikilib->page_exists($pageName);
        $this->assertNotFalse($exists, "Wiki page '$pageName' should exist after profile installation");
        $this->assertGreaterThan(0, $exists, "Wiki page '$pageName' should have a valid page ID");
    }

    /**
     * Clean up a created wiki page
     */
    protected function cleanupWikiPage(string $pageName): void
    {
        $tikilib = TikiLib::lib('tiki');
        if ($tikilib->page_exists($pageName)) {
            $tikilib->remove_all_versions($pageName, 'Cleanup from test');
        }
    }

    /**
     * Verify that a tracker exists in the system
     */
    protected function assertTrackerExists(string $trackerName): void
    {
        $trklib = TikiLib::lib('trk');
        $trackerId = $trklib->get_tracker_by_name($trackerName);
        $this->assertNotFalse($trackerId, "Tracker '$trackerName' should exist after profile installation");
        $this->assertGreaterThan(0, $trackerId, "Tracker '$trackerName' should have a valid tracker ID");
    }

    /**
     * Clean up a created tracker
     */
    protected function cleanupTracker(string $trackerName): void
    {
        $trklib = TikiLib::lib('trk');
        $trackerId = $trklib->get_tracker_by_name($trackerName);
        if ($trackerId && $trackerId > 0) {
            $trklib->remove_tracker($trackerId);
        }
    }

    /**
     * Verify that a category exists in the system
     */
    protected function assertCategoryExists(string $categoryName): void
    {
        $categlib = TikiLib::lib('categ');
        $categoryId = $categlib->get_category_id($categoryName);
        $this->assertNotFalse($categoryId, "Category '$categoryName' should exist after profile installation");
        $this->assertGreaterThan(0, $categoryId, "Category '$categoryName' should have a valid category ID");
    }

    /**
     * Clean up a created category
     */
    protected function cleanupCategory(string $categoryName): void
    {
        $categlib = TikiLib::lib('categ');
        $categoryId = $categlib->get_category_id($categoryName);
        if ($categoryId && $categoryId > 0) {
            $categlib->remove_category($categoryId);
        }
    }
}
