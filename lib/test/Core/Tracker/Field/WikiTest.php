<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiTests;

use TikiTestCase;
use Tracker_Definition;
use TikiLib;
use Tracker_Item;
use Feedback;
use Tracker_Field_Wiki;
use Perms;

/**
 * Integration test for the Tracker Wiki Field type conversion.
 * This test verifies the functionality of the convertFieldTo() method in Tracker_Field_Wiki.
 */
class WikiTest extends TikiTestCase
{
    private $trackerId;
    private $fieldId;
    private $titleFieldId;

    /**
     * Set up a clean environment for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        global $user;
        $user = 'admin';
        $prefs['feature_wiki'] = 'y';

        // Mock Server environment for TikiLib
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = '/tiki-index.php';
        $_SERVER['HTTP_HOST'] = 'localhost';

        $perms = Perms::get();
        $perms->admin = true;
        $trklib = TikiLib::lib('trk');

        // 1. Create Tracker
        $this->trackerId = $trklib->replace_tracker(null, 'Test Wiki Field Conversion', 'Description', [], 'n');

        // 2. Add Title Field
        $this->titleFieldId = $trklib->replace_tracker_field(
            $this->trackerId,
            0,
            'Title',
            't',
            'y',
            'y',
            'y',
            'y',
            'n',
            'n',
            10,
            serialize(['name' => 'Title', 'mode' => 'n'])
        );

        // 3. Add Wiki Field
        $this->fieldId = $trklib->replace_tracker_field(
            $this->trackerId,
            0,
            'WikiPage',
            'w',
            'n',
            'n',
            'y',
            'y',
            'n',
            'n',
            20,
            serialize([
                'fieldIdForPagename' => $this->titleFieldId,
                'namespace' => 'none',
                'wysiwyg' => 'n',
                'samerow' => 1,
                'toolbars' => 1
            ])
        );
    }

    protected function tearDown(): void
    {
        $trklib = TikiLib::lib('trk');
        if ($this->trackerId) {
            $trklib->remove_tracker($this->trackerId);
        }
        parent::tearDown();
    }

    /**
    * Test the normal conversion scenario:
    * - Wiki Page content is moved to the Tracker Field.
    * - Wiki Page is deleted.
    */
    public function testConvertFieldToTextArea()
    {
        $trklib = TikiLib::lib('trk');
        $tikilib = TikiLib::lib('tiki');

        $pageName = 'TestConversionPage_' . uniqid();
        $pageContent = 'This is the content of the wiki page that should be migrated.';

        // 1. Create Tracker Item
        $itemData = [
            $this->titleFieldId => $pageName,
            $this->fieldId => $pageName
        ];
        $itemId = $trklib->replace_item($this->trackerId, 0, $itemData);

        //2. Force insert field value (Ensure data exists for migration logic)
        $trklib->table('tiki_tracker_item_fields')->insertOrUpdate(
            ['value' => $pageName],
            ['itemId' => $itemId, 'fieldId' => $this->fieldId]
        );

        // 3. Create Wiki Page (Direct DB Insert for robustness)
        $trklib->table('tiki_pages')->insert([
            'pageName' => $pageName,
            'data' => $pageContent,
            'lastModif' => time(),
            'comment' => 'Test',
            'user' => 'admin',
            'ip' => '127.0.0.1',
            'created' => time(),
            'is_html' => 0,
            'hits' => 0
        ]);
        $tikilib->invalidate_cache($pageName);

        // Verify precondition directly in DB
        $exists = $trklib->table('tiki_pages')->fetchOne('pageName', ['pageName' => $pageName]);
        $this->assertNotEmpty($exists, 'Pre-condition: Wiki page must exist in DB');

        // 4. Run Conversion
        $def = Tracker_Definition::get($this->trackerId);
        $fieldInfo = $def->getField($this->fieldId);
        $fieldInfo['options_map'] = unserialize($fieldInfo['options']);

        $wikiField = new Tracker_Field_Wiki($fieldInfo, null, $def);
        $wikiField->convertFieldTo('a');

        // 5. Verification
        $dbContent = $trklib->table('tiki_tracker_item_fields')->fetchOne(
            'value',
            ['itemId' => $itemId,
            'fieldId' => $this->fieldId]
        );

        $this->assertEquals($pageContent, $dbContent, 'The Tracker Field should now contain the Wiki Page text.');

        $exists = $trklib->table('tiki_pages')->fetchOne(
            'pageName',
            ['pageName' => $pageName]
        );
        $this->assertFalse($exists, 'The original Wiki Page should be deleted after migration.');
    }

    /**
    * Test the conversion when the Wiki Page is huge (> 65535 bytes).
    * Expected Result:Tracker Field becomes an {include} plugin, Page is NOT deleted.
    */
    public function testConvertFieldToTextAreaKeepBigPage()
    {
        $trklib = TikiLib::lib('trk');
        $tikilib = TikiLib::lib('tiki');

        // 1. Create Huge Content
        $pageName = 'HugePage_' . uniqid();
        $hugeContent = str_repeat('X', 70000);

        // 2. Create Item and Page
        $itemData = [
            $this->titleFieldId => $pageName,
            $this->fieldId => $pageName
        ];
        $itemId = $trklib->replace_item($this->trackerId, 0, $itemData);

        $trklib->table('tiki_tracker_item_fields')->insertOrUpdate(
            ['value' => $pageName],
            ['itemId' => $itemId, 'fieldId' => $this->fieldId]
        );

        // 2. Create Huge Wiki Page
        $trklib->table('tiki_pages')->insert([
            'pageName' => $pageName,
            'data' => $hugeContent,
            'lastModif' => time(),
            'comment' => 'Huge Test',
            'user' => 'admin',
            'ip' => '127.0.0.1',
            'created' => time(),
            'is_html' => 0,
            'hits' => 0
        ]);
        $tikilib->invalidate_cache($pageName);

        // 3. Run Conversion
        $def = Tracker_Definition::get($this->trackerId);
        $fieldInfo = $def->getField($this->fieldId);
        $fieldInfo['options_map'] = unserialize($fieldInfo['options']);

        $wikiField = new Tracker_Field_Wiki($fieldInfo, null, $def);
        $wikiField->convertFieldTo('a');

        // 4. Verification
        $dbContent = $trklib->table('tiki_tracker_item_fields')->fetchOne(
            'value',
            ['itemId' => $itemId,
            'fieldId' => $this->fieldId]
        );

        $this->assertStringContainsString('{include page="' . $pageName . '"}', $dbContent);

        $exists = $trklib->table('tiki_pages')->fetchOne(
            'pageName',
            ['pageName' => $pageName]
        );
        $this->assertNotEmpty($exists, 'Huge Wiki Page should NOT be deleted.');
    }

    /**
     * Test conversion when the Wiki Page is missing (broken link).
     * Expected Result: Tracker Field contains error message "page not found".
     */
    public function testConvertFieldToTextAreaMissingPage()
    {
        $trklib = TikiLib::lib('trk');

        $pageName = 'NonExistentPage_' . uniqid();

        $itemData = [
            $this->titleFieldId => $pageName,
            $this->fieldId => $pageName
        ];
        $itemId = $trklib->replace_item($this->trackerId, 0, $itemData);

        $trklib->table('tiki_tracker_item_fields')->insertOrUpdate(
            ['value' => $pageName],
            ['itemId' => $itemId, 'fieldId' => $this->fieldId]
        );

        $def = Tracker_Definition::get($this->trackerId);
        $fieldInfo = $def->getField($this->fieldId);
        $fieldInfo['options_map'] = unserialize($fieldInfo['options']);

        $wikiField = new Tracker_Field_Wiki($fieldInfo, null, $def);
        $wikiField->convertFieldTo('a');

        $dbContent = $trklib->table('tiki_tracker_item_fields')->fetchOne('value', [
            'itemId' => $itemId,
            'fieldId' => $this->fieldId
        ]);

        $this->assertStringContainsString('page not found', $dbContent);
        $this->assertStringContainsString($pageName, $dbContent);
    }

    /**
     * Test conversion that throws exception for unsupported types
     */
    public function testConvertUnsupportedType()
    {
        $def = Tracker_Definition::get($this->trackerId);
        $fieldInfo = $def->getField($this->fieldId);
        $fieldInfo['options_map'] = unserialize($fieldInfo['options']);

        $wikiField = new Tracker_Field_Wiki($fieldInfo, null, $def);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unsupported field conversion');

        $wikiField->convertFieldTo('t');
    }
}
