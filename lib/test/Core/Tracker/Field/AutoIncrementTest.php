<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiTests;

use Services_Tracker_Utilities;
use Tiki\Lib\Test\TestHelpers\GuardedSetUpTrait;
use Tiki\Lib\Test\TestHelpers\Tracker\TrackerFixtureFactory;
use TikiLib;
use TikiTestCase;
use Tracker_Definition;

/**
 * Regression test for AutoIncrement (type 'q') value generation.
 *
 * Commit 51b8ef7981 changed the $old_value derivation in
 * TrackerLib::replace_item() so that a new item's old value became '' instead
 * of null. Tracker_Field_AutoIncrement::handleSave() only generates when
 * is_null($oldValue), so AutoIncrement fields silently stored nothing for
 * every item created on any insert path (web form, services API, data-array).
 *
 * These tests drive Services_Tracker_Utilities::insertItem()/updateItem() —
 * the same path a browser form submission uses — plus the plain data-array
 * path, and assert that the value is generated on create, sequential across
 * items, and preserved on update.
 *
 * @group integration
 * @group tracker
 */
class TrackerFieldAutoIncrementTest extends TikiTestCase
{
    use GuardedSetUpTrait;

    protected static $trklib;
    protected static $fixtures;
    protected static $old_prefs;

    public static function setUpBeforeClass(): void
    {
        self::guardStaticSetUp(function () {
            global $prefs;
            self::$old_prefs = $prefs;
            $prefs['feature_trackers'] = 'y';
            $prefs['trackerfield_autoincrement'] = 'y';
            $prefs['trackerfield_text'] = 'y';
            parent::setUpBeforeClass();
            self::$trklib = TikiLib::lib('trk');
            self::$fixtures = new TrackerFixtureFactory();
        });
    }

    public static function tearDownAfterClass(): void
    {
        self::$fixtures->cleanup();
        global $prefs;
        $prefs = self::$old_prefs;
        parent::tearDownAfterClass();
    }

    /**
     * Fresh tracker per test so generated sequences are deterministic.
     *
     * @return array{0: int, 1: int, 2: Tracker_Definition} trackerId, auto fieldId, definition
     */
    private function createAutoTracker(array $autoFieldOptions = []): array
    {
        $trackerId = self::$fixtures->createTracker('AutoIncrement regression test');
        $fieldIds = self::$fixtures->addFields($trackerId, [
            [
                'name' => 'Auto field',
                'permName' => 'autoIncField',
                'type' => 'q',
                'options' => $autoFieldOptions,
            ],
            [
                'name' => 'Text field',
                'permName' => 'autoIncText',
                'type' => 't',
            ],
        ]);

        $definition = Tracker_Definition::get($trackerId, false);
        $this->assertNotNull($definition);

        return [$trackerId, $fieldIds['autoIncField'], $definition];
    }

    private function autoValue(int $trackerId, int $itemId, int $fieldId): mixed
    {
        return self::$trklib->get_item_value($trackerId, $itemId, $fieldId, false);
    }

    public function testGeneratesSequentialValuesOnCreateViaServicesPath(): void
    {
        [$trackerId, $autoFieldId, $definition] = $this->createAutoTracker();
        $utilities = new Services_Tracker_Utilities();

        // Mimic a form submit: only the text field is posted, the
        // auto-increment input is read-only and absent from the request.
        $firstItemId = $utilities->insertItem($definition, [
            'status' => 'o',
            'fields' => ['autoIncText' => 'first'],
        ]);
        $this->assertGreaterThan(0, $firstItemId);
        $this->assertSame('1', (string) $this->autoValue($trackerId, $firstItemId, $autoFieldId));

        $secondItemId = $utilities->insertItem($definition, [
            'status' => 'o',
            'fields' => ['autoIncText' => 'second'],
        ]);
        $this->assertSame('2', (string) $this->autoValue($trackerId, $secondItemId, $autoFieldId));
    }

    public function testPreservesValueOnUpdateViaServicesPath(): void
    {
        [$trackerId, $autoFieldId, $definition] = $this->createAutoTracker();
        $utilities = new Services_Tracker_Utilities();

        $itemId = $utilities->insertItem($definition, [
            'status' => 'o',
            'fields' => ['autoIncText' => 'before update'],
        ]);
        $this->assertSame('1', (string) $this->autoValue($trackerId, $itemId, $autoFieldId));

        $utilities->updateItem($definition, [
            'itemId' => $itemId,
            'status' => 'o',
            'fields' => ['autoIncText' => 'after update'],
            'notify_watchers' => false,
        ]);
        $this->assertSame('1', (string) $this->autoValue($trackerId, $itemId, $autoFieldId));
    }

    public function testGeneratesOnCreateViaDataArrayPath(): void
    {
        // The plain replace_item(['data' => ...]) path used by fixtures and
        // parts of the codebase must generate too.
        [$trackerId, $autoFieldId] = $this->createAutoTracker();

        $itemId = self::$fixtures->createItem($trackerId, ['autoIncText' => 'data array path']);
        $this->assertSame('1', (string) $this->autoValue($trackerId, $itemId, $autoFieldId));
    }

    public function testItemIdModeStoresTheItemId(): void
    {
        [$trackerId, $autoFieldId, $definition] = $this->createAutoTracker(['itemId' => 'itemId']);
        $utilities = new Services_Tracker_Utilities();

        $itemId = $utilities->insertItem($definition, [
            'status' => 'o',
            'fields' => ['autoIncText' => 'itemId mode'],
        ]);
        $this->assertSame((string) $itemId, (string) $this->autoValue($trackerId, $itemId, $autoFieldId));
    }
}
