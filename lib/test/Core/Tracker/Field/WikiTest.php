<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiTests;

use TikiTestCase;
use Tracker_Definition;
use TikiLib;
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

        global $user, $prefs;
        $user = 'admin';
        $prefs['feature_wiki'] = 'y';
        $prefs['feature_trackers'] = 'y';

        // Mock Server environment for TikiLib
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = '/tiki-index.php';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';

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
            'wiki',
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
                'toolbars' => 1,
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

        $pageName = 'TestConversionPage_' . uniqid();
        $pageContent = 'This is the content of the wiki page that should be migrated.';

        // 1. Create Tracker Item
        $itemData = [
            $this->titleFieldId => $pageName,
            $this->fieldId => $pageName
        ];
        $itemId = $this->createItem($itemData);

        //2. Force insert field value (Ensure data exists for migration logic)
        $trklib->table('tiki_tracker_item_fields')->insertOrUpdate(
            ['value' => $pageName],
            ['itemId' => $itemId, 'fieldId' => $this->fieldId]
        );

        // 3. Create Wiki Page
        $this->createOrUpdatePage($pageName, $pageContent);

        // Verify precondition directly in DB
        $exists = $trklib->table('tiki_pages')->fetchOne('pageName', ['pageName' => $pageName]);
        $this->assertNotEmpty($exists, 'Pre-condition: Wiki page must exist in DB');

        // 4. Run Conversion
        $this->getWikiFieldHandler($this->fieldId)->convertFieldTo('a');

        // 5. Verification
        $dbContent = $this->getFieldValue($itemId, $this->fieldId);

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

        // 1. Create Huge Content
        $pageName = 'HugePage_' . uniqid();
        $hugeContent = str_repeat('X', 70000);

        // 2. Create Item and Page
        $itemData = [
            $this->titleFieldId => $pageName,
            $this->fieldId => $pageName
        ];
        $itemId = $this->createItem($itemData);

        $trklib->table('tiki_tracker_item_fields')->insertOrUpdate(
            ['value' => $pageName],
            ['itemId' => $itemId, 'fieldId' => $this->fieldId]
        );

        // 2. Create Huge Wiki Page
        $this->createOrUpdatePage($pageName, $hugeContent);

        // 3. Run Conversion
        $this->getWikiFieldHandler($this->fieldId)->convertFieldTo('a');

        // 4. Verification
        $dbContent = $this->getFieldValue($itemId, $this->fieldId);

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
        $tikilib = TikiLib::lib('tiki');

        $pageName = 'NonExistentPage_' . uniqid();

        $itemData = [
            $this->titleFieldId => $pageName,
            $this->fieldId => $pageName
        ];
        $itemId = $this->createItem($itemData);

        $trklib->table('tiki_tracker_item_fields')->insertOrUpdate(
            ['value' => $pageName],
            ['itemId' => $itemId, 'fieldId' => $this->fieldId]
        );

        // replace_item() may auto-create wiki pages for this field type
        $tikilib->remove_all_versions($pageName);
        $tikilib->invalidate_cache($pageName);

        $this->getWikiFieldHandler($this->fieldId)->convertFieldTo('a');

        $dbContent = $this->getFieldValue($itemId, $this->fieldId);

        $this->assertStringContainsString('page not found', $dbContent);
        $this->assertStringContainsString($pageName, $dbContent);
    }

    public function testConvertFieldToTextAreaDoesNotConsumeOtherWikiFieldPage()
    {
        $trklib = TikiLib::lib('trk');
        $tikilib = TikiLib::lib('tiki');

        $secondWikiFieldId = $this->createWikiField('WikiPage2', 30);

        $missingPage = 'MissingPage_' . uniqid();
        $otherFieldPage = 'OtherFieldPage_' . uniqid();
        $otherFieldContent = 'OTHER_FIELD_CONTENT_' . uniqid();

        $itemData = [
            $this->titleFieldId => 'Title_' . uniqid(),
            $this->fieldId => $missingPage,
            $secondWikiFieldId => $otherFieldPage,
        ];
        $itemId = $this->createItem($itemData);

        $tikilib->remove_all_versions($missingPage);
        $tikilib->invalidate_cache($missingPage);
        $this->createOrUpdatePage($otherFieldPage, $otherFieldContent);

        $this->getWikiFieldHandler($this->fieldId)->convertFieldTo('a');

        $convertedValue = $this->getFieldValue($itemId, $this->fieldId);
        $otherFieldValue = $this->getFieldValue($itemId, $secondWikiFieldId);
        $otherPageExists = $trklib->table('tiki_pages')->fetchOne('pageName', ['pageName' => $otherFieldPage]);

        $this->assertStringContainsString('page not found', $convertedValue);
        $this->assertStringContainsString($missingPage, $convertedValue);
        $this->assertNotSame($otherFieldContent, $convertedValue);
        $this->assertSame($otherFieldPage, $otherFieldValue);
        $this->assertNotEmpty($otherPageExists, 'A different Wiki field page must not be consumed or deleted.');
    }

    public function testConvertFieldToTextAreaPreservesEmptyPageContentWithoutFallback()
    {
        $trklib = TikiLib::lib('trk');

        $secondWikiFieldId = $this->createWikiField('WikiPage2', 30);

        $emptyPage = 'EmptyPage_' . uniqid();
        $otherFieldPage = 'OtherFieldPage_' . uniqid();
        $otherFieldContent = 'OTHER_FIELD_CONTENT_' . uniqid();

        $itemData = [
            $this->titleFieldId => 'Title_' . uniqid(),
            $this->fieldId => $emptyPage,
            $secondWikiFieldId => $otherFieldPage,
        ];
        $itemId = $this->createItem($itemData);

        $this->createOrUpdatePage($emptyPage, '');
        $this->createOrUpdatePage($otherFieldPage, $otherFieldContent);

        $this->getWikiFieldHandler($this->fieldId)->convertFieldTo('a');

        $convertedValue = $this->getFieldValue($itemId, $this->fieldId);
        $otherPageExists = $trklib->table('tiki_pages')->fetchOne('pageName', ['pageName' => $otherFieldPage]);

        $this->assertSame('', $convertedValue, 'Existing empty page content should remain empty text.');
        $this->assertNotEmpty($otherPageExists, 'Empty-content conversion must not fall back to a different page.');
    }

    public function testConvertFieldToTextAreaAllowsSingleLegacyLinkedItemFallback()
    {
        $trklib = TikiLib::lib('trk');
        $tikilib = TikiLib::lib('tiki');
        $relationlib = TikiLib::lib('relation');

        $legacyPage = 'LegacyFallbackPage_' . uniqid();
        $legacyContent = 'LEGACY_FALLBACK_CONTENT_' . uniqid();
        $title = 'Title_' . uniqid();

        $itemData = [
            $this->titleFieldId => $title,
            $this->fieldId => '',
        ];
        $itemId = $this->createItem($itemData);
        $tikilib->remove_all_versions($title);
        $tikilib->invalidate_cache($title);

        $this->createOrUpdatePage($legacyPage, $legacyContent);
        // simulate historical data where only linkeditem relation exists
        $relationlib->add_relation('tiki.wiki.linkeditem', 'wiki page', $legacyPage, 'trackeritem', $itemId);

        $this->getWikiFieldHandler($this->fieldId)->convertFieldTo('a');

        $convertedValue = $this->getFieldValue($itemId, $this->fieldId);

        $this->assertSame($legacyContent, $convertedValue);
    }

    public function testConvertFieldToTextAreaDoesNotGuessWithAmbiguousLegacyLinkedItems()
    {
        $trklib = TikiLib::lib('trk');
        $tikilib = TikiLib::lib('tiki');
        $relationlib = TikiLib::lib('relation');

        $legacyPageA = 'LegacyAmbiguousA_' . uniqid();
        $legacyPageB = 'LegacyAmbiguousB_' . uniqid();
        $legacyContentA = 'LEGACY_AMBIGUOUS_CONTENT_A_' . uniqid();
        $legacyContentB = 'LEGACY_AMBIGUOUS_CONTENT_B_' . uniqid();
        $title = 'AmbiguousTitle_' . uniqid();

        $itemData = [
            $this->titleFieldId => $title,
            $this->fieldId => '',
        ];
        $itemId = $this->createItem($itemData);
        $tikilib->remove_all_versions($title);
        $tikilib->invalidate_cache($title);

        $this->createOrUpdatePage($legacyPageA, $legacyContentA);
        $this->createOrUpdatePage($legacyPageB, $legacyContentB);
        // simulate historical data with multiple linkeditem relations and no linkedfield relation
        $relationlib->add_relation('tiki.wiki.linkeditem', 'wiki page', $legacyPageA, 'trackeritem', $itemId);
        $relationlib->add_relation('tiki.wiki.linkeditem', 'wiki page', $legacyPageB, 'trackeritem', $itemId);

        $this->getWikiFieldHandler($this->fieldId)->convertFieldTo('a');

        $convertedValue = $this->getFieldValue($itemId, $this->fieldId);
        $pageAExists = $trklib->table('tiki_pages')->fetchOne('pageName', ['pageName' => $legacyPageA]);
        $pageBExists = $trklib->table('tiki_pages')->fetchOne('pageName', ['pageName' => $legacyPageB]);

        $this->assertStringContainsString('page not found', $convertedValue);
        $this->assertNotSame($legacyContentA, $convertedValue);
        $this->assertNotSame($legacyContentB, $convertedValue);
        $this->assertNotEmpty($pageAExists, 'Ambiguous legacy candidate page A must not be consumed or deleted.');
        $this->assertNotEmpty($pageBExists, 'Ambiguous legacy candidate page B must not be consumed or deleted.');
    }

    public function testConvertFieldToTextAreaLogsPreviousValueForHistory()
    {
        $trklib = TikiLib::lib('trk');

        $pageName = 'HistoryPage_' . uniqid();
        $pageContent = 'HistoryContent_' . uniqid();

        $itemData = [
            $this->titleFieldId => 'Title_' . uniqid(),
            $this->fieldId => $pageName,
        ];
        $itemId = $this->createItem($itemData);
        $this->createOrUpdatePage($pageName, $pageContent);

        $this->getWikiFieldHandler($this->fieldId)->convertFieldTo('a');

        $currentValue = $this->getFieldValue($itemId, $this->fieldId);
        $lastVersion = $trklib->last_log_version($itemId);
        $loggedValue = $trklib->table('tiki_tracker_item_field_logs')->fetchOne('value', [
            'version' => $lastVersion,
            'itemId' => $itemId,
            'fieldId' => $this->fieldId,
        ]);

        $this->assertSame($pageContent, $currentValue);
        $this->assertSame($pageName, $loggedValue, 'History log should contain the previous page-name value.');
        $this->assertNotSame($currentValue, $loggedValue, 'Logged value should differ from the converted value.');
    }

    /**
     * Test conversion that throws exception for unsupported types
     */
    public function testConvertUnsupportedType()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unsupported field conversion');

        $this->getWikiFieldHandler($this->fieldId)->convertFieldTo('t');
    }

    private function createItem(array $values): int
    {
        $trklib = TikiLib::lib('trk');
        $def = Tracker_Definition::get($this->trackerId);
        $fields = $def->getFields();

        foreach ($fields as &$field) {
            $fieldId = $field['fieldId'];
            if (array_key_exists($fieldId, $values)) {
                $field['value'] = $values[$fieldId];
            }
        }
        unset($field);

        return $trklib->replace_item($this->trackerId, 0, ['data' => $fields], 'o');
    }

    private function getFieldValue(int $itemId, int $fieldId): string
    {
        $value = TikiLib::lib('trk')->table('tiki_tracker_item_fields')->fetchOne(
            'value',
            [
                'itemId' => $itemId,
                'fieldId' => $fieldId,
            ]
        );

        $this->assertNotFalse($value, "Missing tracker field value for item {$itemId}, field {$fieldId}");

        return (string)$value;
    }

    private function createWikiField(string $name, int $position): int
    {
        return TikiLib::lib('trk')->replace_tracker_field(
            $this->trackerId,
            0,
            $name,
            'wiki',
            'n',
            'n',
            'y',
            'y',
            'n',
            'n',
            $position,
            serialize([
                'fieldIdForPagename' => $this->titleFieldId,
                'namespace' => 'none',
                'wysiwyg' => 'n',
                'samerow' => 1,
                'toolbars' => 1,
            ])
        );
    }

    private function createOrUpdatePage(string $pageName, string $content): void
    {
        $tikilib = TikiLib::lib('tiki');
        if ($tikilib->page_exists($pageName)) {
            $tikilib->update_page($pageName, $content, 'Test', 'admin', '127.0.0.1');
        } else {
            $tikilib->create_page($pageName, 0, $content, $tikilib->now, 'Test', 'admin', '127.0.0.1', '', '', 0);
        }
        $tikilib->invalidate_cache($pageName);
    }

    private function getWikiFieldHandler(int $fieldId): Tracker_Field_Wiki
    {
        $def = Tracker_Definition::get($this->trackerId);
        $fieldInfo = $def->getField($fieldId);
        $fieldInfo['options_map'] = unserialize($fieldInfo['options']);

        return new Tracker_Field_Wiki($fieldInfo, null, $def);
    }
}
