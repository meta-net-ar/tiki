<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Importer;

use TikiImporter_Wiki_Mediawiki;

/**
 * @group importer
 */
class WikiInsertPageTest extends AbstractImporterTestCase
{
    public $obj;
    protected function setUp(): void
    {
        require_once(__DIR__ . '/fixtures/mediawiki_page_as_array.php');
        global $tikilib;
        $tikilib = $this->getMockBuilder('TikiLib')
           ->onlyMethods(['create_page', 'update_page', 'page_exists', 'remove_all_versions'])
           ->getMock();
        $this->obj = new TikiImporter_Wiki_Mediawiki();
        $this->obj->revisionsNumber = 0;
    }

    public function testInsertPage(): void
    {
        global $tikilib, $page;

        $tikilib->expects($this->once())->method('page_exists')->with($page['name'])->willReturn(false);
        $tikilib->expects($this->once())->method('create_page')->with($page['name'], 0, $page['revisions'][0]['data'], $page['revisions'][0]['lastModif'], $page['revisions'][0]['comment'], $page['revisions'][0]['user'], $page['revisions'][0]['ip']);
       // TODO: how to test parameters for update_page for the 7 different calls
        $tikilib->expects($this->exactly(7))->method('update_page');

       // $page is set on mediawiki_page_as_array.php
        $this->assertEquals('Redes de ensino', $this->obj->insertPage($page));
    }

    public function testInsertPageAlreadyExistentPageNameOverride(): void
    {
        global $tikilib, $page;
        $tikilib->expects($this->once())->method('page_exists')->with($page['name'])->willReturn(true);
        $tikilib->expects($this->once())->method('remove_all_versions')->with($page['name']);
        $tikilib->expects($this->once())->method('create_page');
        $tikilib->expects($this->exactly(7))->method('update_page');

        $this->obj->alreadyExistentPageName = 'override';
        $this->assertEquals('Redes de ensino', $this->obj->insertPage($page));
    }

    public function testInsertPageAlreadyExistentPageNameAppendPrefix(): void
    {
        global $tikilib, $page;

        $newPageName = $this->obj->softwareName . '_' . $page['name'];

        $tikilib->expects($this->once())->method('page_exists')->with($page['name'])->willReturn(true);
        $tikilib->expects($this->once())->method('create_page')->with($newPageName);
        $tikilib->expects($this->exactly(7))->method('update_page')->with($newPageName);

        $this->obj->alreadyExistentPageName = 'appendPrefix';
        $this->assertEquals('Mediawiki_Redes de ensino', $this->obj->insertPage($page));
    }

    public function testInsertPageAlreadyExistentPageNameDoNotImport(): void
    {
        global $tikilib, $page;

        $tikilib->expects($this->once())->method('page_exists')->with($page['name'])->willReturn(true);
        $tikilib->expects($this->never())->method('create_page');
        $tikilib->expects($this->never())->method('update_page');

        $this->obj->alreadyExistentPageName = 'doNotImport';
        $this->assertFalse($this->obj->insertPage($page));
    }
}
