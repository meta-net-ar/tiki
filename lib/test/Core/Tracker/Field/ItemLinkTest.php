<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiTests;

use Tracker_Definition,

TikiLib, Tracker_Item;

/**
 * This is a smoke test for the ItemLink fields.  At least it shows how badly we need a better internal API... - benoitg - 2024-07-04
 */
class TrackerItemLinkTest extends \PHPUnit\Framework\TestCase
{
    protected static $trklib;
    protected static $objectlib;
    protected static $unifiedlib;
    protected static $trackerId;
    protected static $linkedTrackerId;
    protected static $old_pref;
    protected static $old_user;

    public static function setUpBeforeClass(): void
    {
        global $prefs;
        self::$old_pref = $prefs['feature_trackers'];
        $prefs['feature_trackers'] = 'y';

        parent::setUpBeforeClass();
        self::$trklib = TikiLib::lib('trk');
        self::$objectlib = TikiLib::lib('object');

        self ::$linkedTrackerId = self::$trklib->replace_tracker(null, 'Test Tracker', '', [], 'n');

        $fields = [[
        'name' => 'Name',
        'type' => 't',
        'isHidden' => 'n',
        'isMandatory' => 'y',
        'visibleBy' => null,
        'permName' => 'test_linked_name',
        ]];
        foreach ($fields as $i => $field) {
            self::$trklib->replace_tracker_field(
                self::$linkedTrackerId,
                0,
                $field['name'],
                $field['type'],
                'y',
                'y',
                'y',
                'y',
                $field['isHidden'],
                $field['isMandatory'],
                ($i + 1) * 10,
                $field['options'] ?? '',
                '',
                '',
                null,
                '',
                null,
                null,
                'n',
                '',
                '',
                '',
                $field['permName']
            );
        }
        $linkedTrackerDefinition = Tracker_Definition::get(self::$linkedTrackerId);
        $linkedFields = $linkedTrackerDefinition->getFields();
        self::$trackerId = self::$trklib->replace_tracker(null, 'Test Tracker', '', [], 'n');
        $options = json_encode(['trackerId' => $linkedFields[0]['trackerId'],
        'fieldId' => $linkedFields[0]['fieldId']]);

        $fields = [[
        'name' => 'Name',
        'type' => 't',
        'isHidden' => 'n',
        'isMandatory' => 'y',
        'visibleBy' => null,
        'permName' => 'test_name'
        ], [
        'name' => 'Link',
        'type' => 'r',
        'isHidden' => 'y',
        'isMandatory' => 'n',
        'visibleBy' => null,
        'permName' => 'test_link',
        'options' => $options
        ]];
        foreach ($fields as $i => $field) {
            self::$trklib->replace_tracker_field(
                self::$trackerId,
                0,
                $field['name'],
                $field['type'],
                'y',
                'y',
                'y',
                'y',
                $field['isHidden'],
                $field['isMandatory'],
                ($i + 1) * 10,
                $field['options'] ?? '',
                '',
                '',
                null,
                '',
                null,
                null,
                'n',
                '',
                '',
                '',
                $field['permName']
            );
        }
    }

    public static function tearDownAfterClass(): void
    {
        global $prefs, $tikilib;
        $prefs['feature_trackers'] = self::$old_pref;

        parent::tearDownAfterClass();
        self::$trklib->remove_tracker(self::$trackerId);
        self::$trklib->remove_tracker(self::$linkedTrackerId);
    }

    public function testBasicFunctionnality(): void
    {
        $linkedItemTitle = 'Linked item title';
        $linkedTrackerDefinition = Tracker_Definition::get(self::$linkedTrackerId);
        $fields = $linkedTrackerDefinition->getFields();
        $fields[0]['value'] = $linkedItemTitle;
        $linkedItemId = self::$trklib->replace_item(self::$linkedTrackerId, 0, ['data' => $fields], 'o');
        $this->assertNotEmpty($linkedItemId);

        $definition = Tracker_Definition::get(self::$trackerId);
        $this->assertNotEmpty($definition);
        $fields = $definition->getFields();
        $fields[0]['value'] = 'Test item';
        $fields[1]['value'] = $linkedItemId;

        $itemId = self::$trklib->replace_item(self::$trackerId, 0, ['data' => $fields], 'o');
        $this->assertNotEmpty($itemId);
        $item = Tracker_Item::fromId($itemId);
        $itemLinkField = $item->getFieldFromPermName('test_link');
        $output = $itemLinkField->renderOutput();
        $this->assertStringContainsString($linkedItemTitle, $output, "The default output is expected to contains the title of the linked item");
    }

    public function testEmptyLink(): void
    {
        $definition = Tracker_Definition::get(self::$trackerId);
        $fields = $definition->getFields();
        $fields[0]['value'] = 'Test item';
        $fields[1]['value'] = '';

        $itemId = self::$trklib->replace_item(self::$trackerId, 0, ['data' => $fields], 'o');
        $this->assertNotEmpty($itemId);
        $item = Tracker_Item::fromId($itemId);
        $itemLinkField = $item->getFieldFromPermName('test_link');
        $output = $itemLinkField->renderOutput();
        $this->assertEquals('', $output, "An empty item link must return an empty label");
    }

    public function testInvalidLink(): void
    {
        $definition = Tracker_Definition::get(self::$trackerId);
        $fields = $definition->getFields();
        $fields[0]['value'] = 'Test item';
        $fields[1]['value'] = 'nonexistent_id';

        $itemId = self::$trklib->replace_item(self::$trackerId, 0, ['data' => $fields], 'o');
        $item = Tracker_Item::fromId($itemId);
        $itemLinkField = $item->getFieldFromPermName('test_link');
        //We know there is a error raised, we supress it
        $output = @$itemLinkField->renderOutput();
        $this->assertStringContainsString('nonexistent_id', $output, "Error message must display the invalid or deleted id");
    }
}
