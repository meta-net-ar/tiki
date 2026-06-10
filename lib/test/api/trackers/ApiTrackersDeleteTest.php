<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Trackers;

use TikiLib;

/**
 * Tests for Trackers API DELETE endpoints
 *
 * @group api-integration-test
 * @group api-trackers
 */
class ApiTrackersDeleteTest extends ApiBaseTrackersTest
{
    public function testApiDeleteTrackerWithoutPermission()
    {
        $trackerId = static::$defaultTrackerId;

        $response = $this->makeApiRequest('DELETE', "/trackers/{$trackerId}", null, []);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Reserved for tracker administrators');

        // Verify the tracker still exists
        $trklib = TikiLib::lib('trk');
        $info = $trklib->get_tracker($trackerId);
        $this->assertNotEmpty($info, 'Tracker should still exist after failed delete attempt');
    }

    public function testApiDeleteTracker()
    {
        // Create a dedicated tracker to delete so the default tracker remains available
        $trklib    = TikiLib::lib('trk');
        $trackerId = $trklib->replace_tracker(0, 'API_Tracker_To_Delete_' . uniqid(), 'Tracker to be deleted', [], 'n');
        $this->assertGreaterThan(0, $trackerId, 'Should be able to create tracker for deletion test');
        static::$testTrackers[] = $trackerId;

        $response = $this->makeApiRequest('DELETE', "/trackers/{$trackerId}", 'Admins', []);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidDeleteClearDuplicateTrackerResponse($body);
        $this->assertEquals($trackerId, (int) $body['trackerId'], 'Response trackerId should match deleted tracker');
        $this->assertStringContainsString((string) $trackerId, $body['message'], 'Response message should reference the tracker');

        // Verify the tracker no longer exists
        $info = $trklib->get_tracker($trackerId);
        $this->assertEmpty($info, 'Tracker should no longer exist after deletion');

        // Remove from tracked list since it's already deleted
        $key = array_search($trackerId, static::$testTrackers);
        if ($key !== false) {
            unset(static::$testTrackers[$key]);
        }
    }

    public function testApiDeleteNonExistentTracker()
    {
        $response = $this->makeApiRequest('DELETE', '/trackers/999999', 'Admins', []);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiDeleteTrackerWithItems()
    {
        // Create a tracker with items and verify both are removed
        $trklib    = TikiLib::lib('trk');
        $trackerId = $trklib->replace_tracker(0, 'API_Tracker_Delete_With_Items_' . uniqid(), 'Tracker with items to delete', [], 'n');
        $this->assertGreaterThan(0, $trackerId);
        static::$testTrackers[] = $trackerId;

        $itemId = static::createTrackerItem($trackerId, []);
        $this->assertNotNull($itemId, 'Should be able to create a test item');

        $response = $this->makeApiRequest('DELETE', "/trackers/{$trackerId}", 'Admins', []);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidDeleteClearDuplicateTrackerResponse($body);

        // Verify tracker and item are gone
        $trackerInfo = $trklib->get_tracker($trackerId);
        $this->assertEmpty($trackerInfo, 'Tracker should no longer exist after deletion');

        // Remove from tracked lists since they're already deleted
        $tKey = array_search($trackerId, static::$testTrackers);
        if ($tKey !== false) {
            unset(static::$testTrackers[$tKey]);
        }
        $iKey = array_search($itemId, static::$testTrackerItems);
        if ($iKey !== false) {
            unset(static::$testTrackerItems[$iKey]);
        }
    }

    public function testApiDeleteTrackerFieldsWithoutPermission()
    {
        $trackerId = static::$defaultTrackerId;

        $fieldId = $this->getDefaultFieldId('count');
        $this->assertNotNull($fieldId, 'Default field "count" should exist for field deletion test');

        $response = $this->makeApiRequest('DELETE', "/trackers/{$trackerId}/fields", null, [
            'fields' => [$fieldId],
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Reserved for tracker administrators');

        // Verify the field still exists on the tracker
        $definition = \Tracker_Definition::get($trackerId);
        $this->assertTrue($definition->hasFieldId($fieldId), 'Field should still exist on the tracker after failed delete attempt');
    }

    public function testApiDeleteTrackerFields()
    {
        // Create a dedicated tracker with an extra field so we can safely delete the field
        $trklib    = TikiLib::lib('trk');
        $trackerId = $trklib->replace_tracker(0, 'API_Tracker_FieldDelete_' . uniqid(), 'Tracker for field deletion test', [], 'n');
        $this->assertGreaterThan(0, $trackerId);
        static::$testTrackers[] = $trackerId;

        $fieldId = static::addTrackerField($trackerId, 't', 'Extra Field', 'extra_field_' . uniqid());
        $this->assertNotNull($fieldId, 'Should be able to create a field to delete');

        $response = $this->makeApiRequest('DELETE', "/trackers/{$trackerId}/fields", 'Admins', [
            'fields' => [$fieldId],
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidDeleteTrackerFieldResponse($body);

        // Verify the field no longer exists on the tracker
        $definition = \Tracker_Definition::get($trackerId);
        $this->assertFalse($definition->hasFieldId($fieldId), 'Deleted field should no longer exist on the tracker');
    }

    public function testApiDeleteNonExistentTrackerField()
    {
        $trackerId = static::$defaultTrackerId;

        $response = $this->makeApiRequest('DELETE', "/trackers/{$trackerId}/fields", 'Admins', [
            'fields' => [999999],
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, null, "Field with fieldId: 999999 not found in definition of tracker {$trackerId}");
    }

    public function testApiDeleteTrackerItemWithoutPermission()
    {
        $trackerId = static::$defaultTrackerId;
        $itemId    = $this->getDefaultItemId(0);
        $this->assertNotNull($itemId, 'Default item should exist for item deletion test');

        $response = $this->makeApiRequest('DELETE', "/trackers/{$trackerId}/items/{$itemId}", null, []);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');

        // Verify the item still exists
        $trklib   = TikiLib::lib('trk');
        $itemInfo = $trklib->get_tracker_item($itemId);
        $this->assertNotEmpty($itemInfo, 'Item should still exist after failed delete attempt');
    }

    public function testApiDeleteTrackerItem()
    {
        $trackerId = static::$defaultTrackerId;
        $this->assertNotNull($trackerId, 'Default tracker should exist for item deletion test');

        // Create a dedicated item to delete
        $itemId = static::createTrackerItem($trackerId, [
            'title'       => 'Item To Delete',
            'description' => 'This item will be deleted via API',
            'count'       => '99',
        ]);
        $this->assertNotNull($itemId, 'Should be able to create item for deletion test');

        $response = $this->makeApiRequest('DELETE', "/trackers/{$trackerId}/items/{$itemId}", 'Admins', []);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidDeleteTrackerItemResponse($body);
        $this->assertEquals($trackerId, $body['trackerId'], 'Response trackerId should match');
        $this->assertEquals((string) $itemId, (string) $body['itemId'], 'Response itemId should match deleted item');
        $this->assertEquals(1, $body['removeCount'], 'removeCount should be 1');
        $this->assertEquals(false, $body['multiple'], 'multiple should be false when deleting a single item');

        // Verify the item no longer exists
        $trklib   = TikiLib::lib('trk');
        $itemInfo = $trklib->get_tracker_item($itemId);
        $this->assertEmpty($itemInfo, 'Item should no longer exist after deletion');

        // Remove from tracked items since it's already deleted
        $key = array_search($itemId, static::$testTrackerItems);
        if ($key !== false) {
            unset(static::$testTrackerItems[$key]);
        }
    }

    public function testApiDeleteNonExistentTrackerItem()
    {
        $trackerId = static::$defaultTrackerId;
        $this->assertNotNull($trackerId, 'Default tracker should exist for item deletion test');

        $response = $this->makeApiRequest('DELETE', "/trackers/{$trackerId}/items/999999", 'Admins', []);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiDeleteTrackerItemBelongingToWrongTracker()
    {
        // Create a second tracker and try to delete an item from the default tracker using the second tracker's ID
        $trklib     = TikiLib::lib('trk');
        $tracker2Id = $trklib->replace_tracker(0, 'API_Tracker_WrongOwner_' . uniqid(), 'Second tracker for mismatch test', [], 'n');
        $this->assertGreaterThan(0, $tracker2Id);
        static::$testTrackers[] = $tracker2Id;

        $itemId = $this->getDefaultItemId(0);
        $this->assertNotNull($itemId, 'Default item should exist for item deletion test');

        // Try to delete item from tracker1 using tracker2's route
        $response = $this->makeApiRequest('DELETE', "/trackers/{$tracker2Id}/items/{$itemId}", 'Admins', []);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, "Not found (404)");
    }
}
