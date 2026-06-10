<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Galleries;

/**
 * Tests for File Galleries API POST endpoints
 *
 * @group api-integration-test
 * @group api-galleries
 */
class ApiGalleriesPostTest extends ApiBaseGalleriesTest
{
    public function testApiCreateGalleryWithoutPermission()
    {
        $data = [
            'name' => 'API_Test_Gallery_POST_New',
            'description' => 'Created via POST test',
            'type' => 'default'
        ];
        $response = $this->makeApiRequest(
            'POST',
            '/galleries',
            null, // Anonymous user
            $data,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiCreateGallery()
    {
        $data = [
            'name' => 'API_Test_Gallery_POST_New',
            'description' => 'Created via POST test',
            'type' => 'default'
        ];
        $response = $this->makeApiRequest(
            'POST',
            '/galleries',
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidGalleryInfoResponse($body);
        $this->assertEquals('API_Test_Gallery_POST_New', $body['info']['name'], 'Gallery name should match');
        $this->assertEquals('default', $body['info']['type'], 'Gallery type should be default');
    }

    public function testApiUpdateGalleryWithoutPermission()
    {
        $galleryId = static::createGallery('API_Test_Gallery_POST_To_Update', 'Gallery to be updated via POST test');
        $data = [
            'name' => 'API_Test_Gallery_POST_Updated',
            'description' => 'Updated via POST test',
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/{$galleryId}/update",
            null, // Anonymous user
            $data,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiUpdateGallery()
    {
        $galleryId = static::createGallery('API_Test_Gallery_POST_To_Update', 'Gallery to be updated via POST test');
        $data = [
            'name' => 'API_Test_Gallery_POST_Updated',
            'description' => 'Updated via POST test',
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/{$galleryId}/update",
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidGalleryInfoResponse($body);
        $this->assertEquals('API_Test_Gallery_POST_Updated', $body['info']['name'], 'Gallery name should match');
    }

    public function testApiUpdateNonExistentGallery()
    {
        $data = [
            'name' => 'API_Test_Gallery_POST_Updated',
            'description' => 'Updated via POST test',
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/99999/update",
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiCreateChildGallery()
    {
        $parentId = static::$defaultGalleries['default']['galleryId'];

        $data = [
            'name' => 'API_Test_Gallery_POST_Child',
            'description' => 'Child gallery',
            'parentId' => $parentId
        ];
        $response = $this->makeApiRequest(
            'POST',
            '/galleries',
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidGalleryInfoResponse($body);
        $this->assertEquals('API_Test_Gallery_POST_Child', $body['info']['name'], 'Gallery name should match');
        $this->assertEquals('default', $body['info']['type'], 'Gallery type should be default');
        $this->assertEquals($parentId, $body['info']['parentId'], 'Parent ID should match');
    }

    public function testApiCreateGalleryWithoutName()
    {
        // missing 'name' field
        $data = [
            'description' => 'Missing name'
        ];
        $response = $this->makeApiRequest(
            'POST',
            '/galleries',
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 409, '<!--field[name]-->Field Required');
    }

    public function testApiUpdateFile()
    {
        $galleryId = static::$defaultGalleries['default']['galleryId'];
        $existingFileId = static::createFile(
            $galleryId,
            'test_update_file.txt',
            'Test Update File',
            'This is a test update file for API testing',
            'text/plain',
            'Test content for file galleries API'
        );

        $filename = 'testdata.png';
        $filepath = __DIR__ . '/assets/' . $filename;
        $data = [
            'galleryId' => $galleryId,
            'fileId' => $existingFileId, // Indicates update
            'name' => $filename,
            'title' => 'POST Test Upload',
            'description' => 'File uploaded via POST test',
            'data' => $filepath,
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/galleries/upload',
            'Admins',
            $data,
            'multipart/form-data'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidFileUploadResponse($body);
        $this->assertEquals($galleryId, $body['galleryId'], 'Gallery ID should match');
        $this->assertEquals($filename, $body['name'], 'Filename should match');
        $this->assertEquals('POST Test Upload', $body['title'], 'Title should match');
    }

    public function testApiDuplicateFileWithoutPermission()
    {
        $defaultFileId = static::$defaultFiles['default']['fileId'];
        $data = [
            'newName' => 'API_Test_File_POST_Duplicate',
            'description' => 'Duplicated via POST test',
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/files/{$defaultFileId}/duplicate",
            null, // Anonymous user
            $data,
            'application/x-www-form-urlencoded'
        );
        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiDuplicateFile()
    {
        $defaultFileId = static::$defaultFiles['default']['fileId'];
        $data = [
            'newName' => 'API_Test_File_POST_Duplicate',
            'description' => 'Duplicated via POST test',
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/files/{$defaultFileId}/duplicate",
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertEquals('Duplicate file', $body['title'], 'API duplicate file response title should match');
        $this->assertEquals('File duplicated successfully', $body['message'], 'API duplicate file response message should match');
        $this->assertArrayHasKey('id', $body, 'Response should contain new file id');
        $this->assertNotEquals($defaultFileId, $body['id'], 'New file ID should be different from original');
    }

    public function testApiDuplicateNonExistentFile()
    {
        $data = [
            'newName' => 'API_Test_File_POST_Duplicate',
            'description' => 'Duplicated via POST test',
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/files/99999/duplicate",
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiUploadFile()
    {
        $galleryId = static::$defaultGalleries['default']['galleryId'];
        $filename = 'testdata.png';
        $filepath = __DIR__ . '/assets/' . $filename;
        $data = [
            'galleryId' => $galleryId,
            'name' => $filename,
            'title' => 'POST Test Upload',
            'description' => 'File uploaded via POST test',
            'data' => $filepath,
        ];

        $response = $this->makeApiRequest(
            'POST',
            '/galleries/upload',
            'Admins',
            $data,
            'multipart/form-data'
        );

        $this->assertResponseStatus(200, $response, 'POST /galleries/upload');

        $body = $this->getResponseBody($response);
        $this->assertValidFileUploadResponse($body);
        $this->assertEquals($galleryId, $body['galleryId'], 'Gallery ID should match');
        $this->assertEquals($filename, $body['name'], 'Filename should match');
        $this->assertEquals('POST Test Upload', $body['title'], 'Title should match');
    }

    public function testApiUploadFileWithoutData()
    {
        $galleryId = static::$defaultGalleries['default']['galleryId'];
        $data = [
            'galleryId' => $galleryId,
            'name' => 'no_data.txt'
            // Missing data
        ];
        $response = $this->makeApiRequest(
            'POST',
            '/galleries/upload',
            'Admins',
            $data,
            'multipart/form-data'
        );

        // Should return error
        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 406);
    }

    public function testApiUploadFileToNonExistentGallery()
    {
        $filename = 'testdata.png';
        $filepath = __DIR__ . '/assets/' . $filename;
        $data = [
            'galleryId' => 99999,
            'name' => $filename,
            'title' => 'POST Test Upload',
            'description' => 'File uploaded via POST test',
            'data' => $filepath,
        ];
        $response = $this->makeApiRequest(
            'POST',
            '/galleries/upload',
            'Admins',
            $data,
            'multipart/form-data'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Requested gallery does not exist. (404)');
    }

    public function testApiLockFilesWithoutPermission()
    {
        $galleryId = static::$defaultGalleries['default']['galleryId'];
        $fileId = static::createFile(
            $galleryId,
            'test_lock_file.txt',
            'Test Lock File',
            'This is a test lock file for API testing',
            'text/plain',
            'Test content for file galleries API'
        );

        $data = [
            'items' => [$fileId]
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/files/lock",
            null, // Anonymous user
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidLockFileResponse($body);
        $this->assertEquals('Lock files', $body['title'], 'API lock file response title should match');
        $this->assertEquals(0, $body['count'], 'No files should be locked without permission');
        $this->assertEquals([], $body['locked'], 'Locked items should match');
    }

    public function testApiLockFiles()
    {
        $galleryId = static::$defaultGalleries['default']['galleryId'];
        $fileId1 = static::createFile(
            $galleryId,
            'test_lock_file.txt',
            'Test Lock File',
            'This is a test lock file for API testing',
            'text/plain',
            'Test content for file galleries API'
        );
        $fileId2 = static::createFile(
            $galleryId,
            'test_lock_file_2.txt',
            'Test Lock File 2',
            'This is another test lock file for API testing',
            'text/plain',
            'Test content for file galleries API'
        );

        $data = [
            'items' => [$fileId1, $fileId2]
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/files/lock",
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidLockFileResponse($body);
        $this->assertEquals('Lock files', $body['title'], 'API lock file response title should match');
        $this->assertEquals([$fileId1, $fileId2], $body['locked'], 'Locked items should match');
    }

    public function testApiLockNonExistentFiles()
    {
        $data = [
            'items' => [99999, 88888]
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/files/lock",
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidLockFileResponse($body);
        $this->assertEquals('Lock files', $body['title'], 'API lock file response title should match');
        $this->assertEquals(0, $body['count'], 'No files should be locked');
        $this->assertEquals([], $body['locked'], 'Locked items should match');
    }

    public function testApiLockFilesMissingParameters()
    {
        $data = [
            // 'items' missing
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/files/lock",
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );
        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 409, '<!--field[items]-->Field Required');
    }

    // unlock tests would be similar
    public function testApiUnlockFilesWithoutPermission()
    {
        $galleryId = static::$defaultGalleries['default']['galleryId'];
        $fileId = static::createFile(
            $galleryId,
            'test_unlock_file.txt',
            'Test Unlock File',
            'This is a test unlock file for API testing',
            'text/plain',
            'Test content for file galleries API'
        );

        $data = [
            'items' => [$fileId]
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/files/unlock",
            null, // Anonymous user
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidUnlockFileResponse($body);
        $this->assertEquals('Unlock files', $body['title'], 'API unlock file response title should match');
        $this->assertEquals(0, $body['count'], 'No files should be unlocked without permission');
        $this->assertEquals([], $body['unlocked'], 'Unlocked items should match');
    }

    public function testApiUnlockFiles()
    {
        $galleryId = static::$defaultGalleries['default']['galleryId'];
        $fileId1 = static::createFile(
            $galleryId,
            'test_unlock_file.txt',
            'Test Unlock File',
            'This is a test unlock file for API testing',
            'text/plain',
            'Test content for file galleries API'
        );
        $fileId2 = static::createFile(
            $galleryId,
            'test_unlock_file_2.txt',
            'Test Unlock File 2',
            'This is another test unlock file for API testing',
            'text/plain',
            'Test content for file galleries API'
        );

        $data = [
            'items' => [$fileId1, $fileId2]
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/files/unlock",
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidUnlockFileResponse($body);
        $this->assertEquals('Unlock files', $body['title'], 'API unlock file response title should match');
        $this->assertEquals([$fileId1, $fileId2], $body['unlocked'], 'Unlocked items should match');
    }

    public function testApiUnlockNonExistentFiles()
    {
        $data = [
            'items' => [99999, 88888]
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/files/unlock",
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidUnlockFileResponse($body);
        $this->assertEquals('Unlock files', $body['title'], 'API unlock file response title should match');
        $this->assertEquals(0, $body['count'], 'No files should be unlocked');
        $this->assertEquals([], $body['unlocked'], 'Unlocked items should match');
    }

    public function testApiUnlockFilesMissingParameters()
    {
        $data = [
            // 'items' missing
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/files/unlock",
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );
        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 409, '<!--field[items]-->Field Required');
    }

    public function testApiMoveGalleryWithoutPermission()
    {
        $parentGalleryId = static::createGallery('API_Test_Gallery_POST_Move_Parent', 'Parent gallery for move test');
        $childGalleryId = static::createGallery('API_Test_Gallery_POST_Move_Child', 'Child gallery to be moved');
        $data = [
            'newParentId' => $parentGalleryId
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/{$childGalleryId}/move",
            null, // Anonymous user
            $data,
            'application/x-www-form-urlencoded'
        );
        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiMoveGallery()
    {
        $parentGalleryId = static::createGallery('API_Test_Gallery_POST_Move_Parent', 'Parent gallery for move test');
        $childGalleryId = static::createGallery('API_Test_Gallery_POST_Move_Child', 'Child gallery to be moved');

        $data = [
            'newParentId' => $parentGalleryId
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/{$childGalleryId}/move",
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertEquals("Move File Gallery", $body['title'], 'API move gallery response title should match');
        $this->assertEquals("The file gallery $childGalleryId has been moved", $body['message'], 'API move gallery response message should match');
    }

    public function testApiMoveNonExistentGallery()
    {
        $parentGalleryId = static::createGallery('API_Test_Gallery_POST_Move_Parent', 'Parent gallery for move test');
        $data = [
            'newParentId' => $parentGalleryId
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/99999/move",
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiMoveGalleryMissingParameters()
    {
        $childGalleryId = static::createGallery('API_Test_Gallery_POST_Move_Child', 'Child gallery to be moved');
        $data = [
            // 'newParentId' missing
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/{$childGalleryId}/move",
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );
        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 409, '<!--field[newParentId]-->Field Required');
    }

    public function testApiDuplicateGalleryWithoutPermission()
    {
        $defaultGalleryId = static::$defaultGalleries['default']['galleryId'];
        $data = [
            'name' => 'API_Test_Gallery_POST_Duplicate',
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/{$defaultGalleryId}/duplicate",
            null, // Anonymous user
            $data,
            'application/x-www-form-urlencoded'
        );
        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiDuplicateGallery()
    {
        $defaultGalleryId = static::$defaultGalleries['default']['galleryId'];
        $data = [
            'name' => 'API_Test_Gallery_POST_Duplicate',
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/{$defaultGalleryId}/duplicate",
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );
        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $this->assertEquals('Duplicate File Gallery', $body['title'], 'API duplicate gallery response title should match');
        $this->assertEquals('File Gallery duplicated successfully', $body['message'], 'API duplicate gallery response message should match');
        $this->assertArrayHasKey('id', $body, 'Response should contain new gallery id');
        $this->assertNotEquals($defaultGalleryId, $body['id'], 'New gallery ID should be different from original');
    }

    public function testApiDuplicateNonExistentGallery()
    {
        $data = [
            'name' => 'API_Test_Gallery_POST_Duplicate',
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/99999/duplicate",
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiDuplicateGalleryMissingParameters()
    {
        $defaultGalleryId = static::$defaultGalleries['default']['galleryId'];
        $data = [
            // 'name' missing
        ];
        $response = $this->makeApiRequest(
            'POST',
            "/galleries/{$defaultGalleryId}/duplicate",
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );
        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 409, '<!--field[name]-->Field Required');
    }
}
