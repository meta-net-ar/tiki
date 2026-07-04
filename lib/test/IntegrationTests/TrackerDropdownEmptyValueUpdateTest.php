<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\IntegrationTests;

use Services_Tracker_Utilities;
use Tiki\Lib\Test\TestHelpers\GuardedSetUpTrait;
use Tiki\Lib\Test\TestHelpers\Tracker\TrackerFixtureFactory;
use TikiLib;
use TikiTestCase;
use Tracker_Definition;

/**
 * @group integration
 */
class TrackerDropdownEmptyValueUpdateTest extends TikiTestCase
{
    use GuardedSetUpTrait;

    protected static $trklib;
    protected static $fixtures;
    protected static $trackerId;
    protected static $oldPrefs;

    public static function setUpBeforeClass(): void
    {
        self::guardStaticSetUp(function () {
            global $prefs;

            self::$oldPrefs = $prefs;
            $prefs['feature_trackers'] = 'y';

            parent::setUpBeforeClass();
            self::$trklib = TikiLib::lib('trk');
            self::$fixtures = new TrackerFixtureFactory();
            self::$trackerId = self::$fixtures->createTracker('Dropdown Empty Value Test ' . uniqid());

            self::$fixtures->addFields(self::$trackerId, [
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
            ]);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->abortIfStaticSetUpFailed();
    }

    public static function tearDownAfterClass(): void
    {
        global $prefs;

        self::$fixtures->cleanup();

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
        return self::$fixtures->createItem(self::$trackerId, [
            'test_title' => 'Test item',
            'test_statuses' => $statusesValue,
        ]);
    }
}
