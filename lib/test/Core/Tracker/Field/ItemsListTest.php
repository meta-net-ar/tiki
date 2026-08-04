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
 * Behavior tests for the ItemsList (type 'l') transfer-list save.
 *
 * Since commit 51b8ef7981 (dual list / transfer mode), submitting an
 * ItemsList field as an array of item IDs synchronizes the *other* tracker's
 * ItemLink field: newly selected items get linked to this item, deselected
 * ones get unlinked. ItemsList itself stores no value ('' always) — the
 * previous state must be derived from the currently linked items.
 *
 * Two kinds of tests here:
 *
 * - The transfer feature as shipped: the UI path always submits the field
 *   as an array (the controller runs input through getFieldData(), which
 *   returns the submitted array or the derived current-items array), and a
 *   save that omits the field must leave the links untouched.
 *
 * - Saves that do NOT have that UI shape: a plain data-array save (import,
 *   fixture, replace_item callers) carries the field's stored scalar ''
 *   and must be a no-op for the links, not a fatal.
 *
 * @group integration
 * @group tracker
 */
class TrackerFieldItemsListTest extends TikiTestCase
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
            $prefs['trackerfield_itemslist'] = 'y';
            $prefs['trackerfield_itemlink'] = 'y';
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
     * Two linked trackers, fresh per test, WITHOUT the ItemsList field yet:
     * - "here" will hold the ItemsList field (no fieldIdHere -> matches by itemId)
     * - "there" holds a single-value ItemLink field pointing back at "here"
     *
     * The ItemsList field is added separately via addItemsListField() so each
     * test controls whether items are created before or after it exists.
     *
     * @return array{0: int, 1: int, 2: array} hereTrackerId, thereTrackerId,
     *         there fieldIds by permName
     */
    private function createLinkedTrackers(): array
    {
        $hereTrackerId = self::$fixtures->createTracker('ItemsList transfer test, here');
        $hereNameFieldId = self::$fixtures->addField($hereTrackerId, [
            'name' => 'Name',
            'permName' => 'ilsHereName',
            'type' => 't',
        ]);

        $thereTrackerId = self::$fixtures->createTracker('ItemsList transfer test, there');
        $thereFieldIds = self::$fixtures->addFields($thereTrackerId, [
            [
                'name' => 'Name',
                'permName' => 'ilsThereName',
                'type' => 't',
            ],
            [
                'name' => 'Link back',
                'permName' => 'ilsLinkBack',
                'type' => 'r',
                'options' => [
                    'trackerId' => $hereTrackerId,
                    'fieldId' => $hereNameFieldId,
                ],
            ],
        ]);

        return [$hereTrackerId, $thereTrackerId, $thereFieldIds];
    }

    private function addItemsListField(int $hereTrackerId, int $thereTrackerId, array $thereFieldIds): int
    {
        return self::$fixtures->addField($hereTrackerId, [
            'name' => 'Items list',
            'permName' => 'ilsItemsList',
            'type' => 'l',
            'options' => [
                'trackerId' => $thereTrackerId,
                'fieldIdThere' => $thereFieldIds['ilsLinkBack'],
                // multi-value option ('separator' => '|'): stored as an array
                // when saved through the admin UI, and getItemLabels() does
                // not guard the scalar shape
                'displayFieldIdThere' => [$thereFieldIds['ilsThereName']],
                'status' => 'opc',
                'useTransfer' => 1,
            ],
        ]);
    }

    private function linkBackValue(int $thereTrackerId, int $itemId, int $linkBackFieldId): string
    {
        return (string) self::$trklib->get_item_value($thereTrackerId, $itemId, $linkBackFieldId, false);
    }

    public function testTransferSaveLinksAndUnlinksItems(): void
    {
        [$hereTrackerId, $thereTrackerId, $thereFieldIds] = $this->createLinkedTrackers();
        $linkBackFieldId = $thereFieldIds['ilsLinkBack'];

        $parent = self::$fixtures->createItem($hereTrackerId, ['ilsHereName' => 'parent']);
        $one = self::$fixtures->createItem($thereTrackerId, ['ilsThereName' => 'one']);
        $two = self::$fixtures->createItem($thereTrackerId, ['ilsThereName' => 'two']);
        $three = self::$fixtures->createItem($thereTrackerId, ['ilsThereName' => 'three']);

        $itemsListFieldId = $this->addItemsListField($hereTrackerId, $thereTrackerId, $thereFieldIds);
        $definition = Tracker_Definition::get($hereTrackerId, false);
        $utilities = new Services_Tracker_Utilities();

        // The transfer UI submits the target list as an array of item IDs
        $utilities->updateItem($definition, [
            'itemId' => $parent,
            'status' => 'o',
            'fields' => ['ilsItemsList' => [(string) $one, (string) $two]],
            'notify_watchers' => false,
        ]);

        $this->assertSame((string) $parent, $this->linkBackValue($thereTrackerId, $one, $linkBackFieldId));
        $this->assertSame((string) $parent, $this->linkBackValue($thereTrackerId, $two, $linkBackFieldId));
        $this->assertSame('', $this->linkBackValue($thereTrackerId, $three, $linkBackFieldId));

        // Deselect "one", keep "two", add "three": the previous state is not
        // stored anywhere on the ItemsList field — it must be derived from the
        // currently linked items for the diff to unlink "one".
        $utilities->updateItem($definition, [
            'itemId' => $parent,
            'status' => 'o',
            'fields' => ['ilsItemsList' => [(string) $two, (string) $three]],
            'notify_watchers' => false,
        ]);

        $this->assertSame('', $this->linkBackValue($thereTrackerId, $one, $linkBackFieldId));
        $this->assertSame((string) $parent, $this->linkBackValue($thereTrackerId, $two, $linkBackFieldId));
        $this->assertSame((string) $parent, $this->linkBackValue($thereTrackerId, $three, $linkBackFieldId));

        // The ItemsList field itself never stores a value
        $this->assertSame('', (string) self::$trklib->get_item_value($hereTrackerId, $parent, $itemsListFieldId, false));
    }

    public function testSaveWithoutTheFieldLeavesLinksAlone(): void
    {
        [$hereTrackerId, $thereTrackerId, $thereFieldIds] = $this->createLinkedTrackers();
        $linkBackFieldId = $thereFieldIds['ilsLinkBack'];

        $parent = self::$fixtures->createItem($hereTrackerId, ['ilsHereName' => 'parent']);
        $one = self::$fixtures->createItem($thereTrackerId, ['ilsThereName' => 'one']);

        $this->addItemsListField($hereTrackerId, $thereTrackerId, $thereFieldIds);
        $definition = Tracker_Definition::get($hereTrackerId, false);
        $utilities = new Services_Tracker_Utilities();

        $utilities->updateItem($definition, [
            'itemId' => $parent,
            'status' => 'o',
            'fields' => ['ilsItemsList' => [(string) $one]],
            'notify_watchers' => false,
        ]);
        $this->assertSame((string) $parent, $this->linkBackValue($thereTrackerId, $one, $linkBackFieldId));

        // A save that does not include the ItemsList field (e.g. an API
        // update of just the name) must not touch the links — and must not
        // fatal in handleSave() on the non-array value.
        $utilities->updateItem($definition, [
            'itemId' => $parent,
            'status' => 'o',
            'fields' => ['ilsHereName' => 'parent renamed'],
            'notify_watchers' => false,
        ]);
        $this->assertSame((string) $parent, $this->linkBackValue($thereTrackerId, $one, $linkBackFieldId));
    }

    public function testDataArraySaveCarryingTheStoredScalarDoesNotFatal(): void
    {
        [$hereTrackerId, $thereTrackerId, $thereFieldIds] = $this->createLinkedTrackers();
        $linkBackFieldId = $thereFieldIds['ilsLinkBack'];

        $one = self::$fixtures->createItem($thereTrackerId, ['ilsThereName' => 'one']);

        // Field exists before the item: a plain data-array create (import,
        // fixture, resave) includes every definition field, so the ItemsList
        // field arrives as the scalar '' — not the array the transfer UI sends.
        $itemsListFieldId = $this->addItemsListField($hereTrackerId, $thereTrackerId, $thereFieldIds);

        $parent = self::$fixtures->createItem($hereTrackerId, ['ilsHereName' => 'parent']);

        $this->assertGreaterThan(0, $parent);
        $this->assertSame('', (string) self::$trklib->get_item_value($hereTrackerId, $parent, $itemsListFieldId, false));
        $this->assertSame('', $this->linkBackValue($thereTrackerId, $one, $linkBackFieldId));
    }
}
