<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Tabulars;

use Tiki\Lib\Test\Api\ApiSchemaLoader;
use Tiki\Lib\Test\Api\ApiTestCase;
use TikiLib;

/**
 * Base class for Tabulars API integration tests
 * Provides common setup, teardown, and helper methods
 *
 * @group api-integration-test
 */
abstract class ApiBaseTabularsTest extends ApiTestCase
{
    /**
     * Array to track test trackers for cleanup
     * @var array
     */
    protected static $testTrackers = [];

    /**
     * Array to track test tracker items for cleanup
     * @var array
     */
    protected static $testTrackerItems = [];

    /**
     * Array to track test tabulars for cleanup
     * @var array
     */
    protected static $testTabulars = [];

    /**
     * Array to track test tracker fields
     * @var array
     */
    protected static $testTrackerFields = [];

    /**
     * Default test tracker created for testing
     * @var int|null
     */
    protected static $defaultTrackerId = null;

    /**
     * Default test tabular created for testing
     * @var int|null
     */
    protected static $defaultTabularId = null;

    /**
     * Guard for lazy one-time test-data creation.
     */
    private static bool $testDataCreated = false;

    /**
     * Preferences to enable required features
     * @var array
     */
    private static $preferences = [
        'feature_trackers' => 'y',
        'tracker_tabular_enabled' => 'y',
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::setTestPreferences(self::$preferences);

        // Sync to the in-process $prefs global so ORM calls in lazy setUp() do not
        // attempt to reach search services that are absent in the CI environment.
        global $prefs;
        foreach (self::$preferences as $name => $value) {
            $prefs[$name] = $value;
        }
        self::$testDataCreated = false;
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (! self::$testDataCreated) {
            static::createDefaultTestData();
            self::$testDataCreated = true;
        }
    }

    /**
     * Teardown after class - cleanup all test data
     */
    public static function tearDownAfterClass(): void
    {
        static::cleanupTestData();
        parent::tearDownAfterClass();
    }

    /**
     * Create default test data for all tests
     */
    private static function createDefaultTestData()
    {
        static::$defaultTabularId = static::createTrackerTabular('API_Tracker_Tabular');

        $info = TikiLib::lib('tabular')->getInfo(static::$defaultTabularId);
        static::$defaultTrackerId = $info['trackerId'];
    }

    /**
     * Create a tracker tabular for testing
     * @param string $name Tracker name
     * @return int|null The tabular ID or null on failure
     */
    protected static function createTrackerTabular($trackerName)
    {
        $trklib = TikiLib::lib('trk');

        $trackerId = $trklib->replace_tracker(0, $trackerName, 'Test tracker for Tabulars', [], 'n');
        $tabularId = null;

        if ($trackerId) {
            static::$testTrackers[] = $trackerId;

            $field1 = static::addTrackerField($trackerId, 't', 'Title', "{$trackerName}_title_field");
            if ($field1) {
                static::$testTrackerFields[$trackerId][] = $field1;
            }

            $field2 = static::addTrackerField($trackerId, 't', 'Description', "{$trackerName}_desc_field");
            if ($field2) {
                static::$testTrackerFields[$trackerId][] = $field2;
            }

            $field3 = static::addTrackerField($trackerId, 'n', 'Count', "{$trackerName}_count_field");
            if ($field3) {
                static::$testTrackerFields[$trackerId][] = $field3;
            }

            $format = [
                [
                    'field' => "{$trackerName}_title_field",
                    'label' => 'Title',
                    'mode' => 'link',
                    "isPrimary" => true,
                    "isReadOnly" => false,
                    "isExportOnly" => false,
                    "isUniqueKey" => true
                ],
                [
                    'field' => "{$trackerName}_desc_field",
                    'label' => 'Description',
                    'mode' => 'link',
                    "isPrimary" => false,
                    "isReadOnly" => false,
                    "isExportOnly" => false,
                    "isUniqueKey" => false
                ],
                [
                    'field' => "{$trackerName}_count_field",
                    'label' => 'Count',
                    'mode' => 'formatted',
                    "isPrimary" => false,
                    "isReadOnly" => false,
                    "isExportOnly" => false,
                    "isUniqueKey" => false
                ],
            ];
            $tabularId = static::createTabular("{$trackerName}_Format", $trackerId, $format);
        }

        return $tabularId;
    }

    /**
     * Add a field to a tracker
     * @param int $trackerId
     * @param string $type Field type
     * @param string $name Field name
     * @param string $permName Permanent name
     * @return int|null Field ID or null on failure
     */
    private static function addTrackerField($trackerId, $type, $name, $permName)
    {
        $trklib = TikiLib::lib('trk');

        $fieldId = $trklib->replace_tracker_field(
            $trackerId,
            0,
            $name,
            $type,
            'y',
            'y',
            'y',
            'y',
            'n',
            'y',
            10,
            '',
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
            $permName
        );

        return $fieldId ?: null;
    }

    /**
     * Create a tabular format for testing
     * @param string $name Tabular name
     * @param int $trackerId Tracker ID
     * @param array $formatDescriptor Format descriptor array
     * @param array $filterDescriptor Filter descriptor array
     * @return int|null The tabular ID or null on failure
     */
    private static function createTabular($name, $trackerId, $formatDescriptor = [], $filterDescriptor = [])
    {
        $tabularlib = TikiLib::lib('tabular');

        $tabularId = $tabularlib->create($name, $trackerId);

        if ($tabularId && ! empty($formatDescriptor)) {
            $tabularlib->update($tabularId, $name, $formatDescriptor, $filterDescriptor, ["simple_headers" => 1], [], []);
        }

        if ($tabularId) {
            static::$testTabulars[] = $tabularId;
        }

        return $tabularId ?: null;
    }

    /**
     * Cleanup all test data
     */
    private static function cleanupTestData()
    {
        $trklib = TikiLib::lib('trk');
        $tabularlib = TikiLib::lib('tabular');

        foreach (static::$testTrackerItems as $itemId) {
            $trklib->remove_tracker_item($itemId);
        }
        static::$testTrackerItems = [];

        foreach (static::$testTabulars as $tabularId) {
            $tabularlib->remove($tabularId);
        }
        static::$testTabulars = [];

        foreach (static::$testTrackers as $trackerId) {
            $trklib->remove_tracker($trackerId);
        }
        static::$testTrackers = [];

        static::deleteTestPreferences(self::$preferences);
    }

    /**
     * Get tabular information
     * @param int $tabularId
     * @return array|null
     */
    protected function getTabularInfo($tabularId)
    {
        $tabularlib = TikiLib::lib('tabular');
        return $tabularlib->getInfo($tabularId);
    }

    /**
     * Get tracker fields
     * @param int $trackerId
     * @return array
     */
    protected function getTrackerFields($trackerId)
    {
        $trklib = TikiLib::lib('trk');
        return $trklib->list_tracker_fields($trackerId);
    }

    /**
     * Create a temporary CSV file for upload
     * @param string $content CSV content
     * @return string Temporary file path
     */
    protected function createTempCsvFile($content)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'tabular_test_');
        file_put_contents($tmpFile, $content);
        return $tmpFile;
    }

    /**
     * Parse CSV content
     * @param string $content CSV content
     * @return array Parsed rows
     */
    protected function parseCsvContent($content)
    {
        $rows = [];
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        while (($row = fgetcsv($handle, null, ',', '"', '\\')) !== false) {
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    private function getTabularListSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TabularListResponse.yaml');
    }

    private function getTabularSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TabularResponse.yaml');
    }

    protected function assertValidTabularResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getTabularSchema());
    }

    protected function assertValidTabularListResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getTabularListSchema());
    }

    protected function assertValidImportResponse($response)
    {
        $this->assertIsArray($response, 'Response should be an array');
        foreach ($response as $row) {
            $this->assertIsArray($row, 'Each imported row should be an array');
            $this->assertArrayHasKey('itemId', $row, 'Each imported row should have itemId');
        }
    }
}
