<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Trackers;

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

    private function getCreateUpdateTrackerResponseSchema()
    {
        return [
            'accordion_pos' => 'int',
            'title' => 'string',
            'trackerId' => 'int|string',
            'info' => 'array',
            'statusTypes' => [
                '*' => [ // status type key is dynamic (o, p, c)
                    'name' => 'string',
                    'label' => 'string',
                    'perm' => 'string',
                    'image' => 'string',
                    'iconname' => 'string',
                ]
            ],
            'statusList' => 'array',
            'sortFields' => 'array',
            'attachmentAttributes' => 'array',
            'startDate' => 'string|null',
            'startTime' => 'string|null',
            'endDate' => 'string|null',
            'endTime' => 'string|null',
            'groupList' => 'array',
            'groupforAlert' => 'bool',
            'showeachuser' => 'bool',
            'sectionFormats' => 'array',
            'remoteTabulars' => 'array',
            'relationshipBehaviourList' => 'array',
            'displayTimezone' => 'string',
            'fields' => 'array',
        ];
    }

    private function getEmptyTrackerListSchema()
    {
        return [
            'list' => 'array',
            'data' => 'array',
            'count' => 'int',
        ];
    }

    private function getTrackerListSchema()
    {
        return [
            'list' => 'array',
            'data' => [
                [
                    'trackerId' => 'int',
                    'name' => 'string|null',
                    'description' => 'string|null',
                    'descriptionIsParsed' => 'string',
                    'created' => 'int',
                    'lastModif' => 'int',
                    'items' => 'int',
                    'fieldsCount' => 'int',
                    'system_tracker' => 'bool'
                ]
            ],
            'count' => 'int',
        ];
    }

    /**
     * Schema for the item_info / info objects returned in the view response.
     */
    private function getTrackerItemInfoSchema()
    {
        return [
            'itemId'      => 'int',
            'trackerId'   => 'int',
            'created'     => 'int',
            'createdBy'   => 'string|null',
            'status'      => 'string',
            'lastModif'   => 'int',
            'lastModifBy' => 'string|null',
        ];
    }

    /**
     * Schema for a single field entry.
     *
     * Extends getTrackerFieldEntrySchema() with the rendered-value keys that
     * are only present when viewing an item (not in list_fields):
     * - value:       raw and processed scalar value (string for most types)
     * - ins_id:               the HTML input id used for this field ("ins_{fieldId}")
     */
    private function getTrackerItemFieldEntrySchema()
    {
        return array_merge(
            $this->getTrackerFieldEntrySchema(),
            [
                'value'         => 'scalar|null',
                'ins_id'        => 'string',
            ]
        );
    }

    /**
     * Schema for the view (GET /trackers/{id}/items/{itemId}) response.
     *
     * - fields:    each entry is a full field definition plus rendered values
     * - item_info / info: both carry the same item metadata object
     */
    private function getTrackerItemViewSchema()
    {
        return [
            'title'     => 'string|null',
            'format'    => 'string|null',
            'itemId'    => 'int',
            'trackerId' => 'int',
            'fields'    => [$this->getTrackerItemFieldEntrySchema()],
            'canModify' => 'bool',
            'item_info' => $this->getTrackerItemInfoSchema(),
            'info'      => $this->getTrackerItemInfoSchema(),
        ];
    }

    private function getTrackerItemsListSchema()
    {
        return [
            'trackerId' => 'int',
            'offset' => 'int',
            'maxRecords' => 'int',
            'result' => [
                [
                    'itemId' => 'int',
                    'status' => 'string',
                    'fields' => 'array',
                ]
            ],
        ];
    }

    /**
     * Schema for a single tracker field entry (as returned in the 'fields' array of list_fields).
     * options_map keys vary per field type, so it is validated as a plain array.
     */
    private function getTrackerFieldEntrySchema()
    {
        return [
            'fieldId'                 => 'int',
            'trackerId'               => 'int',
            'name'                    => 'string',
            'permName'                => 'string',
            'options'                 => 'string',
            'type'                    => 'string',
            'isMain'                  => 'string',
            'isTblVisible'            => 'string',
            'position'                => 'int',
            'isSearchable'            => 'string',
            'isPublic'                => 'string',
            'isHidden'                => 'string',
            'isMandatory'             => 'string',
            'description'             => 'string',
            'isMultilingual'          => 'string',
            'itemChoices'             => 'array',
            'errorMsg'                => 'string',
            'visibleBy'               => 'array',
            'editableBy'              => 'array',
            'descriptionIsParsed'     => 'string',
            'validation'              => 'string',
            'validationParam'         => 'string',
            'validationMessage'       => 'string',
            'rules'                   => 'string|null',
            'encryptionKeyId'         => 'int|null',
            'excludeFromNotification' => 'string',
            'visibleInViewMode'       => 'string',
            'visibleInEditMode'       => 'string',
            'visibleInHistoryMode'    => 'string',
            'options_array'           => 'array',
            'options_map'             => 'array',  // keys differ per field type
        ];
    }

    /**
     * Schema for a single param entry within a field type definition.
     * Only the three keys present in every param object are checked here;
     * optional keys (default, options, legacy_index, …) are not enforced.
     */
    private function getFieldTypeParamSchema()
    {
        return [
            'name'        => 'string',
            'description' => 'string',
            'filter'      => 'string',
        ];
    }

    /**
     * Schema for a single field type entry - shared by both 'types' and 'typesDisabled'.
     *
     * Only keys guaranteed to be present in every type are listed. Optional keys
     * (readonly, deprecated, help, warning, supported_changes) are intentionally omitted:
     * assertMatchesSchema only fails on *missing* expected keys, so their absence here
     * does not prevent them from being present in the actual response.
     *
     * 'params' uses the '*' wildcard: when the array is empty the loop is a no-op;
     * when it has entries each one is validated against getFieldTypeParamSchema().
     */
    private function getFieldTypeEntrySchema()
    {
        return [
            'name'        => 'string',
            'description' => 'string',
            'prefs'       => 'array',
            'tags'        => 'array',
            'default'     => 'string',
            'params'      => ['*' => $this->getFieldTypeParamSchema()],
        ];
    }

    /**
     * Top-level schema for the list_fields API response.
     *
     * - duplicates:    array of duplicate permName warnings (empty in most responses)
     */
    private function getTrackerFieldsListSchema()
    {
        return [
            'fields'        => [$this->getTrackerFieldEntrySchema()],
            'types'         => ['*' => $this->getFieldTypeEntrySchema()],
            'typesDisabled' => ['*' => $this->getFieldTypeEntrySchema()],
            'duplicates'    => 'array',
        ];
    }

    /**
     * Schema for the export_fields (GET /trackers/{id}/fields/export) response.
     *
     * - fields:    full field definitions (same structure as list_fields entries)
     * - export:    INI-format string representation of all fields
     */
    private function getExportFieldsResponseSchema()
    {
        return [
            'title'     => 'string',
            'trackerId' => 'int',
            'fields'    => [$this->getTrackerFieldEntrySchema()],
            'export'    => 'string',
        ];
    }

    /**
     * Schema for the edit_field (POST /trackers/{id}/fields/{fieldId}) response.
     *
     * - field:                the updated field entry (getTrackerFieldEntrySchema + all_groups)
     * - info:                 field-type definition for the current type (getFieldTypeEntrySchema)
     * - validation_types:     map of validation-type key -> label string
     * - types:                compatible target types for this field (wildcard)
     * - fields:               all fields on the tracker (array of field entries)
     */
    private function getEditFieldResponseSchema()
    {
        $fieldSchema = array_merge(
            $this->getTrackerFieldEntrySchema(),
            ['all_groups' => 'array']
        );

        return [
            'title'                  => 'string',
            'field'                  => $fieldSchema,
            'info'                   => $this->getFieldTypeEntrySchema(),
            'options'                => 'array',
            'validation_types'       => ['*' => 'string'],
            'types'                  => ['*' => $this->getFieldTypeEntrySchema()],
            'permNameMaxAllowedSize' => 'int',
            'fields'                 => [$this->getTrackerFieldEntrySchema()],
            'encryption_keys'        => 'array',
        ];
    }

    /**
     * Schema for the add_field (POST /trackers/{id}/fields) response.
     */
    private function getAddFieldResponseSchema()
    {
        return [
            'title'               => 'string',
            'trackerId'           => 'int',
            'fieldId'             => 'int',
            'name'                => 'string',
            'permName'            => 'string',
            'type'                => 'string',
            'types'               => ['*' => $this->getFieldTypeEntrySchema()],
            'description'         => 'string',
            'descriptionIsParsed' => 'string|null',
            'modal'               => 'string|null',
            'fieldPrefix'         => 'string|bool',
        ];
    }

    private function getDeleteClearDuplicateTrackerResponseSchema()
    {
        return [
            'trackerId' => 'int|string',
            'name' => 'string|null',
            'message' => 'string',
        ];
    }

    private function getDeleteTrackerFieldResponseSchema()
    {
        return [
            'status' => 'string',
            'trackerId' => 'int',
            'fields' => 'array',
        ];
    }

    /**
     * Schema for a single entry in the 'history' array of the item_history response.
     *
     * Only keys guaranteed in every entry are listed. The initial creation entry
     * (version=0) omits 'diff', 'rendered_value', and 'rendered_new'; subsequent
     * entries include them. Because assertMatchesSchema only fails on *missing*
     * expected keys, those optional keys are intentionally excluded here.
     */
    private function getItemHistoryEntrySchema()
    {
        return [
            'version'   => 'int',
            'fieldId'   => 'int|null',
            'value'     => 'string',
            'user'      => 'string|null',
            'lastModif' => 'int',
            'new'       => 'string',
        ];
    }

    /**
     * Schema for the item_history (GET /trackers/{id}/items/{itemId}/history) response.
     *
     * - history:        chronological list of field changes
     * - item_info:      item metadata plus dynamic field values keyed by fieldId string
     *                   (reuses getTrackerItemInfoSchema; extra numbered keys are ignored)
     * - field_option:   map of fieldId string -> full field definition (wildcard)
     */
    private function getTrackerItemHistorySchema()
    {
        return [
            'fieldId'        => 'int|null',
            'filter'         => 'array',
            'diff_style'     => 'string',
            'offset'         => 'int|null',
            'history'        => [$this->getItemHistoryEntrySchema()],
            'count'          => 'int',
            'item_info'      => $this->getTrackerItemInfoSchema(),
            'field_option'   => ['*' => $this->getTrackerFieldEntrySchema()],
            'metatag_robots' => 'string',
            'logging'        => 'int',
        ];
    }

    /**
     * Schema for the update_item (POST /trackers/{id}/items/{itemId}) response.
     *
     * - fields:     flat map of permName -> current value (keys vary per tracker)
     */
    private function getUpdateItemResponseSchema(): array
    {
        return [
            // Feedback::get() reads from $_SESSION which is not populated in the CLI
            // subprocess used for API integration tests, so it returns false.
            'feedback'   => 'array|bool',
            'itemId'     => 'int',
            'status'     => 'string',
            'fields'     => ['*' => 'scalar|null'],
            'nextTicket' => 'string',
        ];
    }

    private function getUpdateItemStatusResponseSchema()
    {
        return [
            'FORWARD' => [
                'controller' => 'string',
                'action' => 'string',
                'status' => 'string',
                'redirect' => 'string',
            ]
        ];
    }

    private function getCreateItemResponseSchema()
    {
        return [
            'itemId' => 'int',
            'status' => 'string',
            'fields' => ['*' => 'scalar|null'],
            'itemTitle' => 'string',
            'processedFields' => ['*' => 'scalar|null'],
            'nextTicket' => 'string',
        ];
    }

    private function getDeleteTrackerItemResponseSchema()
    {
        return [
            'title'         => 'string',
            'trackerId'     => 'int',
            'itemId'        => 'string|int',
            'affectedCount' => 'int',
            'multiple'      => 'bool',
            'removeCount'   => 'int',
        ];
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
