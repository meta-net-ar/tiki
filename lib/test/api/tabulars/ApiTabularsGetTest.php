<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Tabulars;

use TikiLib;

/**
 * Tests for Tabulars API GET endpoints
 *
 * @group api-integration-test
 * @group api-tabulars
 */
class ApiTabularsGetTest extends ApiBaseTabularsTest
{
    public function testApiGetTabularsWithoutPermission()
    {
        $response = $this->makeApiRequest('GET', '/tabulars', null);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiGetTabulars()
    {
        $response = $this->makeApiRequest('GET', '/tabulars', 'Admins');

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidTabularListResponse($body);
        $this->assertEquals('Import-Export Formats', $body['title'], 'Tabular list title should be correct');
        $this->assertGreaterThanOrEqual(1, count($body['formatList']), 'Tabular list should have at least one entry');
    }

    public function testApiGetTabularInfoWithoutPermission()
    {
        $tabularId = static::$defaultTabularId;
        $response = $this->makeApiRequest('GET', "/tabulars/{$tabularId}", null);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiGetTabularInfo()
    {
        $tabularId = static::$defaultTabularId;
        $response = $this->makeApiRequest('GET', "/tabulars/{$tabularId}", 'Admins');

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidTabularResponse($body);

        $this->assertEquals('Edit Format: API_Tracker_Tabular_Format', $body['title'], 'Should return correct title');
        $this->assertEquals(static::$defaultTabularId, $body['tabularId'], 'Should return correct tabularId');
        $this->assertEquals(static::$defaultTrackerId, $body['trackerId'], 'Should return correct trackerId');
        $this->assertEquals('API_Tracker_Tabular_Format', $body['name'], 'Should return correct name');

        $expectedColumns = [
            'API_Tracker_Tabular_title_field',
            'API_Tracker_Tabular_desc_field',
            'API_Tracker_Tabular_count_field',
        ];
        $columnNames = array_map(function ($col) {
            return $col['field'];
        }, $body['columns']);

        $this->assertEquals($expectedColumns, $columnNames, 'Tabular columns should match expected columns');
    }

    public function testApiGetNonExistentTabularInfo()
    {
        $nonExistentId = 999999;

        $response = $this->makeApiRequest('GET', "/tabulars/{$nonExistentId}", 'Admins');
        $body = $this->getResponseBody($response);

        $this->assertValidErrorResponse($body, 404, "Format {$nonExistentId} not found (404)");
    }

    public function testApiGetExportTabularWithoutPermission()
    {
        $tabularId = static::$defaultTabularId;
        $response = $this->makeApiRequest('GET', "/tabulars/{$tabularId}/export", null);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');
    }

    public function testApiGetExportTabular()
    {
        $trklib = TikiLib::lib('trk');
        $trackerId = static::$defaultTrackerId;
        $definition = \Tracker_Definition::get($trackerId);
        $this->assertNotEmpty($definition);
        $fields = $definition->getFields();

        for ($i = 1; $i <= 3; $i++) {
             $fields[0]['value'] = "Title $i";
             $fields[1]['value'] = "Description $i";
             $fields[2]['value'] = $i * 10;
             $itemId = $trklib->replace_item($trackerId, 0, ['data' => $fields]);

             // track created items for cleanup
             static::$testTrackerItems[] = $itemId;
        }

        $tabularId = static::$defaultTabularId;
        $response = $this->makeApiRequest('GET', "/tabulars/{$tabularId}/export", 'Admins');

        $this->assertNotEmpty($response['body'], 'Exported content should not be empty');

        $content = $response['body'];
        $rows = $this->parseCsvContent($content);

        $this->assertGreaterThanOrEqual(4, count($rows), 'Should have at least 4 rows (headers + data)');
    }

    public function testApiGetExportNonExistentTabular()
    {
        $nonExistentId = 999999;

        $response = $this->makeApiRequest('GET', "/tabulars/{$nonExistentId}/export", 'Admins');
        $body = $this->getResponseBody($response);

        $this->assertValidErrorResponse($body, 404, "Format {$nonExistentId} not found (404)");
    }
}
