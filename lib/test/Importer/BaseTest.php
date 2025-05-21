<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Importer;

use Tiki\Lib\Test\Importer\Helper\FirstChild;
use Tiki\Lib\Test\Importer\Helper\GranSon;
use TikiImporter;

/**
 * @group importer
 */
class BaseTest extends AbstractImporterTestCase
{
    public function testGetOptions(): void
    {
        $expectedResult = [['name' => 'name'],
                                ['name' => 'otherName'],
                                ['secondName' => 'something']];
        $object = new GranSon();
        $this->assertEquals($expectedResult, $object->getOptions());

        $expectedResult = [['name' => 'someName', 'property1' => 'someProperty'],
                                ['name' => 'differentName', 'property' => 'anotherProperty']];
        $object = new FirstChild();
        $this->assertEquals($expectedResult, $object->getOptions());
    }

    public function testChangePhpSettings(): void
    {
        TikiImporter::changePhpSettings();
        $this->assertEquals(E_ALL & ~E_DEPRECATED, ini_get('error_reporting'), 'Should change the value of the error reporting');
        $this->assertEquals('on', ini_get('display_errors'), 'Should change the value of display_errors');
        $this->assertEquals(0, ini_get('max_execution_time'), 'Should change the value of max_execution_time');
    }

    public function testDisplayPhpUploadError(): void
    {
        $this->assertNull(TikiImporter::displayPhpUploadError(-1), 'Should return null if invalid code passed as param');
        $this->assertEquals('No file was uploaded.', TikiImporter::displayPhpUploadError(4));
    }
}
