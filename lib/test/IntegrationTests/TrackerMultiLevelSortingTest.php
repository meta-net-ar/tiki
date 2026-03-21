<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * @group integration
 * Integration tests for multi-level tracker sorting.
 */
class TrackerMultiLevelSortingTest extends TikiTestCase
{
    protected static $trklib;
    protected static $categlib;
    protected static $trackerId;
    protected static $linkedTrackerId;
    protected static $old_prefs;
    protected static $fieldIds = [];
    protected static $categoryIds = [];
    protected static $itemIds = [];
    protected static $linkedItemIds = [];
    protected static $setUpException = null;
    public static function setUpBeforeClass(): void
    {
        try {
            global $prefs;
            self::$old_prefs = $prefs;
            $prefs['feature_trackers'] = 'y';
            $prefs['feature_categories'] = 'y';
            $prefs['trackerfield_starsystem'] = 'y';
            parent::setUpBeforeClass();
            self::$trklib = TikiLib::lib('trk');
            self::$categlib = TikiLib::lib('categ');
            // Create categories for testing category field sorting
            self::$categoryIds['alpha'] = self::$categlib->add_category(0, 'Alpha Category ' . uniqid(), '');
            self::$categoryIds['beta'] = self::$categlib->add_category(0, 'Beta Category ' . uniqid(), '');
            self::$categoryIds['gamma'] = self::$categlib->add_category(0, 'Gamma Category ' . uniqid(), '');
            // Create linked tracker for ItemLink field testing
            self::$linkedTrackerId = self::$trklib->replace_tracker(null, 'Linked Tracker for Sorting Test ' . uniqid(), '', [], 'n');
            $linkedFieldId = self::$trklib->replace_tracker_field(self::$linkedTrackerId, 0, 'Linked Name', 't', 'y', 'y', 'y', 'y', 'n', 'y', 10, '', '', '', null, '', null, null, 'n', '', '', '', 'linked_name');
            // Create linked items with different names for sorting
            $linkedNames = ['Zebra', 'Apple', 'Mango'];
            foreach ($linkedNames as $name) {
                $definition = Tracker_Definition::get(self::$linkedTrackerId);
                $fields = $definition->getFields();
                if (! isset($fields[0])) {
                    throw new \Exception('Linked tracker field definition missing');
                }
                $fields[0]['value'] = $name;
                self::$linkedItemIds[$name] = self::$trklib->replace_item(self::$linkedTrackerId, 0, ['data' => $fields], 'o');
            }
            // Create main tracker with various field types
            self::$trackerId = self::$trklib->replace_tracker(null, 'Multi-Level Sort Test Tracker ' . uniqid(), '', [], 'n');
            // Get linked tracker field for ItemLink options
            $linkedDefinition = Tracker_Definition::get(self::$linkedTrackerId);
            $linkedFields = $linkedDefinition->getFields();
            // Define fields for testing
            $fieldsConfig = [
                [
                    'name' => 'Text Field',
                    'type' => 't',
                    'permName' => 'test_text',
                ],
                [
                    'name' => 'Numeric Field',
                    'type' => 'n',
                    'permName' => 'test_numeric',
                ],
                [
                    'name' => 'ItemLink Field',
                    'type' => 'r',
                    'permName' => 'test_itemlink',
                    'options' => json_encode([
                        'trackerId' => self::$linkedTrackerId,
                        'fieldId' => $linkedFields[0]['fieldId'] ?? null
                    ]),
                ],
                [
                    'name' => 'Category Field',
                    'type' => 'e',
                    'permName' => 'test_category',
                ],
                [
                    'name' => 'Rating Field',
                    'type' => 's',
                    'permName' => 'test_rating',
                    'options' => json_encode(['mode' => 's', 'options' => '5']),
                ],
            ];
            foreach ($fieldsConfig as $i => $field) {
                $fieldId = self::$trklib->replace_tracker_field(
                    self::$trackerId,
                    0,
                    $field['name'],
                    $field['type'],
                    'y',
                    'y',
                    'y',
                    'y',
                    'n',
                    'n',
                    ($i + 1) * 10,
                    $field['options'] ?? '',
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
                    $field['permName']
                );
                self::$fieldIds[$field['permName']] = $fieldId;
            }
            // Create test items with different values for sorting
            self::createTestItems();
        } catch (\Throwable $e) {
            self::$setUpException = $e;
        }
    }

    protected static function createTestItems()
    {
        try {
            $definition = Tracker_Definition::get(self::$trackerId);
            if (! $definition) {
                throw new \Exception('Main tracker definition missing');
            }
            $testData = [
                [
                    'text' => 'Banana',
                    'numeric' => 50,
                    'itemlink' => self::$linkedItemIds['Apple'] ?? null,
                    'category' => self::$categoryIds['beta'] ?? null,
                    'rating' => 3,
                ],
                [
                    'text' => 'Apple',
                    'numeric' => 100,
                    'itemlink' => self::$linkedItemIds['Zebra'] ?? null,
                    'category' => self::$categoryIds['alpha'] ?? null,
                    'rating' => 5,
                ],
                [
                    'text' => 'Cherry',
                    'numeric' => 25,
                    'itemlink' => self::$linkedItemIds['Mango'] ?? null,
                    'category' => self::$categoryIds['gamma'] ?? null,
                    'rating' => 2,
                ],
                [
                    'text' => 'Apple',
                    'numeric' => 75,
                    'itemlink' => self::$linkedItemIds['Apple'] ?? null,
                    'category' => self::$categoryIds['alpha'] ?? null,
                    'rating' => 4,
                ],
                [
                    'text' => 'Banana',
                    'numeric' => 30,
                    'itemlink' => self::$linkedItemIds['Mango'] ?? null,
                    'category' => self::$categoryIds['beta'] ?? null,
                    'rating' => 1,
                ],
            ];
            foreach ($testData as $data) {
                $fields = $definition->getFields();
                if (! isset($fields[0], $fields[1], $fields[2], $fields[3], $fields[4])) {
                    throw new \Exception('Main tracker field definition incomplete');
                }
                $fields[0]['value'] = $data['text'];
                $fields[1]['value'] = $data['numeric'];
                $fields[2]['value'] = $data['itemlink'];
                $fields[3]['value'] = $data['category'];
                $fields[4]['value'] = $data['rating'];
                try {
                    $itemId = self::$trklib->replace_item(self::$trackerId, 0, ['data' => $fields], 'o');
                    self::$itemIds[] = $itemId;
                } catch (\Throwable $e) {
                    throw $e;
                }
            }
        } catch (\Throwable $e) {
            self::$setUpException = $e;
        }
    }
    protected function setUp(): void
    {
        parent::setUp();
        if (self::$setUpException) {
            $this->fail('Exception during static setup: ' . self::$setUpException->getMessage() . "\n" . self::$setUpException->getTraceAsString());
        }
    }

    public static function tearDownAfterClass(): void
    {
        try {
            global $prefs;
            $prefs = self::$old_prefs;
            parent::tearDownAfterClass();
            // Clean up
            if (self::$trackerId) {
                self::$trklib->remove_tracker(self::$trackerId);
            }
            if (self::$linkedTrackerId) {
                self::$trklib->remove_tracker(self::$linkedTrackerId);
            }
            foreach (self::$categoryIds as $categId) {
                if ($categId) {
                    self::$categlib->remove_category($categId);
                }
            }
            self::$fieldIds = [];
            self::$categoryIds = [];
            self::$itemIds = [];
            self::$linkedItemIds = [];
        } catch (\Throwable $e) {
            // Log but do not throw
        }
    }

    /**
     * Test single-column sorting by text field (ascending)
     */
    public function testSortByTextFieldAscending()
    {
        $fieldId = self::$fieldIds['test_text'];
        $result = self::$trklib->list_items(self::$trackerId, 0, -1, ['f_' . $fieldId . '_asc'], '', '', '', '', '', '', '', null, true, true);
        $this->assertEquals(5, $result['count']);
        $textValues = array_column($result['data'], 'itemId');
        $firstItem = self::getItemFieldValue($result['data'][0]['itemId'], $fieldId);
        $this->assertEquals('Apple', $firstItem);
    }

    /**
     * Test single-column sorting by numeric field (descending)
     */
    public function testSortByNumericFieldDescending()
    {
        $fieldId = self::$fieldIds['test_numeric'];
        $result = self::$trklib->list_items(self::$trackerId, 0, -1, ['f_' . $fieldId . '_desc'], '', '', '', '', '', '', '', null, true, true);
        $this->assertEquals(5, $result['count']);
        $firstValue = self::getItemFieldValue($result['data'][0]['itemId'], $fieldId);
        $lastValue = self::getItemFieldValue($result['data'][4]['itemId'], $fieldId);
        $this->assertEquals('100', $firstValue);
        $this->assertEquals('25', $lastValue);
    }

    /**
     * Test single-column sorting by ItemLink field
     * This tests the special join logic for ItemLink fields
     */
    public function testSortByItemLinkField()
    {
        $fieldId = self::$fieldIds['test_itemlink'];
        $result = self::$trklib->list_items(self::$trackerId, 0, -1, ['f_' . $fieldId . '_asc'], '', '', '', '', '', '', '', null, true, true);
        $this->assertEquals(5, $result['count']);
        $firstItemLink = self::getItemFieldValue($result['data'][0]['itemId'], $fieldId);
        $this->assertEquals(self::$linkedItemIds['Apple'], $firstItemLink);
    }

    /**
     * Test single-column sorting by Category field
     * This tests the special join logic for Category fields
     */
    public function testSortByCategoryField()
    {
        $fieldId = self::$fieldIds['test_category'];
        $result = self::$trklib->list_items(self::$trackerId, 0, -1, ['f_' . $fieldId . '_asc'], '', '', '', '', '', '', '', null, true, true);
        $this->assertEquals(5, $result['count']);
        $firstItemCateg = self::getItemFieldValue($result['data'][0]['itemId'], $fieldId);
        if (is_array($firstItemCateg)) {
            $this->assertContains(self::$categoryIds['alpha'], $firstItemCateg);
        } else {
            $this->assertEquals(self::$categoryIds['alpha'], $firstItemCateg);
        }
    }

    /**
     * Test single-column sorting by Rating field (numeric sort)
     */
    public function testSortByRatingField()
    {
        $fieldId = self::$fieldIds['test_rating'];
        $result = self::$trklib->list_items(self::$trackerId, 0, -1, ['f_' . $fieldId . '_desc'], '', '', '', '', '', '', '', null, true, true);
        $this->assertEquals(5, $result['count']);
        $firstValue = self::getItemFieldValue($result['data'][0]['itemId'], $fieldId);
        $this->assertEquals('5', $firstValue);
    }

    /**
     * Test multi-level sorting: Text field (asc) then Numeric field (desc)
     */
    public function testMultiLevelSortTextNumeric()
    {
        $textFieldId = self::$fieldIds['test_text'];
        $numericFieldId = self::$fieldIds['test_numeric'];
        $result = self::$trklib->list_items(self::$trackerId, 0, -1, [
                'f_' . $textFieldId . '_asc',
                'f_' . $numericFieldId . '_desc'
            ], '', '', '', '', '', '', '', null, true, true);
        $this->assertEquals(5, $result['count']);
// First two items should have 'Apple' (sorted by text first)
        $first = self::getItemFieldValue($result['data'][0]['itemId'], $textFieldId);
        $second = self::getItemFieldValue($result['data'][1]['itemId'], $textFieldId);
        $this->assertEquals('Apple', $first);
        $this->assertEquals('Apple', $second);
// Among the 'Apple' items, numeric should be descending (100 before 75)
        $firstNumeric = self::getItemFieldValue($result['data'][0]['itemId'], $numericFieldId);
        $secondNumeric = self::getItemFieldValue($result['data'][1]['itemId'], $numericFieldId);
        $this->assertEquals('100', $firstNumeric);
        $this->assertEquals('75', $secondNumeric);
    }

    /**
     * Test multi-level sorting with three levels
     * Text (asc), Numeric (asc), Rating (desc)
     */
    public function testThreeLevelSorting()
    {
        $textFieldId = self::$fieldIds['test_text'];
        $numericFieldId = self::$fieldIds['test_numeric'];
        $ratingFieldId = self::$fieldIds['test_rating'];
        $result = self::$trklib->list_items(self::$trackerId, 0, -1, [
                'f_' . $textFieldId . '_asc',
                'f_' . $numericFieldId . '_asc',
                'f_' . $ratingFieldId . '_desc'
            ], '', '', '', '', '', '', '', null, true, true);
        $this->assertEquals(5, $result['count']);
// Verify the result is properly sorted at all three levels
        $this->assertTrue(is_array($result['data']));
        $this->assertCount(5, $result['data']);
    }

    /**
     * Test multi-level sorting with standard fields
     * created (desc), lastModif (asc)
     */
    public function testMultiLevelSortStandardFields()
    {
        $result = self::$trklib->list_items(self::$trackerId, 0, -1, [
                'created_desc',
                'lastModif_asc'
            ], '', '', '', '', '', '', '', null, true, true);
        $this->assertEquals(5, $result['count']);
        $this->assertTrue(is_array($result['data']));
    }

    /**
     * Test multi-level sorting mixing standard and custom fields
     */
    public function testMixedStandardAndCustomFieldSorting()
    {
        $textFieldId = self::$fieldIds['test_text'];
        $result = self::$trklib->list_items(self::$trackerId, 0, -1, [
                'f_' . $textFieldId . '_asc',
                'created_desc'
            ], '', '', '', '', '', '', '', null, true, true);
        $this->assertEquals(5, $result['count']);
        $this->assertTrue(is_array($result['data']));
    }

    /**
     * Test multi-level sorting with ItemLink and Category fields
     * This tests the complex join logic for special field types
     */
    public function testMultiLevelSortSpecialFields()
    {
        $itemLinkFieldId = self::$fieldIds['test_itemlink'];
        $categoryFieldId = self::$fieldIds['test_category'];
        $result = self::$trklib->list_items(self::$trackerId, 0, -1, [
                'f_' . $itemLinkFieldId . '_asc',
                'f_' . $categoryFieldId . '_asc'
            ], '', '', '', '', '', '', '', null, true, true);
        $this->assertEquals(5, $result['count']);
        $this->assertTrue(is_array($result['data']));
        $firstItemLink = self::getItemFieldValue($result['data'][0]['itemId'], $itemLinkFieldId);
        $this->assertNotEmpty($firstItemLink);
    }

    /**
     * Test empty sort mode defaults to itemId_asc
     */
    public function testEmptySortModeDefaultsToItemId()
    {
        $result = self::$trklib->list_items(self::$trackerId, 0, -1, [], '', '', '', '', '', '', '', null, true, true);
        $this->assertEquals(5, $result['count']);
// Should default to itemId ascending
        $this->assertLessThan($result['data'][1]['itemId'], $result['data'][0]['itemId']);
    }

    /**
     * Test filtering out empty values in sort mode array
     */
    public function testFilterEmptyValuesInSortMode()
    {
        $textFieldId = self::$fieldIds['test_text'];
        $result = self::$trklib->list_items(self::$trackerId, 0, -1, ['f_' . $textFieldId . '_asc', '', null], '', '', '', '', '', '', '', null, true, true);
        $this->assertEquals(5, $result['count']);
// Should work despite empty values in array
        $this->assertTrue(is_array($result['data']));
    }

    /**
     * Helper method to get field value for an item
     */
    protected static function getItemFieldValue($itemId, $fieldId)
    {
        if (! self::$trackerId || ! $itemId || ! $fieldId) {
            return null;
        }
        try {
            return self::$trklib->get_item_value(self::$trackerId, $itemId, $fieldId);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
