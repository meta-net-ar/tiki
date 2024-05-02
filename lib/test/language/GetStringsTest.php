<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once(__DIR__ . '/../../language/CollectFiles.php');
require_once(__DIR__ . '/../../language/WriteFile.php');
require_once(__DIR__ . '/../../language/GetStrings.php');
require_once(__DIR__ . '/../../language/FileType.php');
require_once(__DIR__ . '/../../language/FileType/Php.php');
require_once(__DIR__ . '/../../language/FileType/Tpl.php');

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;

class Language_GetStringsTest extends TikiTestCase
{
    /** @var  Language_CollectFiles */
    protected Language_CollectFiles $collectFiles;
    /** @var  Language_FileType_Php */
    protected Language_FileType_Php $fileType;
    /** @var  Language_GetStrings */
    protected Language_GetStrings $obj;
    private string $baseDir;
    private $writeFileFactory;
    private $writeFile;

    /**
     * @throws Language_Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        $this->baseDir = __DIR__ . '/../../../';
        $this->collectFiles = $this->createMock('Language_CollectFiles');
        $this->fileType = $this->createMock('Language_FileType_Php');
        $this->writeFileFactory = $this->createMock('Language_WriteFile_Factory');
        $this->writeFile = $this->getMockBuilder('Language_WriteFile')
                                ->onlyMethods(['writeStringsToFile'])
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->obj = new Language_GetStrings($this->collectFiles, $this->writeFileFactory, ['baseDir' => $this->baseDir]);
    }

    public function testConstructShouldRaiseExceptionForInvalidBaseDir(): void
    {
        $this->expectException('Language_Exception');
        $this->obj = new Language_GetStrings($this->collectFiles, $this->writeFileFactory, ['baseDir' => 'invalidDir']);
    }

    /**
     * @throws Language_Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testAddFileType(): void
    {
        $php = $this->createMock('Language_FileType_Php');
        $php->expects($this->once())->method('getExtensions')->willReturn(['.php']);

        $tpl = $this->createMock('Language_FileType_Tpl');
        $tpl->expects($this->once())->method('getExtensions')->willReturn(['.tpl']);

        $this->obj->addFileType($php);
        $this->obj->addFileType($tpl);

        $this->assertEquals(['.php', '.tpl'], $this->obj->getExtensions());
        $this->assertEquals([$php, $tpl], $this->obj->getFileTypes());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testAddFileTypeShouldRaiseExceptionIfSameTypeIsAddedMoreThanOnce(): void
    {
        $this->expectException('Language_Exception');

        $php = $this->createMock('Language_FileType_Php');
        $php->expects($this->once(
        ))->method('getExtensions')->willReturn(['.php']);

        $this->obj->addFileType($php);
        $this->obj->addFileType($php);
    }

    public function testCollectStringsShouldRaiseExceptionIfEmptyFileTypes(): void
    {
        $this->expectException('Language_Exception');
        $this->obj->collectStrings('file.php');
    }

    public function testCollectStringsShouldRaiseExceptionIfInvalidFileExtension(): void
    {
        $this->expectException('Language_Exception');
        $this->fileType->method('getExtensions')->willReturn(['.php']);
        $this->obj->addFileType($this->fileType);
        $this->obj->collectStrings('file.');
    }

    /**
     * @throws Language_Exception
     */
    public function testCollectStringsWithFileTypePhp(): void
    {
        $this->obj->addFileType(new Language_FileType_Php());
        $strings = $this->obj->collectStrings(__DIR__ . '/fixtures/test_collecting_strings.php');

        $expectedResult = ['%0 enabled', '%0 disabled', 'Features', 'Enable/disable Tiki features here, but configure them elsewhere',
            'General', 'General preferences and settings', 'Login', 'User registration, login and authentication', 'Wiki', 'Wiki settings',
            'Help on $admintitle Config', "Congratulations!\n\nYour server can send emails.\n\n",
        ];

        $this->assertEquals($expectedResult, $strings);
    }

    /**
     * @throws Language_Exception
     */
    public function testCollectStringsShouldCallRegexPostProcessMethodIfOneExists(): void
    {
        $php = $this->getMockBuilder('Language_FileType_Php')
                    ->onlyMethods(['getExtensions', 'getCleanupRegexes', 'singleQuoted', 'doubleQuoted'])
                    ->getMock();

        $php->expects($this->exactly(2))->method('getExtensions')->willReturn(['.php']);
        $php->expects($this->once())->method('getCleanupRegexes')->willReturn([]);
        $php->expects($this->once(
        ))->method('singleQuoted')->willReturn([0 => '', 1 => '']);
        $php->expects($this->once(
        ))->method('doubleQuoted')->willReturn([0 => '', 1 => '']);

        $this->obj->addFileType($php);
        $this->obj->addFileType(new Language_FileType_Tpl());
        $this->obj->collectStrings(__DIR__ . '/fixtures/test_collecting_strings.php');
    }

    /**
     * @throws Language_Exception
     */
    public function testCollectStringsWithFileTypeTpl(): void
    {
        $this->obj->addFileType(new Language_FileType_Tpl());
        $strings = $this->obj->collectStrings(__DIR__ . '/fixtures/test_collecting_strings.tpl');

        $expectedResult = ['Bytecode Cache', 'Using <strong>%0</strong>.These stats affect all PHP applications running on the server.',
            'Used', 'Available', 'Memory', 'Hit', 'Miss', 'Cache Hits', 'Few hits recorded. Statistics may not be representative.',
            'Low hit ratio. %0 may be misconfigured and not used.',
            'Errors', 'Errors:', 'Created',
        ];

        $this->assertEquals($expectedResult, $strings);
    }

    /**
     * @throws Language_Exception
     */
    public function testCollectStringShouldNotConsiderEmptyCallsToTra(): void
    {
        $this->obj->addFileType(new Language_FileType_Php());

        $fileName = 'file1.php';

        $root = vfsStream::setup();

        $file = new vfsStreamFile($fileName);
        $file->setContent(
            "'sub' => array(
                   'required' => false,
                   'default' => 'n',
                   'filter' => 'alpha',
                   'options' => array(
                       array('text' => tra(''), 'value' => ''),
                       array('text' => tra('Yes'), 'value' => 'y'),
                       array('text' => tra('No'), 'value' => 'n')
                   ),
               ),"
        );

        $root->addChild($file);

        $expectedResult = ['Yes', 'No'];

        $this->assertEquals($expectedResult, $this->obj->collectStrings(vfsStream::url('root/' . $fileName)));
    }

    public function testRunShouldRaiseExceptionIfEmptyFileTypes(): void
    {
        $this->expectException('Language_Exception');
        $this->obj->run();
    }

    /**
     * @throws Language_Exception
     */
    public function testRunShouldReturnCollectedStrings(): void
    {
        $files = ['file1', 'file2', 'file3'];

        $strings = [
            'string1' => ['name' => 'string1'],
            'string2' => ['name' => 'string2'],
            'string3' => ['name' => 'string3'],
            'string4' => ['name' => 'string4'],
        ];

        $this->collectFiles->expects($this->once())->method('setExtensions');
        $this->collectFiles->expects($this->once())->method('run')->with($this->baseDir)->willReturn($files);

        $obj = $this->getMockBuilder('Language_GetStrings')
                    ->onlyMethods(['collectStrings', 'writeToFiles'])
                    ->setConstructorArgs([
                        $this->collectFiles,
                        $this->writeFileFactory,
                        ['baseDir' => $this->baseDir]])
            ->getMock();
        $obj->expects($this->once())->method('writeToFiles')->with($strings);

        $collectStringsCalls = [
            ['file1', ['string1', 'string2']],
            ['file2', ['string2', 'string3']],
            ['file3', ['string3', 'string4']],
        ];

        $collectStringsIndex = 0;

        $obj->method('collectStrings')->willReturnCallback(function () use (&$collectStringsCalls, &$collectStringsIndex) {
            $args = func_get_args();
            $this->assertSame($collectStringsCalls[$collectStringsIndex][0], $args[0]);
            return $collectStringsCalls[$collectStringsIndex++][1];
        });

        $this->fileType->expects($this->once())->method('getExtensions')->willReturn(['.php']);
        $obj->addFileType($this->fileType);

        $this->assertNull($obj->run());
    }

    /**
     * @throws Language_Exception
     */
    public function testSetLanguagesShouldSetLanguagesForArrayParam(): void
    {
        $languages = ['en', 'es', 'pt-br'];
        $this->obj->setLanguages($languages);
        $this->assertEquals($this->obj->getLanguages(), $languages);
    }

    /**
     * @throws Language_Exception
     */
    public function testSetLanguagesShouldSetLanguagesForStringParam(): void
    {
        $language = 'en';
        $this->obj->setLanguages($language);
        $this->assertEquals($this->obj->getLanguages(), [$language]);
    }

    public function testSetLanguagesShouldRaiseExceptionForInvalidLanguage(): void
    {
        $languages = ['en', 'invalid'];
        $this->expectException('Language_Exception');
        $this->obj->setLanguages($languages);
    }

    /**
     * @throws Language_Exception
     */
    public function testSetLanguagesShouldCallGetAllLanguagesIfLanguageParamIsNull(): void
    {
        $obj = $this->getMockBuilder('Language_GetStrings')
                    ->onlyMethods(['getAllLanguages'])
                    ->setConstructorArgs([$this->collectFiles, $this->writeFileFactory])
                    ->getMock();

        $obj->expects($this->once())->method('getAllLanguages');
        $obj->setLanguages();
    }

    /**
     * @throws Language_Exception
     */
    public function testWriteToFilesShouldCallWriteStringsThreeTimes(): void
    {
        $strings = ['string1', 'string2', 'string3', 'string4'];

        $this->writeFile->expects($this->exactly(3))->method('writeStringsToFile')->with($strings, false);
        $this->obj->setLanguages(['en', 'es', 'pt-br']);

        $writeFileFactoryCalls = [
            ['en/language.php'],
            ['es/language.php'],
            ['pt-br/language.php'],
        ];

        $writeFileFactoryIndex = 0;

        $this->writeFileFactory->method('factory')->willReturnCallback(function () use (&$writeFileFactoryCalls, &$writeFileFactoryIndex) {
            $args = func_get_args();
            $this->stringContains($writeFileFactoryCalls[$writeFileFactoryIndex++][0]);
            return $this->writeFile;
        });

        $this->obj->writeToFiles($strings);
    }

    /**
     * @throws Language_Exception
     */
    public function testWriteToFilesShouldCallWriteStringsWithOutputFileParam(): void
    {
        $strings = ['string1', 'string2', 'string3', 'string4'];

        $this->writeFile->expects($this->atLeastOnce())->method('writeStringsToFile')->with($strings, true);
        $this->writeFileFactory->expects($this->atLeastOnce())->method('factory')->willReturn($this->writeFile);

        $obj = new Language_GetStrings($this->collectFiles, $this->writeFileFactory, ['outputFiles' => true, 'baseDir' => $this->baseDir]);

        $obj->writeToFiles($strings);
    }

    /**
     * @throws Language_Exception
     */
    public function testWriteToFilesShouldUseCustomFileName(): void
    {
        $strings = ['string1', 'string2', 'string3', 'string4'];

        $this->writeFile->expects($this->once())->method('writeStringsToFile')->with($strings, false);
        $this->writeFileFactory->expects($this->once())->method('factory')->with($this->stringContains('language_r.php'))->willReturn($this->writeFile);

        $obj = new Language_GetStrings($this->collectFiles, $this->writeFileFactory, ['baseDir' => $this->baseDir, 'lang' => 'es', 'fileName' => 'language_r.php']);
        $obj->writeToFiles($strings);
    }

    public function testScanFilesShouldReturnStringsFromFiles(): void
    {
        $files = ['file1', 'file2', 'file3'];

        $strings = [
            'string1' => ['name' => 'string1'],
            'string2' => ['name' => 'string2'],
            'string3' => ['name' => 'string3'],
            'string4' => ['name' => 'string4'],
        ];

        $obj = $this->getMockBuilder('Language_GetStrings')
                    ->onlyMethods(['collectStrings', 'setLanguages'])
                    ->setConstructorArgs([$this->collectFiles, $this->writeFileFactory])
                    ->getMock();
        $collectStringsCalls = [
            ['file1', ['string1', 'string2']],
            ['file2', ['string2', 'string3']],
            ['file3', ['string3', 'string4']],
        ];

        $collectStringsIndex = 0;

        $obj->method('collectStrings')->willReturnCallback(function () use (&$collectStringsCalls, &$collectStringsIndex) {
            $args = func_get_args();
            $this->assertSame($collectStringsCalls[$collectStringsIndex][0], $args[0]);
            return $collectStringsCalls[$collectStringsIndex++][1];
        });

        $this->assertEquals($strings, $obj->scanFiles($files));
    }

    public function testScanFilesShouldReturnInformationAboutTheFilesWhereTheStringsWereFound(): void
    {
        $files = ['file1', 'file2', 'file3'];
        $strings = [
            'string1' => ['name' => 'string1', 'files' => ['file1']],
            'string2' => ['name' => 'string2', 'files' => ['file1', 'file2']],
            'string3' => ['name' => 'string3', 'files' => ['file2', 'file3']],
            'string4' => ['name' => 'string4', 'files' => ['file3']],
        ];
        $obj = $this->getMockBuilder('Language_GetStrings')
                    ->onlyMethods(['collectStrings', 'setLanguages'])
                    ->setConstructorArgs([$this->collectFiles, $this->writeFileFactory, ['outputFiles' => true]])
                    ->getMock();
        $collectStringsCalls = [
            ['file1', ['string1', 'string2']],
            ['file2', ['string2', 'string3']],
            ['file3', ['string3', 'string4']],
        ];
        $collectStringsIndex = 0;
        $obj->method('collectStrings')->willReturnCallback(function () use (&$collectStringsCalls, &$collectStringsIndex) {
            $args = func_get_args();
            $this->assertSame($collectStringsCalls[$collectStringsIndex][0], $args[0]);
            return $collectStringsCalls[$collectStringsIndex++][1];
        });
        $this->assertEquals($strings, $obj->scanFiles($files));
    }
}
