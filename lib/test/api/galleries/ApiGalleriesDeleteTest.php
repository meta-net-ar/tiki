<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Galleries;

use TikiLib;

/**
 * Tests for File Galleries API DELETE endpoints
 *
 * @group api-integration-test
 * @group api-galleries
 */
class ApiGalleriesDeleteTest extends ApiBaseGalleriesTest
{
    public function testApiDeleteGalleryWithoutPermission()
    {
        $galleryId = static::createGallery('API_Test_Gallery_DELETE_NoPerm', 'Gallery to be deleted without permission');
        $data = [
            'galleryId' => static::$rootGalleryId,
            'recurse' => true
        ];
        $response = $this->makeApiRequest(
            'DELETE',
            "/galleries/{$galleryId}/delete",
            null, // Anonymous user (no permission)
            $data
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');

        // the gallery should still exist
        $fileGallery = TikiLib::lib('filegal');
        $galleryInfo = $fileGallery->get_file_gallery_info($galleryId);
        $this->assertNotEmpty($galleryInfo, 'Gallery should still exist after failed deletion attempt');
    }

    public function testApiDeleteGallery()
    {
        $galleryId = static::createGallery('API_Test_Gallery_DELETE_ToRemove', 'Gallery to be deleted');
        $data = [
            'galleryId' => static::$rootGalleryId,
            'recurse' => true
        ];
        $response = $this->makeApiRequest(
            'DELETE',
            "/galleries/{$galleryId}/delete",
            'Admins',
            $data
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidDeleteFileGalleryResponse($body);
        $this->assertEquals('Delete File Gallery', $body['title'], 'Response title should match');
        $this->assertStringContainsString("The file gallery $galleryId has been deleted", $body['message'], 'Response message should indicate deletion');

        // check that the gallery no longer exists
        $fileGallery = TikiLib::lib('filegal');
        $galleryInfo = $fileGallery->get_file_gallery_info($galleryId);
        $this->assertEmpty($galleryInfo, 'Gallery should no longer exist after deletion');
    }

    public function testApiDeleteGalleryWithChildren()
    {
        // Create parent gallery
        $parentId = static::createGallery('API_Test_Gallery_DELETE_Parent', 'Parent gallery');

        // Create child gallery
        $childId = static::createGallery('API_Test_Gallery_DELETE_Child', 'Child gallery', 'default', $parentId);

        // create file in child gallery
        $fileId = static::createFile(
            $childId,
            'child_gallery_file.txt',
            'Child Gallery File',
            'File in child gallery',
            'text/plain',
            'Content of the file in child gallery'
        );

        $data = [
            'galleryId' => static::$rootGalleryId,
            'recurse' => true
        ];
        $response = $this->makeApiRequest(
            'DELETE',
            "/galleries/{$parentId}/delete",
            'Admins',
            $data
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidDeleteFileGalleryResponse($body);
        $this->assertStringContainsString("The file gallery $parentId has been deleted", $body['message'], 'Response message should indicate deletion');

        // Check that both parent and child galleries are deleted
        $fileGallery = TikiLib::lib('filegal');
        $parentInfo = $fileGallery->get_file_gallery_info($parentId);
        $childInfo = $fileGallery->get_file_gallery_info($childId);
        $this->assertEmpty($parentInfo, 'Parent gallery should no longer exist after deletion');
        $this->assertEmpty($childInfo, 'Child gallery should no longer exist after parent deletion');

        // Check that the file in child gallery is also deleted
        $info = TikiLib::lib('filegal')->get_file_info($fileId);
        $this->assertEmpty($info, 'File in child gallery should no longer exist after parent gallery deletion');
    }

    public function testApiDeleteGalleryWithoutRecurse()
    {
        // Create parent gallery
        $parentId = static::createGallery('API_Test_Gallery_DELETE_NoRecurse_Parent', 'Parent gallery no recurse');

        // Create child gallery
        $childId = static::createGallery('API_Test_Gallery_DELETE_NoRecurse_Child', 'Child gallery no recurse', 'default', $parentId);

        // create file in child gallery
        $fileId = static::createFile(
            $childId,
            'child_gallery_file_norecurse.txt',
            'Child Gallery File No Recurse',
            'File in child gallery no recurse',
            'text/plain',
            'Content of the file in child gallery no recurse'
        );

        $data = [
            'galleryId' => static::$rootGalleryId,
            'recurse' => false
        ];
        $response = $this->makeApiRequest(
            'DELETE',
            "/galleries/{$parentId}/delete",
            'Admins',
            $data,
            'application/x-www-form-urlencoded'
        );

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidDeleteFileGalleryResponse($body);
        $this->assertStringContainsString("The file gallery $parentId has been deleted", $body['message'], 'Response message should indicate deletion');

        // Check that the child gallery still exists
        $fileGallery = TikiLib::lib('filegal');
        $parentInfo = $fileGallery->get_file_gallery_info($parentId);
        $childInfo = $fileGallery->get_file_gallery_info($childId);
        $this->assertEmpty($parentInfo, 'Parent gallery should no longer exist after deletion');
        $this->assertNotEmpty($childInfo, 'Child gallery should still exist after parent deletion');

        // Check that the file in child gallery still exists
        $info = TikiLib::lib('filegal')->get_file_info($fileId);
        $this->assertNotEmpty($info, 'File in child gallery should still exist after parent gallery deletion');
    }

    public function testApiDeleteNonExistentGallery()
    {
        $data = [
            'galleryId' => static::$rootGalleryId,
            'recurse' => true
        ];
        $response = $this->makeApiRequest(
            'DELETE',
            '/galleries/999999/delete',
            'Admins',
            $data
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiDeleteFileWithoutPermission()
    {
        $galleryId = static::createGallery('API_Test_Gallery_DELETE_File_NoPerm', 'Gallery for file deletion no permission');
        $fileId = static::createFile(
            $galleryId,
            'file_no_permission.txt',
            'File No Permission',
            'This file will test deletion without permission',
            'text/plain',
            'Content to be tested'
        );

        $response = $this->makeApiRequest(
            'DELETE',
            "/galleries/files/{$fileId}/delete",
            null // Anonymous user (no permission)
        );
        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');

        // the file should still exist
        $fileInfo = TikiLib::lib('filegal')->get_file_info($fileId);
        $this->assertNotEmpty($fileInfo, 'File should still exist after failed deletion attempt');
    }

    public function testApiDeleteFile()
    {
        $galleryId = static::$defaultGalleries['default']['galleryId'];

        // Create a file to delete
        $fileId = static::createFile(
            $galleryId,
            'file_to_delete.txt',
            'File to Delete',
            'This file will be deleted',
            'text/plain',
            'Content to be removed'
        );

        $response = $this->makeApiRequest(
            'DELETE',
            "/galleries/files/{$fileId}/delete",
            'Admins'
        );

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidDeleteFileGalleryResponse($body);
        $this->assertEquals('Delete File', $body['title'], 'Response title should match');
        $this->assertStringContainsString("The file $fileId has been deleted", $body['message'], 'Response message should indicate deletion');
        // Verify the file is deleted
        $fileInfo = TikiLib::lib('filegal')->get_file_info($fileId);
        $this->assertEmpty($fileInfo, 'File should no longer exist after deletion');
    }

    public function testApiDeleteNonExistentFile()
    {
        $response = $this->makeApiRequest(
            'DELETE',
            '/galleries/files/999999/delete',
            'Admins'
        );

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Requested file does not exist (404)');
    }
}
