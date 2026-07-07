<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\IntegrationTests;

use Services_Tracker_Utilities;
use TikiLib;
use TikiTestCase;
use Tracker_Definition;

/**
 * @group integration
 */
class TrackerDropdownEmptyValueUpdateTest extends TikiTestCase
{
    protected static $trklib;
    protected static $trackerId;
    protected static $oldPrefs;

    public static function setUpBeforeClass(): void
    {
        global $prefs;

        self::$oldPrefs = $prefs;
        $prefs['feature_trackers'] = 'y';

        parent::setUpBeforeClass();
        self::$trklib = TikiLib::lib('trk');
        self::$trackerId = self::$trklib->replace_tracker(null, 'Dropdown Empty Value Test ' . uniqid(), '', [], 'n');

        $fields = [
            [
                'name' => 'Title',
                'type' => 't',
                'isHidden' => 'n',
                'isMandatory' => 'y',
                'permName' => 'test_title',
            ],
            [
                'name' => 'Statuses',
                'type' => 'M',
                'isHidden' => 'n',
                'isMandatory' => 'n',
                'permName' => 'test_statuses',
                'options' => json_encode([
                    'options' => ['=Default', '1=Active', '2=Delayed', '3=Closed'],
                ]),
            ],
        ];

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
        global $prefs;

        if (! empty(self::$trackerId)) {
            self::$trklib->remove_tracker(self::$trackerId);
        }

        $prefs = self::$oldPrefs;
        parent::tearDownAfterClass();
    }

    public function testUpdateItemAllowsSavingValidEmptyDropdownValue(): void
    {
        $itemId = $this->createItem('1');
        $definition = Tracker_Definition::get(self::$trackerId);
        $fieldInfo = $definition->getFieldFromPermName('test_statuses');
        $itemInfo = self::$trklib->get_tracker_item($itemId);
        $handler = $definition->getFieldFactory()->getHandler($fieldInfo, $itemInfo);

        $fieldData = $handler->getFieldData([
            'ins_' . $fieldInfo['fieldId'] => ['', '1', '2'],
        ]);

        $this->assertSame(',1,2', $fieldData['value']);
        $this->assertSame(['', '1', '2'], $fieldData['selected']);

        $utilities = new Services_Tracker_Utilities();
        $result = $utilities->updateItem(
            $definition,
            [
                'itemId' => $itemId,
                'status' => 'o',
                'fields' => [
                    'test_statuses' => $fieldData['value'],
                ],
                'validate' => true,
                'notify_watchers' => null,
            ]
        );

        $this->assertNotFalse($result);

        $updatedItem = self::$trklib->get_tracker_item($itemId);
        $this->assertSame(',1,2', $updatedItem[$fieldInfo['fieldId']]);

        $updatedHandler = $definition->getFieldFactory()->getHandler($fieldInfo, $updatedItem);
        $this->assertTrue($updatedHandler->isValid() === true);
    }

    private function createItem(string $statusesValue): int
    {
        $definition = Tracker_Definition::get(self::$trackerId);
        $fields = $definition->getFields();

        foreach ($fields as &$field) {
            if ($field['permName'] === 'test_title') {
                $field['value'] = 'Test item';
            }

            if ($field['permName'] === 'test_statuses') {
                $field['value'] = $statusesValue;
            }
        }

        return self::$trklib->replace_item(self::$trackerId, 0, ['data' => $fields], 'o');
    }
}
