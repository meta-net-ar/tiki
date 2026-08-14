<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Importer;

use Tiki\Lib\Importer\Wiki;

/**
 * @group importer
 */
class WikiTest extends AbstractImporterTestCase
{
    public function testImportShouldCallMethodsToStartImportProcess(): void
    {
        ob_start();
        $obj = $this->getMockBuilder(Wiki::class)
           ->onlyMethods(['validateInput', 'parseData', 'insertData'])
           ->getMock();
        $obj->expects($this->once())->method('validateInput');
        $obj->expects($this->once())->method('parseData');
        $obj->expects($this->once())->method('insertData');

        $obj->import();

        $output = ob_get_clean();
        $this->assertEquals("\nImportation completed!\n\n<b><a href=\"tiki-importer.php\">Click here</a> to finish the import process</b>", $output);
    }

    public function testImportShouldSetInstanceProperties(): void
    {
        ob_start();

        $obj = $this->getMockBuilder(Wiki::class)
           ->onlyMethods(['validateInput', 'parseData', 'insertData'])
           ->getMock();
        $_POST['alreadyExistentPageName'] = 'override';
        $_POST['wikiRevisions'] = 100;

        $obj->import();

        $this->assertEquals(100, $obj->revisionsNumber);
        $this->assertEquals('override', $obj->alreadyExistentPageName);

        unset($_POST['alreadyExistentPageName'], $_POST['wikiRevisions']);
        $obj->import();

        $this->assertEquals(0, $obj->revisionsNumber);
        $this->assertEquals('doNotImport', $obj->alreadyExistentPageName);

        ob_get_clean();
    }

    public function testImportShouldSetSessionVariables(): void
    {
        ob_start();

        $expectedImportFeedback = ['importedPages' => 10, 'totalPages' => '13'];
        $obj = $this->getMockBuilder(Wiki::class)
           ->onlyMethods(['validateInput', 'parseData', 'insertData', 'saveAndDisplayLog'])
           ->getMock();
        $obj->expects($this->once())->method('validateInput');
        $obj->expects($this->once())->method('parseData');
        $obj->expects($this->once())->method('insertData')->willReturn($expectedImportFeedback);
        $obj->expects($this->once())->method('saveAndDisplayLog');

        $obj->log = 'some log string';
        $obj->import();

        $this->assertEquals($expectedImportFeedback, $_SESSION['tiki_importer_feedback']);
        $this->assertEquals('some log string', $_SESSION['tiki_importer_log']);

        ob_get_clean();
    }

    public function testInsertDataCallInsertPageFourTimes(): void
    {
        ob_start();

        $obj = $this->getMockBuilder(Wiki::class)
           ->onlyMethods(['insertPage'])
           ->getMock();
        $obj->expects($this->exactly(4))->method('insertPage');
        $parsedData = [['name' => '1'],['name' => '2'],['name' => '3'],['name' => '4'],];
        $obj->insertData($parsedData);

        ob_get_clean();
    }

    public function testInsertDataCallInsertPageOnceWithProperParam(): void
    {
        ob_start();

        $obj = $this->getMockBuilder(Wiki::class)
           ->onlyMethods(['insertPage'])
           ->getMock();
        $obj->expects($this->once())->method('insertPage')->with(['name' => '1']);
        $parsedData = [['name' => '1'],];
        $obj->insertData($parsedData);

        ob_get_clean();
    }

    public function testInsertDataShouldNotCallInsertPage(): void
    {
        ob_start();

        $obj = $this->getMockBuilder(Wiki::class)
           ->onlyMethods(['insertPage'])
           ->getMock();
        $obj->expects($this->never())->method('insertPage');
        $parsedData = [];
        $obj->insertData($parsedData);

        ob_get_clean();
    }

    public function testInsertDataShouldReturnCountData(): void
    {
        ob_start();

        $obj = $this->getMockBuilder(Wiki::class)
           ->onlyMethods(['insertPage'])
           ->getMock();
        $obj->expects($this->exactly(6))->method('insertPage')->willReturnOnConsecutiveCalls(true, true, false, true, false, true);

        $parsedData = [
            ['name' => 'Page1'],
            ['name' => 'Page2'],
            ['name' => 'Page3'],
            ['name' => 'Page4'],
            ['name' => 'Page5'],
            ['name' => 'Page6'],
        ];
        $countData = $obj->insertData($parsedData);
        $expectedResult = ['totalPages' => 6, 'importedPages' => 4];

        $this->assertEquals($expectedResult, $countData);

        ob_get_clean();
    }
}
