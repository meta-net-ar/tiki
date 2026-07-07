<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Trackers;

use TikiLib;

/**
 * Tests for Trackers API POST endpoints
 *
 * @group api-integration-test
 * @group api-trackers
 */
class ApiTrackersPostTest extends ApiBaseTrackersTest
{
    public function testApiCreateTrackerWithoutPermission()
    {
        $response = $this->makeApiRequest('POST', '/trackers', null, [
            'name' => 'Unauthorized Tracker',
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Reserved for tracker administrators');
    }

    public function testApiCreateTracker()
    {
        $trackerName = 'API_Created_Tracker_' . uniqid();
        $description = 'A tracker created via API integration test';

        $response = $this->makeApiRequest('POST', '/trackers', 'Admins', [
            'name' => $trackerName,
            'description' => $description,
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidCreateUpdateTrackerResponse($body);
        $newTrackerId = (int) $body['trackerId'];

        // Track for cleanup
        static::$testTrackers[] = $newTrackerId;

        // Verify the tracker exists in the system
        $trklib = TikiLib::lib('trk');
        $info   = $trklib->get_tracker($newTrackerId);
        $this->assertNotEmpty($info, 'Newly created tracker should exist in the database');
        $this->assertEquals($trackerName, $info['name'], 'Tracker name should match');
        $this->assertEquals($description, $info['description'], 'Tracker description should match');
    }

    public function testApiCreateTrackerWithoutNameReturnsError()
    {
        $response = $this->makeApiRequest('POST', '/trackers', 'Admins', []);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 409, '<!--field[name]-->Field Required');
    }

    public function testApiUpdateTrackerWithoutPermission()
    {
        $trackerId = static::$defaultTrackerId;

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}", null, [
            'name' => 'Renamed Without Permission',
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403);

        // Verify the name was not changed in the database
        $trklib = TikiLib::lib('trk');
        $info   = $trklib->get_tracker($trackerId);
        $this->assertNotEmpty($info, 'Tracker should still exist in the database');
        $this->assertNotEquals('Renamed Without Permission', $info['name'], 'Tracker name should not be changed without permission');
    }

    public function testApiUpdateTracker()
    {
        // Create a dedicated tracker so renaming does not affect other tests
        $originalName = 'API_Tracker_To_Update_' . uniqid();
        $updatedName  = 'API_Tracker_Updated_' . uniqid();
        $trackerId = $this::createTracker($originalName, 'Tracker for update test');
        $this->assertNotNull($trackerId, 'Should be able to create a tracker for update test');

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}", 'Admins', [
            'name' => $updatedName,
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidCreateUpdateTrackerResponse($body);

        // Verify the name was updated in the database
        $trklib    = TikiLib::lib('trk');
        $info = $trklib->get_tracker($trackerId);
        $this->assertEquals($updatedName, $info['name'], 'Tracker name should be updated');
    }

    public function testApiUpdateNonExistentTracker()
    {
        $response = $this->makeApiRequest('POST', '/trackers/999999', 'Admins', [
            'name' => 'Non Existent Tracker',
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiClearTrackerWithoutPermission()
    {
        $trackerId = static::$defaultTrackerId;

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}/clear", null, []);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Reserved for tracker administrators');
    }

    public function testApiClearTracker()
    {
        // Create a dedicated tracker and item so clearing doesn't affect other tests
        $trklib    = TikiLib::lib('trk');
        $trackerName = 'API_Tracker_To_Clear_' . uniqid();
        $trackerId = $this::createTracker($trackerName, 'Tracker for clear test');
        $this->assertNotNull($trackerId, 'Should be able to create a tracker for clear test');

        // Create an item to ensure the tracker has data to clear
        $itemId = static::createTrackerItem($trackerId, []);
        $this->assertNotNull($itemId, 'Should be able to create a test item');

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}/clear", 'Admins', []);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidDeleteClearDuplicateTrackerResponse($body);

        // Verify the item was cleared (should no longer exist)
        $itemInfo = $trklib->get_tracker_item($itemId);
        $this->assertEmpty($itemInfo, 'Item should no longer exist after tracker clear');

        // Remove from tracked items since it was deleted by clear
        $key = array_search($itemId, static::$testTrackerItems);
        if ($key !== false) {
            unset(static::$testTrackerItems[$key]);
        }
    }

    public function testApiDuplicateTrackerWithoutPermission()
    {
        $trackerId = static::$defaultTrackerId;

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}/duplicate", null, [
            'name' => 'Duplicate Without Permission',
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Reserved for tracker administrators');
    }

    public function testApiDuplicateTracker()
    {
        $trackerId   = static::$defaultTrackerId;
        $duplicateName = 'API_Tracker_Duplicate_' . uniqid();

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}/duplicate", 'Admins', [
            'name' => $duplicateName,
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidDeleteClearDuplicateTrackerResponse($body);

        $newTrackerId = (int) $body['trackerId'];
        $this->assertGreaterThan(0, $newTrackerId, 'Duplicate tracker ID should be positive');
        $this->assertNotEquals($trackerId, $newTrackerId, 'Duplicate should get a new ID');

        // Track for cleanup
        static::$testTrackers[] = $newTrackerId;
    }

    public function testApiDuplicateTrackerWithoutNameReturnsError()
    {
        $trackerId = static::$defaultTrackerId;

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}/duplicate", 'Admins', []);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 409, '<!--field[name]-->Field Required');
    }

    public function testApiAddTrackerFieldWithoutPermission()
    {
        $trackerId = static::$defaultTrackerId;

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}/fields", null, [
            'name' => 'Unauthorized Field',
            'type' => 't',
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Reserved for tracker administrators');
    }

    public function testApiAddTrackerField()
    {
        $trackerId = static::$defaultTrackerId;

        $fieldName = 'API_Extra_Field_' . uniqid();

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}/fields", 'Admins', [
            'name'     => $fieldName,
            'type'     => 't',
            'permName' => strtolower(str_replace('-', '_', $fieldName)),
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidAddFieldResponse($body);
        $this->assertEquals($fieldName, $body['name'], 'Field name should match');
    }

    public function testApiAddTrackerFieldWithoutNameReturnsError()
    {
        $trackerId = static::$defaultTrackerId;

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}/fields", 'Admins', [
            'type' => 't',
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 409, '<!--field[name]-->Field Required');
    }

    public function testApiEditTrackerFieldWithoutPermission()
    {
        $trackerId = static::$defaultTrackerId;
        $fieldId   = $this->getDefaultFieldId('title');

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}/fields/{$fieldId}", null, [
            'name' => 'Unauthorized Update',
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Reserved for tracker administrators');
    }

    public function testApiEditTrackerField()
    {
        // create a dedicated tracker and field so editing doesn't affect other tests
        $trackerId = $this::createTracker('API_Tracker_For_Field_Edit_' . uniqid(), 'Tracker for field edit test');
        $fieldId   = $this->getDefaultFieldId('description');

        $updatedName = 'Updated Description ' . uniqid();

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}/fields/{$fieldId}", 'Admins', [
            'name' => $updatedName,
            'type' => 't',
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidEditFieldResponse($body);
        $this->assertEquals($fieldId, (int) $body['field']['fieldId'], 'Response fieldId should match edited field');
        $this->assertEquals($trackerId, (int) $body['field']['trackerId'], 'Response trackerId should match');
    }

    public function testApiEditNonExistentTrackerField()
    {
        $trackerId = static::$defaultTrackerId;

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}/fields/999999", 'Admins', [
            'name' => 'Non Existent Field',
            'type' => 't',
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, null, "Field with fieldId: 999999 not found in definition of tracker {$trackerId}");
    }

    public function testApiInsertTrackerItemWithoutPermission()
    {
        $trackerId = static::$defaultTrackerId;

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}/items", null, [
            'trackerId'                     => $trackerId,
            'fields' => [
                $this->getDefaultFieldPermName('title') => 'Unauthorized Item',
            ],
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiInsertTrackerItem()
    {
        // create a dedicated tracker and field so inserting doesn't affect other tests
        $trackerId = $this::createTracker('API_Tracker_For_Item_Insert_' . uniqid(), 'Tracker for item insert test');
        $fields = [
            $this->getDefaultFieldPermName('title') => 'API Inserted Item',
            $this->getDefaultFieldPermName('description') => 'Description from API insert test',
            $this->getDefaultFieldPermName('count') => '42',
        ];

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}/items", 'Admins', [
            'trackerId' => $trackerId,
            'fields'    => $fields,
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidCreateItemResponse($body);
        $newItemId = (int) $body['itemId'];

        // Track for cleanup
        static::$testTrackerItems[] = $newItemId;
    }

    public function testApiInsertTrackerItemIntoNonExistentTracker()
    {
        $response = $this->makeApiRequest('POST', '/trackers/999999/items', 'Admins', [
            'trackerId' => 999999,
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiUpdateTrackerItemWithoutPermission()
    {
        $trackerId = static::$defaultTrackerId;
        $itemId    = $this->getDefaultItemId(0);

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}/items/{$itemId}", null, [
            'fields' => [
                $this->getDefaultFieldPermName('title') => 'Unauthorized Update',
            ],
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiUpdateTrackerItem()
    {
        // create a dedicated tracker and item so updating doesn't affect other tests
        $trackerId = $this::createTracker('API_Tracker_For_Item_Update_' . uniqid(), 'Tracker for item update test');

        // Create a dedicated item for update
        $itemId = static::createTrackerItem($trackerId, [
            'title'       => 'Item Before Update',
            'description' => 'This item will be updated',
            'count'       => '5',
        ]);
        $this->assertNotNull($itemId, 'Should be able to create item for update test');

        $updatedTitle = 'Item After API Update ' . uniqid();

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}/items/{$itemId}", 'Admins', [
            'fields' => [
                $this->getDefaultFieldPermName('title') => $updatedTitle,
            ],
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidUpdateItemResponse($body);
        $this->assertEquals($itemId, (int) $body['itemId'], 'Returned itemId should match');
    }

    public function testApiUpdateNonExistentTrackerItem()
    {
        $trackerId = static::$defaultTrackerId;

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}/items/999999", 'Admins', [
            'fields' => [
                $this->getDefaultFieldPermName('title') => 'Update Non Existent Item',
            ],
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiUpdateTrackerItemStatusWithoutPermission()
    {
        $trackerId = static::$defaultTrackerId;
        $itemId    = $this->getDefaultItemId(0);

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}/items/{$itemId}/status", null, [
            'status' => 'c',
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiUpdateTrackerItemStatus()
    {
        $trackerId = static::$defaultTrackerId;

        // Create a dedicated item
        $itemId = static::createTrackerItem($trackerId, [
            'title' => 'Item For Status Update',
        ]);
        $this->assertNotNull($itemId, 'Should be able to create item for status update test');

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}/items/{$itemId}/status", 'Admins', [
            'status' => 'c',
        ]);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidUpdateItemStatusResponse($body);
    }

    public function testApiUpdateTrackerItemStatusNonExistent()
    {
        $trackerId = static::$defaultTrackerId;

        $response = $this->makeApiRequest('POST', "/trackers/{$trackerId}/items/999999/status", 'Admins', [
            'status' => 'c',
        ]);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }
}
