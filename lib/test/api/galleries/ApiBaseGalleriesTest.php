<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Galleries;

use Tiki\Lib\Test\Api\ApiSchemaLoader;
use Tiki\Lib\Test\Api\ApiTestCase;
use TikiLib;
use Exception;

/**
 * Base class for File Galleries API integration tests
 * Provides common setup, teardown, and helper methods
 *
 * @group api-integration-test
 */
abstract class ApiBaseGalleriesTest extends ApiTestCase
{
    /**
     * Array to track test gallery IDs for cleanup
     * @var array
     */
    protected static $testGalleries = [];

    /**
     * Array to track test file IDs for cleanup
     * @var array
     */
    protected static $testFiles = [];

    /**
     * Default galleries created for testing
     * @var array
     */
    protected static $defaultGalleries = [];

    /**
     * Default files created for testing
     * @var array
     */
    protected static $defaultFiles = [];

    /**
     * Root gallery ID from preferences
     * @var int
     */
    protected static $rootGalleryId;

     /**
     * Preferences to enable required features
     * @var array
     */
    private static $preferences = [
        'feature_file_galleries' => 'y',
        'feature_file_galleries_comments' => 'y',
        'feature_categories' => 'y',
        'fgal_use_db' => 'y', // Store files in database for testing
        'fgal_use_dir' => ''   // Don't use file system storage
    ];

    /**
     * Setup before class - enable features and create default test galleries
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::setTestPreferences(self::$preferences);

        global $prefs;
        // Get root gallery ID
        static::$rootGalleryId = $prefs['fgal_root_id'];

        // Create default test galleries and files
        static::createDefaultTestGalleries();
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
     * Create default test galleries for all tests
     */
    protected static function createDefaultTestGalleries()
    {
        // Create a default test gallery
        $defaultGalleryId = static::createGallery('API_Test_Gallery_Default', 'Default test gallery');
        if ($defaultGalleryId) {
            static::$defaultGalleries['default'] = [
                'galleryId' => $defaultGalleryId,
                'name' => 'API_Test_Gallery_Default'
            ];

            // Create a default file in the gallery
            $defaultFileId = static::createFile(
                $defaultGalleryId,
                'test_file.txt',
                'Test File',
                'This is a test file for API testing',
                'text/plain',
                'Test content for file galleries API'
            );

            if ($defaultFileId) {
                static::$defaultFiles['default'] = [
                    'fileId' => $defaultFileId,
                    'filename' => 'test_file.txt',
                    'galleryId' => $defaultGalleryId
                ];
            }
        }

        // Create a podcast gallery
        $podcastGalleryId = static::createGallery('API_Test_Gallery_Podcast', 'Podcast test gallery', 'podcast');
        if ($podcastGalleryId) {
            static::$defaultGalleries['podcast'] = [
                'galleryId' => $podcastGalleryId,
                'name' => 'API_Test_Gallery_Podcast'
            ];
        }

        // Create a child gallery
        $childGalleryId = static::createGallery('API_Test_Gallery_Child', 'Child test gallery', 'default', $defaultGalleryId);
        if ($childGalleryId) {
            static::$defaultGalleries['child'] = [
                'galleryId' => $childGalleryId,
                'name' => 'API_Test_Gallery_Child',
                'parentId' => $defaultGalleryId
            ];
        }
    }

    /**
     * Create a file gallery for testing
     * @param string $name Gallery name
     * @param string $description Gallery description
     * @param string $type Gallery type (default, podcast, vidcast)
     * @param int|null $parentId Parent gallery ID
     * @return int|null The gallery ID or null on failure
     */
    protected static function createGallery($name, $description = '', $type = 'default', $parentId = null)
    {
        $filegallib = TikiLib::lib('filegal');

        if ($parentId === null) {
            $parentId = static::$rootGalleryId;
        }

        $galleryId = $filegallib->replace_file_gallery([
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'visible' => 'y',
            'parentId' => $parentId,
            'user' => 'admin'
        ]);

        if ($galleryId) {
            static::$testGalleries[] = $galleryId;
        }

        return $galleryId;
    }

    /**
     * Create a file for testing
     * @param int $galleryId Gallery ID
     * @param string $filename Filename
     * @param string $name File name/title
     * @param string $description File description
     * @param string $type MIME type
     * @param string $data File content
     * @return int|null The file ID or null on failure
     */
    protected static function createFile($galleryId, $filename, $name, $description, $type, $data)
    {
        $filegallib = TikiLib::lib('filegal');

        // Get gallery info for upload_single_file
        $gal_info = $filegallib->get_file_gallery_info($galleryId);
        if (! $gal_info) {
            return null;
        }

        $size = strlen($data);
        $fileId = $filegallib->upload_single_file(
            $gal_info,
            $filename,
            $size,
            $type,
            $data,
            'admin',      // user
            null,         // image_x
            null,         // image_y
            $description,
            '',           // created (empty = current time)
            $name         // title
        );

        if ($fileId) {
            static::$testFiles[] = $fileId;
        }

        return $fileId ?: null;
    }

    /**
     * Cleanup all test data
     */
    protected static function cleanupTestData()
    {
        $filegallib = TikiLib::lib('filegal');

        // Remove all test files
        foreach (static::$testFiles as $fileId) {
            try {
                $fileInfo = $filegallib->get_file_info($fileId);
                if ($fileInfo) {
                    $filegallib->remove_file($fileInfo);
                }
            } catch (Exception $e) {
                // Ignore errors during cleanup
            }
        }

        // Remove all test galleries
        $galleries = static::$testGalleries;
        foreach ($galleries as $galleryId) {
            try {
                $filegallib->remove_file_gallery($galleryId, static::$rootGalleryId, true);
            } catch (Exception $e) {
                // Ignore errors during cleanup
            }
        }

        static::deleteTestPreferences(self::$preferences);
    }

    protected function getGalleriesListResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('GalleryListResponse.yaml');
    }

    protected function getFilesListResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('FileListResponse.yaml');
    }

    protected function getFileResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('FileInfoResponse.yaml');
    }

    protected function getGalleryResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('Gallery.yaml');
    }

    protected function getInfoGalleriesResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('GalleryInfoResponse.yaml');
    }

    protected function getFileUploadResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('FileUploadResponse.yaml');
    }

    protected function getLockFileResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('LockFileResponse.yaml');
    }

    protected function getUnlockFileResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('UnlockFileResponse.yaml');
    }

    protected function getDeleteFileGalleryResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('DeleteFileGalleryResponse.yaml');
    }

    protected function assertValidGalleryResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getGalleryResponseSchema());
    }

    protected function assertValidGalleryListResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getGalleriesListResponseSchema());
    }

    protected function assertValidGalleryInfoResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getInfoGalleriesResponseSchema());
    }

    protected function assertValidFileUploadResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getFileUploadResponseSchema());
    }

    protected function assertValidFileListResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getFilesListResponseSchema());
    }

    protected function assertValidFileResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getFileResponseSchema());
    }

    public function assertValidLockFileResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getLockFileResponseSchema());
    }

    public function assertValidUnlockFileResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getUnlockFileResponseSchema());
    }

    public function assertValidDeleteFileGalleryResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getDeleteFileGalleryResponseSchema());
    }
}
