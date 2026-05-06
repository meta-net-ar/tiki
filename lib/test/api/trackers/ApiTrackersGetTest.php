<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Trackers;

/**
 * Tests for Trackers API GET endpoints
 *
 * @group api-integration-test
 * @group api-trackers
 */
class ApiTrackersGetTest extends ApiBaseTrackersTest
{
    public function testApiListTrackers()
    {
        $response = $this->makeApiRequest('GET', '/trackers', 'Admins');

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidTrackerListResponse($body);
    }

    public function testApiListTrackersAsAnonymous()
    {
        // Anonymous users can list trackers they have access to view
        $response = $this->makeApiRequest('GET', '/trackers', null);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidEmptyTrackerListResponse($body);
    }

    public function testApiListTrackersContainsDefaultTracker()
    {
        $trackerId = static::$defaultTrackerId;
        $this->assertNotNull($trackerId, 'Default tracker should exist for listing test');

        $response = $this->makeApiRequest('GET', '/trackers', 'Admins');

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $trackerIds = array_map('intval', array_column($body['data'], 'trackerId'));
        $this->assertContains((int) $trackerId, $trackerIds, 'Default tracker should appear in the list');
    }

    public function testApiGetTrackerItems()
    {
        $trackerId = static::$defaultTrackerId;
        $this->assertNotNull($trackerId, 'Default tracker should exist for item listing test');

        $response = $this->makeApiRequest('GET', "/trackers/{$trackerId}", 'Admins');

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidTrackerItemsListResponse($body);
        $this->assertEquals($trackerId, $body['trackerId']);
    }

    public function testApiGetTrackerItemsWithoutPermission()
    {
        $trackerId = static::$defaultTrackerId;
        $this->assertNotNull($trackerId, 'Default tracker should exist for item listing test');

        $response = $this->makeApiRequest('GET', "/trackers/{$trackerId}", null);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Reserved for tracker administrators');
    }

    public function testApiGetNonExistentTracker()
    {
        $response = $this->makeApiRequest('GET', '/trackers/999999', 'Admins');

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Not found (404)');
    }

    public function testApiViewTrackerItem()
    {
        $trackerId = static::$defaultTrackerId;
        $itemId    = $this->getDefaultItemId(0);
        $this->assertNotNull($trackerId, 'Default tracker should exist for item view test');
        $this->assertNotNull($itemId, 'Default item should exist for item view test');

        $response = $this->makeApiRequest('GET', "/trackers/{$trackerId}/items/{$itemId}", 'Admins');

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidTrackerItemViewResponse($body);
        $this->assertEquals($itemId, $body['itemId'], 'Returned item ID should match requested ID');
        $this->assertEquals($trackerId, $body['trackerId'], 'Returned tracker ID should match');
    }

    public function testApiViewTrackerItemWithoutPermission()
    {
        $trackerId = static::$defaultTrackerId;
        $itemId    = $this->getDefaultItemId(0);
        $this->assertNotNull($trackerId, 'Default tracker should exist for item view test');
        $this->assertNotNull($itemId, 'Default item should exist for item view test');

        $response = $this->makeApiRequest('GET', "/trackers/{$trackerId}/items/{$itemId}", null);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiViewNonExistentTrackerItem()
    {
        $trackerId = static::$defaultTrackerId;
        $this->assertNotNull($trackerId, 'Default tracker should exist for item view test');

        $response = $this->makeApiRequest('GET', "/trackers/{$trackerId}/items/999999", 'Admins');

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, 'Item not found (404)');
    }

    public function testApiGetTrackerFields()
    {
        $trackerId = static::$defaultTrackerId;
        $this->assertNotNull($trackerId, 'Default tracker should exist for fields listing test');

        $response = $this->makeApiRequest('GET', "/trackers/{$trackerId}/fields", 'Admins');

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidTrackerFieldsListResponse($body);
    }

    public function testApiGetTrackerFieldsWithoutPermission()
    {
        $trackerId = static::$defaultTrackerId;
        $this->assertNotNull($trackerId, 'Default tracker should exist for fields listing test');

        $response = $this->makeApiRequest('GET', "/trackers/{$trackerId}/fields", null);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, "You don't have permission to view the tracker");
    }

    public function testApiGetTrackerFieldsContainsCreatedFields()
    {
        $trackerId = static::$defaultTrackerId;
        $this->assertNotNull($trackerId, 'Default tracker should exist for fields listing test');

        $response = $this->makeApiRequest('GET', "/trackers/{$trackerId}/fields", 'Admins');

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $fieldNames = array_column($body['fields'], 'name');
        $this->assertContains('Title', $fieldNames, 'Title field should be present');
        $this->assertContains('Description', $fieldNames, 'Description field should be present');
        $this->assertContains('Count', $fieldNames, 'Count field should be present');
    }

    public function testApiExportTrackerFields()
    {
        $trackerId = static::$defaultTrackerId;
        $this->assertNotNull($trackerId, 'Default tracker should exist for fields export test');

        $response = $this->makeApiRequest('GET', "/trackers/{$trackerId}/fields/export", 'Admins');

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);

        $this->assertValidExportFieldsResponse($body);
        $this->assertEquals($trackerId, $body['trackerId']);
    }

    public function testApiExportTrackerFieldsWithoutPermission()
    {
        $trackerId = static::$defaultTrackerId;
        $this->assertNotNull($trackerId, 'Default tracker should exist for fields export test');

        $response = $this->makeApiRequest('GET', "/trackers/{$trackerId}/fields/export", null);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, "Reserved for tracker administrators");
    }

    public function testApiDumpTrackerItemsWithoutPermission()
    {
        $trackerId = static::$defaultTrackerId;
        $this->assertNotNull($trackerId, 'Default tracker should exist for dump test');

        $response = $this->makeApiRequest('GET', "/trackers/{$trackerId}/dump", null);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, "You don't have permission to export");
    }

    public function testApiGetTrackerItemHistory()
    {
        $trackerId = static::$defaultTrackerId;
        $itemId    = $this->getDefaultItemId(0);
        $this->assertNotNull($trackerId, 'Default tracker should exist for item history test');
        $this->assertNotNull($itemId, 'Default item should exist for item history test');

        $response = $this->makeApiRequest('GET', "/trackers/{$trackerId}/items/{$itemId}/history", 'Admins');

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertValidTrackerItemHistoryResponse($body);
    }

    public function testApiGetTrackerItemHistoryWithoutPermission()
    {
        $trackerId = static::$defaultTrackerId;
        $itemId    = $this->getDefaultItemId(0);
        $this->assertNotNull($trackerId, 'Default tracker should exist for item history test');
        $this->assertNotNull($itemId, 'Default item should exist for item history test');

        $response = $this->makeApiRequest('GET', "/trackers/{$trackerId}/items/{$itemId}/history", null);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 401, 'You do not have permission to view this page.');
    }
}
