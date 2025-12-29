<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Tabulars;

use TikiLib;

/**
 * Tests for Tabulars API POST endpoints
 *
 * @group api-integration-test
 * @group api-tabulars
 */
class ApiTabularsPostTest extends ApiBaseTabularsTest
{
    public function testApiImportCsvTabularWithoutPermission()
    {
        $tabularId = static::$defaultTabularId;

        $filename = 'testdata.csv';
        $filepath = __DIR__ . '/assets/' . $filename;
        $data = ["file" => $filepath, "separator" => ","];
        $response = $this->makeApiRequest('POST', "/tabulars/{$tabularId}/import", null, $data, 'multipart/form-data');

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');

        // Verify that no items were imported
        $trackerId = static::$defaultTrackerId;
        $trklib = TikiLib::lib('trk');
        $items = $trklib->get_all_tracker_items($trackerId);
        $this->assertCount(0, $items, 'Tracker should have 0 items after failed import due to permission denied');
    }

    public function testApiImportCsvTabular()
    {
        $filename = 'testdata.csv';
        $filepath = __DIR__ . '/assets/' . $filename;
        $tabularId = static::createTrackerTabular('API_Tracker_Import_Tabular');

        $data = ["file" => $filepath, "separator" => ","];
        $response = $this->makeApiRequest('POST', "/tabulars/{$tabularId}/import", 'Admins', $data, 'multipart/form-data');

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertValidImportResponse($body);
        $this->assertCount(3, $body, 'Imported rows should be 3');

        $numbers = ["One", "Two", "Three"];
        foreach ($body as $index => $row) {
            $this->assertEquals("Title " . $numbers[$index], $row['API_Tracker_Import_Tabular_title_field'], "Row " . ($index + 1) . " Title should match");
            $this->assertEquals("Description " . $numbers[$index], $row['API_Tracker_Import_Tabular_desc_field'], "Row " . ($index + 1) . " Description should match");
            $this->assertEquals(($index + 1) * 10, (int)$row['API_Tracker_Import_Tabular_count_field'], "Row " . ($index + 1) . " Count should match");
        }

        // Verify that items were actually imported into the tracker
        $info = TikiLib::lib('tabular')->getInfo($tabularId);
        $trackerId = $info['trackerId'];

        $trklib = TikiLib::lib('trk');
        $items = $trklib->get_all_tracker_items($trackerId);
        $this->assertCount(3, $items, 'Tracker should have 3 items after successful import');
    }

    public function testApiImportTabularWithoutFile()
    {
        $tabularId = static::$defaultTabularId;
        $response = $this->makeApiRequest('POST', "/tabulars/{$tabularId}/import", 'Admins', []);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 409, '<!--field[file]-->Field Required');
    }

    public function testApiImportInvalidCsvTabular()
    {
        $csvContent = "Title,Description\nItem1";  // Missing "Count" column
        $tmpFile = $this->createTempCsvFile($csvContent);

        $tabularId = static::$defaultTabularId;
        $data = ["file" => $tmpFile, "separator" => ","];

        $response = $this->makeApiRequest('POST', "/tabulars/{$tabularId}/import", 'Admins', $data, 'multipart/form-data');
        @unlink($tmpFile);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 0, 'Expected header "Count" not found.');

        // Verify that no items were imported
        $trackerId = static::$defaultTrackerId;
        $trklib = TikiLib::lib('trk');
        $items = $trklib->get_all_tracker_items($trackerId);
        $this->assertCount(0, $items, 'Tracker should have 0 items after failed import due to invalid CSV');
    }

    public function testApiImportEmptyCsvTabular()
    {
        $csvContent = "Title,Description,Count\n";  // No data rows
        $tmpFile = $this->createTempCsvFile($csvContent);

        $tabularId = static::$defaultTabularId;
        $data = ["file" => $tmpFile, "separator" => ","];

        $response = $this->makeApiRequest('POST', "/tabulars/{$tabularId}/import", 'Admins', $data, 'multipart/form-data');
        @unlink($tmpFile);

        $body = $this->getResponseBody($response);
        $this->assertIsArray($body, 'Response should be an array');
        $this->assertCount(0, $body, 'Imported rows should be 0 for empty CSV');

        // Verify that no items were imported
        $trackerId = static::$defaultTrackerId;
        $trklib = TikiLib::lib('trk');
        $items = $trklib->get_all_tracker_items($trackerId);
        $this->assertCount(0, $items, 'Tracker should have 0 items after importing empty CSV');
    }

    public function testApiImportNonExistentTabular()
    {
        $filename = 'testdata.csv';
        $filepath = __DIR__ . '/assets/' . $filename;
        $tabularId = 999999; // Non-existent tabular ID

        $data = ["file" => $filepath, "separator" => ","];
        $response = $this->makeApiRequest('POST', "/tabulars/{$tabularId}/import", 'Admins', $data, 'multipart/form-data');
        $body = $this->getResponseBody($response);

        $this->assertValidErrorResponse($body, 404, "Format $tabularId not found");
    }
}
