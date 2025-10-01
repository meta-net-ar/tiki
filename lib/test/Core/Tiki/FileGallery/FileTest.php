<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @group unit
 *
 */

use org\bovigo\vfs\vfsStream;
use Tiki\FileGallery\File;
use Tiki\FileGallery\FileWrapper;
use Tiki\Lib\Image\ImageFileHandler;

class Tiki_FileGallery_FileTest extends TikiTestCase
{
    public $oldPrefs;
    protected function setUp(): void
    {
        global $prefs;
        $this->oldPrefs = $prefs;
        parent::setUp();
        TikiLib::lib('filegal')->clearLoadedGalleryDefinitions();
        $this->ensureDefaultGalleryExists();
    }

    protected function tearDown(): void
    {
        global $prefs;
        $prefs = $this->oldPrefs;
        TikiLib::lib('filegal')->clearLoadedGalleryDefinitions();
    }

    public function testInstantiation()
    {
        $file = new File();
        $this->assertEquals(0, $file->fileId);
        $this->assertFalse($file->exists());
    }

    public function testInitialization()
    {
        $file = new File(['filename' => 'test.zip']);
        $this->assertEquals('test.zip', $file->filename);
    }

    public function testLoading()
    {
        $file = new File(['filename' => 'test.zip']);
        $filesTable = TikiLib::lib('filegal')->table('tiki_files');
        $fileId = $filesTable->insert($file->getParamsForDb());

        $file = File::id($fileId);
        $this->assertEquals('test.zip', $file->filename);

        $filesTable->delete(['fileId' => $fileId]);
    }

    public function testCorrectWrapper()
    {
        global $prefs;

        $file = new File(['filename' => 'test.txt', 'data' => 'test content', 'path' => '']);
        $prefs['fgal_use_db'] = 'y';

        $this->assertInstanceOf(FileWrapper\PreloadedContent::class, $file->getWrapper());

        TikiLib::lib('filegal')->clearLoadedGalleryDefinitions();

        $file = new File(['filename' => 'test.txt', 'data' => 'test content', 'path' => 'abcdtest']);
        $prefs['fgal_use_db'] = 'n';
        $prefs['fgal_use_dir'] = vfsStream::setup(uniqid('', true), null)->url();

        $this->assertInstanceOf(FileWrapper\PhysicalFile::class, $file->getWrapper());
        $this->assertEmpty($file->data);
    }

    public function testReplaceContents()
    {
        global $prefs;

        $file = new File(['filename' => 'test.txt', 'data' => 'test content', 'path' => '']);
        $prefs['fgal_use_db'] = 'y';

        $file->replaceContents('updated content');
        $this->assertEquals('updated content', $file->data);
        $this->assertEmpty($file->path);

        TikiLib::lib('filegal')->clearLoadedGalleryDefinitions();

        $file = new File(['filename' => 'test.txt', 'data' => '', 'path' => 'abcdtest']);
        $prefs['fgal_use_db'] = 'n';
        $prefs['fgal_use_dir'] = vfsStream::setup(uniqid('', true), null)->url();
        file_put_contents($prefs['fgal_use_dir'] . '/' . $file->path, 'test content');

        $file->replaceContents('updated content');
        $this->assertEquals('updated content', file_get_contents($prefs['fgal_use_dir'] . '/' . $file->path));
        $this->assertEmpty($file->data);
    }

    public function testHeicConversion()
    {
        global $prefs;

        $prefs['fgal_use_db'] = 'n';
        $prefs['fgal_use_dir'] = vfsStream::setup(uniqid('', true), null)->url();
        $imagepath = $prefs['fgal_use_dir'] . '/testfile.heic';

        file_put_contents($imagepath, 'valid heic content');

        $imageHandlerMock = $this->createMock(ImageFileHandler::class);
        $imageHandlerMock->method('convertHeicToJpg')
            ->with($imagepath)
            ->willReturn('converted jpeg content');

        try {
            $convertedContent = $imageHandlerMock->convertHeicToJpg($imagepath);
            $this->assertNotEmpty($convertedContent);

            $finfo = $this->createMock(finfo::class);
            $finfo->method('buffer')->willReturn('image/jpeg');
            $mimeType = $finfo->buffer($convertedContent);
            $this->assertEquals('image/jpeg', $mimeType, 'The converted file is not a valid JPG.');
        } catch (Exception $e) {
            $this->fail('Error during HEIC conversion: ' . $e->getMessage());
        }
    }

    public function testProcessFile()
    {
        global $prefs;

        $prefs['fgal_use_db'] = 'n';
        $prefs['fgal_use_dir'] = vfsStream::setup(uniqid('', true), null)->url();
        $imagepath = $prefs['fgal_use_dir'] . '/testfile.heic';
        file_put_contents($imagepath, 'valid heic content');

        $wrapperMock = $this->createMock(FileWrapper\PhysicalFile::class);
        $wrapperMock->method('getReadableFile')->willReturn($imagepath);
        $wrapperMock->method('getContents')->willReturn('valid heic content');

        $imageHandler = new ImageFileHandler();
        $convertedContent = $imageHandler->processFile(['filename' => 'testfile.heic'], $wrapperMock);
        $this->assertNotEmpty($convertedContent);

        $finfo = $this->createMock(finfo::class);
        $finfo->method('buffer')->willReturn('image/jpeg');
        $mimeType = $finfo->buffer($convertedContent);
        $this->assertEquals('image/jpeg', $mimeType, 'The converted file is not a valid JPG.');

        $imagepath = $prefs['fgal_use_dir'] . '/testfile.jpg';
        file_put_contents($imagepath, 'valid jpg content');

        $wrapperMock = $this->createMock(FileWrapper\PhysicalFile::class);
        $wrapperMock->method('getReadableFile')->willReturn($imagepath);
        $wrapperMock->method('getContents')->willReturn('valid jpg content');

        $convertedContent = $imageHandler->processFile(['filename' => 'testfile.jpg'], $wrapperMock);
        $this->assertEquals('valid jpg content', $convertedContent);
    }
}
