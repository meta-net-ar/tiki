<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Tabulars;

use ApiTestCase;
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
     * Preferences to enable required features
     * @var array
     */
    private static $preferences = [
        'feature_trackers' => 'y',
        'tracker_tabular_enabled' => 'y',
    ];

    /**
     * Setup before class - enable features and create default test content
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $tikilib = TikiLib::lib('tiki');
        foreach (self::$preferences as $pref => $value) {
            $tikilib->set_preference($pref, $value);
        }

        static::createDefaultTestData();
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
                    "isPrimary" => false,
                    "isReadOnly" => false,
                    "isExportOnly" => false,
                    "isUniqueKey" => false
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
            [],
            '',
            '',
            null,
            '',
            [],
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
        $tikilib = TikiLib::lib('tiki');

        foreach (static::$testTrackerItems as $itemId) {
            $trklib->remove_tracker_item($itemId);
        }

        foreach (static::$testTabulars as $tabularId) {
            $tabularlib->remove($tabularId);
        }

        foreach (static::$testTrackers as $trackerId) {
            $trklib->remove_tracker($trackerId);
        }

        foreach (self::$preferences as $pref => $value) {
            $tikilib->delete_preference($pref);
        }
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

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    private function getTabularListSchema()
    {
        return [
            'title' => 'string',
            'formatList' => [
                [
                    'tabularId' => 'int',
                    'name' => 'string',
                    'trackerId' => 'int',
                ]
            ],
        ];
    }

    private function getTabularSchema()
    {
        return [
            'title' => 'string',
            'tabularId' => 'int',
            'trackerId' => 'int',
            'name' => 'string',
            'config' => [
                'simple_headers' => 'int',
                'import_update' => 'int',
                'ignore_blanks' => 'int',
                'import_transaction' => 'int',
                'bulk_import' => 'int',
                'skip_unmodified' => 'int',
                'encoding' => 'string',
                'format' => 'string',
                'mapping' => 'string',
            ],
            'odbc_config' => 'array',
            'api_config' => 'array',
            'columns' => [
                [
                    'label' => 'string',
                    'field' => 'string',
                    'mode' => 'string',
                    'displayAlign' => 'string',
                    'isPrimary' => 'bool',
                    'isReadOnly' => 'bool',
                    'isExportOnly' => 'bool',
                    'isUniqueKey' => 'bool',
                ]
            ],
            'filters' => 'array',
            'schema' => 'array',
            'filterCollection' => 'array',
            'has_odbc' => 'bool',
            'encodings' => 'array',
        ];
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
