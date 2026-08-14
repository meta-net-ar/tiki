<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Tabulars;

use TikiLib;

/**
 * Tests for Tabulars API delete endpoint
 *
 * @group api-integration-test
 * @group api-tabulars
 */
class ApiTabularsDeleteTest extends ApiBaseTabularsTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // Keep each test isolated by clearing tracker items.
        $trklib = TikiLib::lib('trk');
        $items = $trklib->get_all_tracker_items(static::$defaultTrackerId);
        foreach ($items as $item) {
            if (! empty($item['itemId'])) {
                $trklib->remove_tracker_item($item['itemId']);
            }
        }
    }

    public function testApiDeleteCsvWithoutPermission()
    {
        $tabularId = static::$defaultTabularId;
        $dataFile = __DIR__ . '/assets/testdata.csv';

        $importResponse = $this->makeApiRequest('POST', "/tabulars/{$tabularId}/import", 'Admins', ['file' => $dataFile, 'separator' => ','], 'multipart/form-data');
        $this->assertResponseStatus(200, $importResponse);

        $trklib = TikiLib::lib('trk');
        $beforeCount = count($trklib->get_all_tracker_items(static::$defaultTrackerId));

        $csv = "\"Title\",\"Description\",\"Count\"\n\"Ghost\",\"\",0\n";
        $tmpFile = $this->createTempCsvFile($csv);

        $response = $this->makeApiRequest('POST', "/tabulars/{$tabularId}/delete", null, ['file' => $tmpFile], 'multipart/form-data');
        @unlink($tmpFile);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 403, 'Permission denied');

        $afterCount = count($trklib->get_all_tracker_items(static::$defaultTrackerId));
        $this->assertEquals($beforeCount, $afterCount, 'Tracker items should remain unchanged after a denied delete request');
    }

    public function testApiDeleteCsvNonExistentTabular()
    {
        $tabularId = 999999;
        $csv = "\"Title\",\"Description\",\"Count\"\n\"Ghost\",\"\",0\n";
        $tmpFile = $this->createTempCsvFile($csv);

        $response = $this->makeApiRequest('POST', "/tabulars/{$tabularId}/delete", 'Admins', ['file' => $tmpFile], 'multipart/form-data');
        @unlink($tmpFile);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 404, "Format $tabularId not found");
    }

    public function testApiDeleteCsvWithoutFile()
    {
        $tabularId = static::$defaultTabularId;

        $response = $this->makeApiRequest('POST', "/tabulars/{$tabularId}/delete", 'Admins', []);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 409, '<!--field[file]-->Field Required');
    }

    public function testApiDeleteCsvDeletesSingleItem()
    {
        $tabularId = static::$defaultTabularId;
        $dataFile = __DIR__ . '/assets/testdata.csv';

        $importResponse = $this->makeApiRequest('POST', "/tabulars/{$tabularId}/import", 'Admins', ['file' => $dataFile, 'separator' => ','], 'multipart/form-data');
        $this->assertResponseStatus(200, $importResponse);

        $trklib = TikiLib::lib('trk');
        $beforeCount = count($trklib->get_all_tracker_items(static::$defaultTrackerId));

        $csv = "\"Title\",\"Description\",\"Count\"\n\"Title One\",\"\",0\n";
        $tmpFile = $this->createTempCsvFile($csv);

        $response = $this->makeApiRequest('POST', "/tabulars/{$tabularId}/delete", 'Admins', ['file' => $tmpFile], 'multipart/form-data');
        @unlink($tmpFile);

        $this->assertResponseStatus(200, $response);
        $body = $this->getResponseBody($response);
        $this->assertArrayHasKey('feedback', $body);
        $this->assertStringContainsString('Your delete request was completed successfully.', $body['feedback']);

        $afterCount = count($trklib->get_all_tracker_items(static::$defaultTrackerId));

        $this->assertNotEmpty($afterCount, 'Tracker should still contain items after deleting a single row');
        $this->assertEquals($beforeCount - 1, $afterCount, 'Exactly one item should be deleted');
    }

    public function testApiDeleteCsvDeletesMultipleItems()
    {
        $tabularId = static::$defaultTabularId;
        $dataFile = __DIR__ . '/assets/testdata.csv';

        $importResponse = $this->makeApiRequest('POST', "/tabulars/{$tabularId}/import", 'Admins', ['file' => $dataFile, 'separator' => ','], 'multipart/form-data');
        $this->assertResponseStatus(200, $importResponse);

        $trklib = TikiLib::lib('trk');
        $beforeCount = count($trklib->get_all_tracker_items(static::$defaultTrackerId));

        $csv = "\"Title\",\"Description\",\"Count\"\n\"Title One\",\"\",0\n\"Title Two\",\"\",0\n";
        $tmpFile = $this->createTempCsvFile($csv);

        $response = $this->makeApiRequest('POST', "/tabulars/{$tabularId}/delete", 'Admins', ['file' => $tmpFile], 'multipart/form-data');
        @unlink($tmpFile);

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertArrayHasKey('feedback', $body);
        $this->assertStringContainsString('Your delete request was completed successfully.', $body['feedback']);

        $afterCount = count($trklib->get_all_tracker_items(static::$defaultTrackerId));
        $this->assertNotEmpty($afterCount, 'Tracker should still contain items after deleting a multiple rows');
        $this->assertEquals($beforeCount - 2, $afterCount, 'Exactly two items should be deleted');
    }

    public function testApiDeleteCsvNonExistentPrimaryKeys()
    {
        $tabularId = static::$defaultTabularId;
        $dataFile = __DIR__ . '/assets/testdata.csv';

        $importResponse = $this->makeApiRequest('POST', "/tabulars/{$tabularId}/import", 'Admins', ['file' => $dataFile, 'separator' => ','], 'multipart/form-data');
        $this->assertResponseStatus(200, $importResponse);

        $trklib = TikiLib::lib('trk');
        $beforeCount = count($trklib->get_all_tracker_items(static::$defaultTrackerId));

        $csv = "\"Title\",\"Description\",\"Count\"\n\"Does Not Exist\",\"\",0\n";
        $tmpFile = $this->createTempCsvFile($csv);

        $response = $this->makeApiRequest('POST', "/tabulars/{$tabularId}/delete", 'Admins', ['file' => $tmpFile], 'multipart/form-data');
        @unlink($tmpFile);

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertArrayHasKey('feedback', $body);
        $this->assertStringContainsString('Your delete request was completed successfully.', $body['feedback']);

        $afterCount = count($trklib->get_all_tracker_items(static::$defaultTrackerId));
        $this->assertEquals($beforeCount, $afterCount, 'No items should be deleted');
    }

    public function testApiDeleteCsvWithEmptyCsv()
    {
        $tabularId = static::$defaultTabularId;
        $dataFile = __DIR__ . '/assets/testdata.csv';

        $importResponse = $this->makeApiRequest('POST', "/tabulars/{$tabularId}/import", 'Admins', ['file' => $dataFile, 'separator' => ','], 'multipart/form-data');
        $this->assertResponseStatus(200, $importResponse);

        $trklib = TikiLib::lib('trk');
        $beforeCount = count($trklib->get_all_tracker_items(static::$defaultTrackerId));

        $csv = "\"Title\",\"Description\",\"Count\"\n";
        $tmpFile = $this->createTempCsvFile($csv);

        $response = $this->makeApiRequest('POST', "/tabulars/{$tabularId}/delete", 'Admins', ['file' => $tmpFile], 'multipart/form-data');
        @unlink($tmpFile);

        $this->assertResponseStatus(200, $response);

        $body = $this->getResponseBody($response);
        $this->assertArrayHasKey('feedback', $body);
        $this->assertStringContainsString('Your delete request was completed successfully.', $body['feedback']);

        $afterCount = count($trklib->get_all_tracker_items(static::$defaultTrackerId));
        $this->assertEquals($beforeCount, $afterCount, 'No items should be deleted for empty CSV');
    }

    public function testApiDeleteCsvWithInvalidCsvFormat(): void
    {
        $tabularId = static::$defaultTabularId;

        $csv = "\"Description\"\n\"Some value\"\n";
        $tmpFile = $this->createTempCsvFile($csv);

        $response = $this->makeApiRequest('POST', "/tabulars/{$tabularId}/delete", 'Admins', ['file' => $tmpFile], 'multipart/form-data');
        @unlink($tmpFile);

        $body = $this->getResponseBody($response);
        $this->assertValidErrorResponse($body, 0, 'Expected header "Title" not found');
    }
}
