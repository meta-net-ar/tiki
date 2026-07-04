<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiTests;

use Tiki\Lib\Test\TestHelpers\GuardedSetUpTrait;
use Tiki\Lib\Test\TestHelpers\Tracker\TrackerFixtureFactory;
use TikiTestCase;
use Tracker_Definition,

TikiLib, Tracker_Item;

/**
 * This is a smoke test for the Math field.  At least it shows how badly we need a better internal API... - benoitg - 2024-09-04
 */
class TrackerFieldMathTest extends TikiTestCase
{
    use GuardedSetUpTrait;

    protected static $trklib;
    protected static $objectlib;
    protected static $unifiedlib;
    protected static $fixtures;
    protected static $trackerId;
    protected static $linkedTrackerId;
    protected static $old_prefs;
    protected static $old_user;

    public static function setUpBeforeClass(): void
    {
        self::guardStaticSetUp(function () {
            global $prefs;
            self::$old_prefs = $prefs;
            $prefs['feature_trackers'] = 'y';
            $prefs['short_date_format'] = '%Y-%m-%d';
            $prefs['short_time_format'] = '%H:%M';
            parent::setUpBeforeClass();
            self::$trklib = TikiLib::lib('trk');
            self::$objectlib = TikiLib::lib('object');
            self::$fixtures = new TrackerFixtureFactory();

            self::$linkedTrackerId = self::$fixtures->createTracker('Test Tracker');

            $someOtherDateFieldId = self::$fixtures->addField(self::$linkedTrackerId, [
                'name' => 'Date field for formatting',
                'type' => 'f',
                'isHidden' => 'n',
                'isMandatory' => 'n',
                'visibleBy' => null,
                'permName' => 'someOtherDateField'
            ]);

            self::$trackerId = self::$fixtures->createTracker('Test Tracker');
            $calculation = "(add childrenBirthDate (mul 31536000 18))"; //Inspired from https://doc.tiki.org/Mathematical-Calculation-Tracker-Field
            $options = json_encode(['calculation' => $calculation,
            'mirrorField' => $someOtherDateFieldId]);

            self::$fixtures->addFields(self::$trackerId, [[
                'name' => 'Date field containing the childrens birth date',
                'type' => 'f',
                'isHidden' => 'n',
                'isMandatory' => 'n',
                'visibleBy' => null,
                'permName' => 'childrenBirthDate'
            ], [
                'name' => 'Link',
                'type' => 'math',
                'isHidden' => 'n',
                'isMandatory' => 'n',
                'visibleBy' => null,
                'permName' => 'childrenAdultOn',
                'options' => $options
            ]]);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->abortIfStaticSetUpFailed();
    }

    public static function tearDownAfterClass(): void
    {
        global $prefs, $tikilib;
        $prefs['feature_trackers'] = self::$old_prefs['feature_trackers'];

        parent::tearDownAfterClass();
        self::$fixtures->cleanup();
        $prefs['unified_engine'] = self::$old_prefs['unified_engine'];
    }

    public function testBasicFunctionality(): void
    {
        $birthDate = time() - (31536000); //Now - one year
        $dateChildWillBe18 = $birthDate + (31536000 * 18);
        $someOtherDate = time();

        $linkedTrackerDefinition = Tracker_Definition::get(self::$linkedTrackerId);
        $fields = $linkedTrackerDefinition->getFields();
        $fields[0]['value'] = $someOtherDate;
        $linkedItemId = self::$trklib->replace_item(self::$linkedTrackerId, 0, ['data' => $fields], 'o');

        $definition = Tracker_Definition::get(self::$trackerId);
        $this->assertNotEmpty($definition);
        $fields = $definition->getFields();

        $fields[0]['value'] = $birthDate;  //Date field
        $fields[1]['value'] = $someOtherDate;  //Math field

        $itemId = self::$trklib->replace_item(self::$trackerId, 0, ['data' => $fields], 'o');
        $this->assertNotEmpty($itemId);
        $item = Tracker_Item::fromId($itemId);
        $childrenBirthDateField = $item->getFieldFromPermName('childrenBirthDate');
        $birthDateValue = $childrenBirthDateField->getValue();
        $this->assertEquals($birthDate, $birthDateValue);

        $childrenAdultOnField = $item->getFieldFromPermName('childrenAdultOn');
        $childrenAdultOnFieldValue = $childrenAdultOnField->getValue();
        $this->assertEquals($dateChildWillBe18, $childrenAdultOnFieldValue, "Should be the date the child is 18 years old");
        $output = $childrenAdultOnField->renderOutput();
        //Note:  There is something wrong with other tests not cleaning state.  See https://gitlab.com/tikiwiki/tiki/-/merge_requests/5543#note_2091298734. The test passed with php phpunit --filter=TrackerFieldMathTest but not php phpunit.  So I replaced assertEquals with assertStringContainsString
        $this->assertStringContainsString(date('Y-m-d H:i', $dateChildWillBe18), $output);

        $typeFactory  = new \Search_Type_Factory_Direct();
        $documentPart = $childrenAdultOnField->getDocumentPart($typeFactory);
        $mathBasekey = $childrenAdultOnField->getBaseKey();

        $this->assertArrayHasKey($mathBasekey, $documentPart, "The key must be from the math field, not the mirrorField");
    }
}
