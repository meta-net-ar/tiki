<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Lib\Test\TestHelpers\GuardedSetUpTrait;
use Tiki\Lib\Test\TestHelpers\Tracker\TrackerFixtureFactory;
use Tiki\Lib\Test\TestHelpers\Tracker\TrackerPermissionsFixture;

/**
 * @group integration
 * @group tracker
 */
class TrackerItemPermissionTest extends TikiTestCase
{
    use GuardedSetUpTrait;

    protected static $trklib;
    protected static $unifiedlib;
    protected static $fixtures;
    protected static $permsFixture;
    protected static $trackerId;
    protected static $old_pref;
    protected static $old_user;

    public static function setUpBeforeClass(): void
    {
        self::guardStaticSetUp(function () {
            global $prefs;
            self::$old_pref = $prefs['feature_trackers'];
            $prefs['feature_trackers'] = 'y';

            parent::setUpBeforeClass();
            self::$trklib = TikiLib::lib('trk');
            self::$fixtures = new TrackerFixtureFactory();
            self::$permsFixture = new TrackerPermissionsFixture();

            // create tracker and couple of fields
            self::$trackerId = self::$fixtures->createTracker('Test Tracker');

            self::$fixtures->addFields(self::$trackerId, [[
                'name' => 'Name',
                'type' => 't',
                'isHidden' => 'n',
                'isMandatory' => 'y',
                'visibleBy' => null,
                'permName' => 'test_name',
            ], [
                'name' => 'Admin info',
                'type' => 't',
                'isHidden' => 'y',
                'isMandatory' => 'n',
                'visibleBy' => null,
                'permName' => 'test_admin',
            ], [
                'name' => 'Registered info',
                'type' => 't',
                'isHidden' => 'n',
                'isMandatory' => 'n',
                'visibleBy' => ['Registered'],
                'permName' => 'test_registered',
            ], [
                'name' => 'Special info',
                'type' => 't',
                'isHidden' => 'n',
                'isMandatory' => 'n',
                'visibleBy' => ['SpecialGroup'],
                'permName' => 'test_special',
            ]]);

            self::$fixtures->createItem(self::$trackerId, [
                'test_name' => 'Test item',
                'test_admin' => 'Secret value',
                'test_registered' => 'Secret value',
                'test_special' => 'Secret value',
            ]);

            self::$permsFixture->grantGroupPermissions([
                'Registered' => ['tiki_p_view_trackers'],
            ]);

            self::$unifiedlib = TikiLib::lib('unifiedsearch');
            self::$unifiedlib->rebuild();
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->abortIfStaticSetUpFailed();
    }

    public static function tearDownAfterClass(): void
    {
        global $prefs;
        $prefs['feature_trackers'] = self::$old_pref;

        parent::tearDownAfterClass();
        self::$fixtures->cleanup();

        self::$permsFixture->dropUnifiedIndex();

        self::$permsFixture->revokeGroupPermissions();
    }

    public function testAdminCanSeeAllFields(): void
    {
        // impersonate admin
        self::$permsFixture->impersonate('admin', ['Admins']);

        $query = self::$unifiedlib->buildQuery([
            'type' => 'trackeritem',
            'tracker_id' => self::$trackerId,
        ]);
        $result = $query->search(self::$unifiedlib->getIndex());
        $resultArray = $result->getArrayCopy();

        $this->assertEquals('Test item', $resultArray[0]['tracker_field_test_name']);
        $this->assertEquals('Secret value', $resultArray[0]['tracker_field_test_admin']);
        $this->assertEquals('Secret value', $resultArray[0]['tracker_field_test_registered']);
        $this->assertEquals('Secret value', $resultArray[0]['tracker_field_test_special']);
    }

    public function testRegisteredCanSeeSpecificFields(): void
    {
        // impersonate a regitered user
        self::$permsFixture->impersonate('someone', ['Registered']);

        $query = self::$unifiedlib->buildQuery([
            'type' => 'trackeritem',
            'tracker_id' => self::$trackerId,
        ]);
        $result = $query->search(self::$unifiedlib->getIndex());
        $resultArray = $result->getArrayCopy();

        $this->assertEquals('Test item', $resultArray[0]['tracker_field_test_name']);
        $this->assertEquals('', $resultArray[0]['tracker_field_test_admin']);
        $this->assertEquals('Secret value', $resultArray[0]['tracker_field_test_registered']);
        $this->assertEquals('', $resultArray[0]['tracker_field_test_special']);
    }
}
