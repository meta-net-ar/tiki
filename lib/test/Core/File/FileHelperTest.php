<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Core\File;

use Tiki\File\FileHelper;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStreamWrapper;

class FileHelperTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        vfsStreamWrapper::register();
    }

    /**
     * @covers \Tiki\File\FileHelper::appendCSV
     */
    public function testAppendCSV()
    {
        $dir = vfsStream::setup(uniqid('', true));
        $path = $dir->url() . '/file-test.csv';

        $this->assertFileDoesNotExist($path);

        $header = ['id', 'name'];
        $rows = [
            [1, 'John Doe'],
        ];

        FileHelper::appendCSV($path, $header, $rows);

        $this->assertFileExists($path);
        $fileContents = file_get_contents($path);

        $expectedContent = <<<TXT
id,name
1,"John Doe"
TXT;

        $this->assertEquals($expectedContent, trim($fileContents));

        // Append a new file
        $rows = [
            [2, 'Mary Jane'],
        ];

        FileHelper::appendCSV($path, $header, $rows);
        $fileContents = file_get_contents($path);

        $expectedContent .= <<<TXT
\n2,"Mary Jane"
TXT;

        $this->assertEquals($expectedContent, trim($fileContents));
    }
}
