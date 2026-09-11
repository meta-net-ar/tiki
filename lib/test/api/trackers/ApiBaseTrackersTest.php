<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Trackers;

use Tiki\Lib\Test\Api\ApiSchemaLoader;
use Tiki\Lib\Test\Api\ApiTestCase;
use TikiLib;
use Tracker_Definition;

/**
 * Base class for Trackers API integration tests
 * Provides common setup, teardown, and helper methods
 *
 * @group api-integration-test
 */
abstract class ApiBaseTrackersTest extends ApiTestCase
{
    /**
     * IDs of trackers created for testing
     * @var array
     */
    protected static $testTrackers = [];

    /**
     * IDs of tracker items created for testing
     * @var array
     */
    protected static $testTrackerItems = [];

    /**
     * Default tracker ID created in setUpBeforeClass
     * @var int|null
     */
    protected static $defaultTrackerId = null;

    /**
     * Field info for the default tracker
     * @var array
     */
    protected static $defaultTrackerFields = [];

    /**
     * Default item IDs pre-created for read tests
     * @var array
     */
    protected static $defaultItemIds = [];

    /**
     * Preferences to enable for testing
     * @var array
     */
    private static $preferences = [
        'feature_trackers' => 'y',
        'unified_engine'   => 'n',
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::setTestPreferences(self::$preferences);
        static::createDefaultTestData();
    }

    /**
     * Clean up all test data after the last test in the class
     */
    public static function tearDownAfterClass(): void
    {
        static::cleanupTestData();
        parent::tearDownAfterClass();
    }

    /**
     * Create the default tracker and items used across tests
     */
    private static function createDefaultTestData(): void
    {
        $trackerId = static::createTracker('API_Test_Tracker', 'Default tracker for API integration tests');
        if (! $trackerId) {
            return;
        }

        static::$defaultTrackerId = $trackerId;

        $item1 = static::createTrackerItem($trackerId, [
            'title'       => 'First Test Item',
            'description' => 'Description of the first test item',
            'count'       => '10',
        ]);
        if ($item1) {
            static::$defaultItemIds[] = $item1;
        }

        $item2 = static::createTrackerItem($trackerId, [
            'title'       => 'Second Test Item',
            'description' => 'Description of the second test item',
            'count'       => '20',
        ]);
        if ($item2) {
            static::$defaultItemIds[] = $item2;
        }
    }

    /**
     * Create a tracker with standard text and numeric fields
     *
     * @param string $name        Tracker name
     * @param string $description Tracker description
     * @return int|null           Tracker ID, or null on failure
     */
    protected static function createTracker($name, $description = '')
    {
        $trklib = TikiLib::lib('trk');

        $trackerId = $trklib->replace_tracker(0, $name, $description, [], 'n');
        if (! $trackerId) {
            return null;
        }

        static::$testTrackers[] = $trackerId;

        $titleId = static::addTrackerField($trackerId, 't', 'Title', "{$name}_title");
        $descId  = static::addTrackerField($trackerId, 't', 'Description', "{$name}_description");
        $countId = static::addTrackerField($trackerId, 'n', 'Count', "{$name}_count");

        static::$defaultTrackerFields = [
            'title' => ['fieldId' => $titleId, 'permName' => "{$name}_title"],
            'description' => ['fieldId' => $descId,  'permName' => "{$name}_description"],
            'count' => ['fieldId' => $countId, 'permName' => "{$name}_count"],
        ];

        return $trackerId;
    }

    /**
     * Add a field to a tracker
     *
     * @param int    $trackerId
     * @param string $type       Field type letter (t=text, n=numeric, etc.)
     * @param string $name       Field display name
     * @param string $permName   Permanent name (unique identifier)
     * @return int|null          Field ID or null on failure
     */
    protected static function addTrackerField($trackerId, $type, $name, $permName)
    {
        $trklib = TikiLib::lib('trk');

        $fieldId = $trklib->replace_tracker_field(
            $trackerId,
            0,       // fieldId 0 = create new
            $name,
            $type,
            'y',     // isTblVisible
            'y',     // isSearchable
            'y',     // isPublic
            'n',     // isMandatory
            'n',     // isHidden
            'y',     // isMain
            10,      // position
            '',      // options
            '',      // description
            '',      // descriptionIsParsed
            null,    // itemChoices
            '',      // errorMsg
            null,      // visibleBy
            null,    // editableBy
            'n',     // isMultilingual
            '',      // validation
            '',      // validationParam
            '',      // validationMessage
            $permName
        );

        return $fieldId ?: null;
    }

    /**
     * Create a tracker item with specified field values
     *
     * Field values are keyed by permName.
     *
     * @param int   $trackerId
     * @param array $fieldValues  e.g. ['title' => 'My Item', 'description' => 'Text']
     * @return int|null           Item ID or null on failure
     */
    protected static function createTrackerItem($trackerId, $fieldValues = [])
    {
        $trklib = TikiLib::lib('trk');
        $definition = Tracker_Definition::get($trackerId);
        if (! $definition) {
            return null;
        }

        $data = [];
        foreach ($definition->getFields() as $field) {
            $value = $fieldValues[$field['permName']] ?? '';

            $data[] = array_merge($field, ['value' => $value]);
        }

        $itemId = $trklib->replace_item($trackerId, 0, ['data' => $data], 'o');

        if ($itemId && $itemId > 0) {
            static::$testTrackerItems[] = $itemId;
        }

        return ($itemId && $itemId > 0) ? $itemId : null;
    }

    /**
     * Clean up all test data created during the test run
     */
    protected static function cleanupTestData(): void
    {
        $trklib = TikiLib::lib('trk');

        foreach (static::$testTrackerItems as $itemId) {
            try {
                $trklib->remove_tracker_item($itemId);
            } catch (\Exception) {
                // Ignore cleanup errors
            }
        }
        static::$testTrackerItems = [];

        foreach (static::$testTrackers as $trackerId) {
            try {
                $trklib->remove_tracker($trackerId);
            } catch (\Exception) {
                // Ignore cleanup errors
            }
        }
        static::$testTrackers = [];

        static::$defaultTrackerId = null;
        static::$defaultTrackerFields = [];
        static::$defaultItemIds = [];

        static::deleteTestPreferences(self::$preferences);
    }

    /**
     * Get the field ID for the default tracker by lowercase field name key
     * e.g. getDefaultFieldId('title')
     *
     * @param string $key lowercase field name
     * @return int|null
     */
    protected function getDefaultFieldId($key)
    {
        return static::$defaultTrackerFields[$key]['fieldId'] ?? null;
    }

    /**
     * Get the permName for the default tracker by lowercase field name key
     *
     * @param string $key
     * @return string|null
     */
    protected function getDefaultFieldPermName($key)
    {
        return static::$defaultTrackerFields[$key]['permName'] ?? null;
    }

    /**
     * Get a default pre-created item ID by zero-based index
     *
     * @param int $index
     * @return int|null
     */
    protected function getDefaultItemId($index = 0)
    {
        return static::$defaultItemIds[$index] ?? null;
    }

    private function getCreateUpdateTrackerResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TrackerCreateUpdateResponse.yaml');
    }

    private function getEmptyTrackerListSchema(): array
    {
        // Same shape as TrackerListResponse.yaml, but 'data' is expected to be empty here,
        // so it is validated as a plain array instead of a non-empty array of entries.
        return array_merge(
            ApiSchemaLoader::fromSchemaFile('TrackerListResponse.yaml'),
            ['data' => 'array']
        );
    }

    private function getTrackerListSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TrackerListResponse.yaml');
    }

    /**
     * Schema for the item_info / info objects returned in the view response.
     */
    private function getTrackerItemInfoSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TrackerItem.yaml');
    }

    /**
     * Schema for the view (GET /trackers/{id}/items/{itemId}) response.
     *
     * - fields:    each entry is a full field definition plus rendered values
     * - item_info / info: both carry the same item metadata object
     */
    private function getTrackerItemViewSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TrackerItemViewResponse.yaml');
    }

    private function getTrackerItemsListSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TrackerItemsListResponse.yaml');
    }

    /**
     * Schema for a single tracker field entry (as returned in the 'fields' array of list_fields).
     * options_map keys vary per field type, so it is validated as a plain array.
     */
    private function getTrackerFieldEntrySchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TrackerField.yaml');
    }

    /**
     * Top-level schema for the list_fields API response.
     *
     * - duplicates:    array of duplicate permName warnings (empty in most responses)
     */
    private function getTrackerFieldsListSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TrackerFieldsListResponse.yaml');
    }

    /**
     * Schema for the export_fields (GET /trackers/{id}/fields/export) response.
     *
     * - fields:    full field definitions (same structure as list_fields entries)
     * - export:    INI-format string representation of all fields
     */
    private function getExportFieldsResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TrackerFieldsExportResponse.yaml');
    }

    /**
     * Schema for the edit_field (POST /trackers/{id}/fields/{fieldId}) response.
     *
     * - field:                the updated field entry (TrackerField + all_groups)
     * - info:                 field-type definition for the current type
     * - validation_types:     map of validation-type key -> label string
     * - types:                compatible target types for this field (wildcard)
     * - fields:               all fields on the tracker (array of field entries)
     */
    private function getEditFieldResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TrackerEditFieldResponse.yaml');
    }

    /**
     * Schema for the add_field (POST /trackers/{id}/fields) response.
     */
    private function getAddFieldResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TrackerAddFieldResponse.yaml');
    }

    private function getDeleteClearDuplicateTrackerResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TrackerDeleteClearDuplicateResponse.yaml');
    }

    private function getDeleteTrackerFieldResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TrackerDeleteFieldResponse.yaml');
    }

    /**
     * Schema for the item_history (GET /trackers/{id}/items/{itemId}/history) response.
     *
     * - history:        chronological list of field changes
     * - item_info:      item metadata plus dynamic field values keyed by fieldId string
     * - field_option:   map of fieldId string -> full field definition (wildcard)
     */
    private function getTrackerItemHistorySchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TrackerItemHistoryResponse.yaml');
    }

    /**
     * Schema for the update_item (POST /trackers/{id}/items/{itemId}) response.
     *
     * - fields:     flat map of permName -> current value (keys vary per tracker)
     */
    private function getUpdateItemResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TrackerUpdateItemResponse.yaml');
    }

    private function getUpdateItemStatusResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TrackerUpdateItemStatusResponse.yaml');
    }

    private function getCreateItemResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TrackerCreateItemResponse.yaml');
    }

    private function getDeleteTrackerItemResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('TrackerDeleteItemResponse.yaml');
    }

    protected function assertValidCreateUpdateTrackerResponse($body)
    {
        $this->assertMatchesSchema($body, $this->getCreateUpdateTrackerResponseSchema());
    }

    protected function assertValidTrackerListResponse($body)
    {
        $this->assertMatchesSchema($body, $this->getTrackerListSchema());
    }

    protected function assertValidTrackerItemViewResponse($body)
    {
        $this->assertMatchesSchema($body, $this->getTrackerItemViewSchema());
    }

    protected function assertValidTrackerItemsListResponse($body)
    {
        $this->assertMatchesSchema($body, $this->getTrackerItemsListSchema());
    }

    protected function assertValidTrackerFieldsListResponse($body)
    {
        $this->assertNotEmpty($body['fields'], 'Tracker should have at least one field');
        $this->assertNotEmpty($body['types'], 'Field types should always be present in the response');
        $this->assertMatchesSchema($body, $this->getTrackerFieldsListSchema());
    }

    protected function assertValidDeleteClearDuplicateTrackerResponse($body)
    {
        $this->assertMatchesSchema($body, $this->getDeleteClearDuplicateTrackerResponseSchema());
    }

    protected function assertValidExportFieldsResponse($body)
    {
        $this->assertNotEmpty($body['export'], 'export string should not be empty');
        $this->assertNotEmpty($body['fields'], 'Exported fields should not be empty');
        $this->assertMatchesSchema($body, $this->getExportFieldsResponseSchema());
    }

    protected function assertValidEditFieldResponse($body)
    {
        $this->assertMatchesSchema($body, $this->getEditFieldResponseSchema());
        $this->assertGreaterThan(0, $body['field']['fieldId'], 'field.fieldId should be a positive integer');
    }

    protected function assertValidAddFieldResponse($body)
    {
        $this->assertMatchesSchema($body, $this->getAddFieldResponseSchema());
        $this->assertGreaterThan(0, $body['fieldId'], 'fieldId should be a positive integer');
    }

    protected function assertValidTrackerItemHistoryResponse($body)
    {
        $this->assertMatchesSchema($body, $this->getTrackerItemHistorySchema());
        $this->assertIsArray($body['history']);
    }

    protected function assertValidUpdateItemResponse($body)
    {
        $this->assertMatchesSchema($body, $this->getUpdateItemResponseSchema());
        $this->assertGreaterThan(0, $body['itemId'], 'itemId should be a positive integer');
        $this->assertNotEmpty($body['nextTicket'], 'nextTicket should not be empty');
    }

    protected function assertValidUpdateItemStatusResponse($body)
    {
        $this->assertMatchesSchema($body, $this->getUpdateItemStatusResponseSchema());
    }

    protected function assertValidCreateItemResponse($body)
    {
        $this->assertMatchesSchema($body, $this->getCreateItemResponseSchema());
        $this->assertGreaterThan(0, $body['itemId'], 'itemId should be a positive integer');
        $this->assertNotEmpty($body['nextTicket'], 'nextTicket should not be empty');
    }

    protected function assertValidDeleteTrackerItemResponse($body)
    {
        $this->assertMatchesSchema($body, $this->getDeleteTrackerItemResponseSchema());
        $this->assertEquals('Remove', $body['title'], 'Response title should be "remove" after deleting tracker item');
    }

    protected function assertValidDeleteTrackerFieldResponse($body)
    {
        $this->assertMatchesSchema($body, $this->getDeleteTrackerFieldResponseSchema());
        $this->assertEquals('DONE', $body['status'], 'Status should be DONE after deleting tracker field');
    }

    protected function assertValidEmptyTrackerListResponse($body)
    {
        $this->assertMatchesSchema($body, $this->getEmptyTrackerListSchema());
        $this->assertEmpty($body['list'], 'Tracker list should be empty');
        $this->assertEmpty($body['data'], 'Tracker data should be empty');
        $this->assertGreaterThanOrEqual(0, $body['count'], 'Tracker count should be a non-negative integer');
    }
}
