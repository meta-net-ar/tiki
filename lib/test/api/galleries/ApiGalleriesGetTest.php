<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Galleries;

/**
 * Tests for File Galleries API GET endpoints
 *
 * @group api-integration-test
 * @group api-galleries
 */
class ApiGalleriesGetTest extends ApiBaseGalleriesTest
{
    public function testApiGetGalleriesWithoutPermission()
    {
        $response = $this->makeApiRequest(
            'GET',
            '/galleries'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiGetGalleries()
    {
        $response = $this->makeApiRequest(
            'GET',
            '/galleries',
            'Admins'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidGalleryListResponse($body);
        $this->assertEquals('List Galleries', $body['title'], 'Offset should be 0');
        $this->assertEquals(1, $body['parentId'], 'parentId should be 1');
    }

    public function testApiGetGalleriesWithPagination()
    {
        $uid = uniqid();
        foreach (['A', 'B', 'C'] as $suffix) {
            static::createGallery('API_Test_Pagination_' . $suffix . '_' . $uid, 'Pagination test gallery');
        }
        $data = [
            'offset' => 0,
            'maxRecords' => 2,
        ];
        $response = $this->makeApiRequest(
            'GET',
            '/galleries',
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidGalleryListResponse($body);

        $this->assertEquals('List Galleries', $body['title'], 'Offset should be 0');
        $this->assertEquals(1, $body['parentId'], 'parentId should be 1');
        $this->assertEquals(0, $body['offset'], 'Offset should be 0');
        $this->assertEquals(2, count($body['result']), 'Should return at most 2 results');
        $this->assertEquals(2, $body['maxRecords'], 'maxRecords should be 2');
    }

    public function testApiGetGalleriesWithSorting()
    {
        $data = ['sort_mode' => 'name_asc'];
        $response = $this->makeApiRequest(
            'GET',
            '/galleries',
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidGalleryListResponse($body);

        // Verify galleries are sorted by name (if we have multiple)
        if (count($body['result']) > 1) {
            $names = array_column($body['result'], 'name');
            $sortedNames = $names;
            sort($sortedNames, SORT_NATURAL | SORT_FLAG_CASE);
            $this->assertEquals($sortedNames, $names, 'Galleries should be sorted by name ascending');
        }
    }

    public function testApiGetGalleriesWithSearch()
    {
        // create a gallery to search for
        $name = 'API_Test_Gallery_Search_' . uniqid();
        $galleryId = static::createGallery($name, 'Gallery created for API search testing');
        $this->assertNotEmpty($galleryId, 'The gallery to search for should have been created');

        $data = ['find' => $name];
        $response = $this->makeApiRequest(
            'GET',
            '/galleries',
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidGalleryListResponse($body);
        // Should return only galleries matching the search term (API_Test_Gallery_Search)
        $this->assertCount(1, $body['result'], 'Should return 1 matching gallery');
        $this->assertEquals($name, $body['result'][0]['name'], 'Gallery name should match the search term');
    }

    public function testApiGetGalleryInfoWithoutPermission()
    {
        $galleryId = static::$defaultGalleries['default']['galleryId'];
        $response = $this->makeApiRequest(
            'GET',
            "/galleries/{$galleryId}"
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiGetGalleryInfo()
    {
        $galleryId = static::$defaultGalleries['default']['galleryId'];
        $response = $this->makeApiRequest(
            'GET',
            "/galleries/{$galleryId}",
            'Admins'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidGalleryResponse($body);

        $this->assertEquals($galleryId, $body['galleryId'], 'Should return the requested gallery');
        $this->assertEquals('API_Test_Gallery_Default', $body['name'], 'Gallery name should match');
    }

    public function testApiGetNonExistentGalleryInfo()
    {
        $response = $this->makeApiRequest(
            'GET',
            '/galleries/999999',
            'Admins'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiGetGalleryFilesWithoutPermission()
    {
        $galleryId = static::$defaultGalleries['default']['galleryId'];
        $response = $this->makeApiRequest(
            'GET',
            "/galleries/{$galleryId}/list_files"
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiGetGalleryFiles()
    {
        $galleryId = static::$defaultGalleries['default']['galleryId'];
        $response = $this->makeApiRequest(
            'GET',
            "/galleries/{$galleryId}/list_files",
            'Admins'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidFileListResponse($body);

        $this->assertEquals($galleryId, $body['galleryId'], 'Should return files for the requested gallery');

        // Verify our default file is in the list
        $foundDefault = false;
        foreach ($body['result'] as $file) {
            if ($file['filename'] === 'test_file.txt') {
                $foundDefault = true;
                break;
            }
        }
        $this->assertTrue($foundDefault, 'Default test file should be in the list');
    }

    public function testApiGetGalleryFilesKeepsExtractedContentByDefault()
    {
        $galleryId = static::$defaultGalleries['default']['galleryId'];
        $response = $this->makeApiRequest(
            'GET',
            "/galleries/{$galleryId}/list_files",
            'Admins'
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidFileListResponse($body);
        $this->assertNotEmpty($body['result'], 'The gallery should list at least one file');

        foreach ($body['result'] as $file) {
            $this->assertArrayHasKey('metadata', $file);
            $this->assertArrayHasKey('search_data', $file);
        }
    }

    public function testApiGetGalleryFilesOmitsExtractedContentWhenRequested()
    {
        $galleryId = static::$defaultGalleries['default']['galleryId'];
        $response = $this->makeApiRequest(
            'GET',
            "/galleries/{$galleryId}/list_files",
            'Admins',
            ['omit_metadata' => 1]
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidFileListResponse($body);
        $this->assertNotEmpty($body['result'], 'The gallery should list at least one file');

        foreach ($body['result'] as $file) {
            $this->assertArrayNotHasKey('metadata', $file);
            $this->assertArrayNotHasKey('search_data', $file);
            $this->assertArrayNotHasKey('ocr_data', $file);
            $this->assertArrayHasKey('fileId', $file, 'The regular fields should still be present');
            $this->assertArrayHasKey('filename', $file, 'The regular fields should still be present');
        }
    }

    public function testApiGetNonExistentGalleryFiles()
    {
        $response = $this->makeApiRequest(
            'GET',
            '/galleries/999999/list_files',
            'Admins'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiGetGalleryFilesWithPagination()
    {
        $galleryId = static::$defaultGalleries['default']['galleryId'];
        // Add more files to ensure we have enough for pagination test
        for ($i = 0; $i < 5; $i++) {
            static::createFile(
                $galleryId,
                "test_file_{$i}.txt",
                "Test File {$i}",
                "This is test file number {$i} for API testing",
                'text/plain',
                "Test content for file number {$i} in galleries API"
            );
        }

        $data = [
            'offset' => 0,
            'maxRecords' => 2,
        ];
        $response = $this->makeApiRequest(
            'GET',
            "/galleries/{$galleryId}/list_files",
            'Admins',
            $data
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidFileListResponse($body);

        $this->assertEquals('List files', $body['title'], 'Offset should be 0');
        $this->assertEquals(2, count($body['result']), 'Should return at most 2 results');
        $this->assertEquals($galleryId, $body['galleryId'], 'galleryId should be 1');
        $this->assertEquals(0, $body['offset'], 'Offset should be 0');
        $this->assertEquals(2, $body['maxRecords'], 'maxRecords should be 2');
    }

    public function testApiGetGalleryFilesWithSorting()
    {
        $galleryId = static::createGallery('API_Test_Gallery_Sorting', 'Gallery for sorting test');
        $this->assertNotEmpty($galleryId, 'Sorting test gallery should be created');

        static::createFile($galleryId, 'cherry.txt', 'Cherry File', 'Cherry file for sorting test', 'text/plain', 'Cherry content');
        static::createFile($galleryId, 'apple.txt', 'Apple File', 'Apple file for sorting test', 'text/plain', 'Apple content');
        static::createFile($galleryId, 'banana.txt', 'Banana File', 'Banana file for sorting test', 'text/plain', 'Banana content');

        $response = $this->makeApiRequest(
            'GET',
            "/galleries/{$galleryId}/list_files",
            'Admins',
            ['sort_mode' => 'filename_asc']
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidFileListResponse($body);

        $filenames = array_column($body['result'], 'filename');
        $this->assertEquals(
            ['apple.txt', 'banana.txt', 'cherry.txt'],
            $filenames,
            'Files should be sorted by filename ascending'
        );
    }

    public function testApiGetGalleryFilesWithSearch()
    {
        $galleryId = static::$defaultGalleries['default']['galleryId'];

        $data = ['find' => 'Test File'];
        $response = $this->makeApiRequest(
            'GET',
            "/galleries/{$galleryId}/list_files",
            'Admins',
            $data
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidFileListResponse($body);

        // should return only files matching the search term
        $this->assertGreaterThanOrEqual(1, count($body['result']), 'Should return at least 1 matching file');
        foreach ($body['result'] as $file) {
            $this->assertStringContainsString('Test File', $file['name'], 'File name should contain search term');
        }
    }

    public function testApiGetFileInfoWithoutPermission()
    {
        $fileId = static::$defaultFiles['default']['fileId'];
        $response = $this->makeApiRequest(
            'GET',
            "/galleries/files/{$fileId}"
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiGetFileInfo()
    {
        $fileId = static::$defaultFiles['default']['fileId'];
        $response = $this->makeApiRequest(
            'GET',
            "/galleries/files/{$fileId}",
            'Admins'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidFileResponse($body);

        $this->assertEquals($fileId, $body['fileId'], 'Should return the requested file info');
        $this->assertEquals('test_file.txt', $body['info']['filename'], 'File name should match');
        $this->assertEquals('Test File', $body['info']['name'], 'File name should match');
        $this->assertEquals('This is a test file for API testing', $body['info']['description'], 'File description should match');
    }

    public function testApiGetNonExistentFileInfo()
    {
        $response = $this->makeApiRequest(
            'GET',
            '/galleries/files/999999',
            'Admins'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Requested file does not exist (404)');
    }
}
