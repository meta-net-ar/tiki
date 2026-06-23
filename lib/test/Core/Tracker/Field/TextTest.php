<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiTests;

use Tracker_Definition;
use TikiLib;

/**
 * Test for Tracker Text Field, specifically multilingual behavior
 */
class TextTest extends \PHPUnit\Framework\TestCase
{
    protected static $trklib;
    protected static $trackerId;
    protected static $old_pref_trackers;
    protected static $old_pref_multilingual;

    /**
     * @var string Store original language to restore after test
     */
    protected $originalLanguage;

    public static function setUpBeforeClass(): void
    {
        global $prefs;
        self::$old_pref_trackers = $prefs['feature_trackers'];
        self::$old_pref_multilingual = $prefs['feature_multilingual'];

        $prefs['feature_trackers'] = 'y';
        $prefs['feature_multilingual'] = 'y';

        parent::setUpBeforeClass();
        self::$trklib = TikiLib::lib('trk');

        self::$trackerId = self::$trklib->replace_tracker(null, 'Test Text Tracker', '', [], 'n');

        $fields = [[
            'name' => 'MultiText',
            'type' => 't',
            'isHidden' => 'n',
            'isMandatory' => 'n',
            'isMultilingual' => 'y',
            'permName' => 'test_multi_text',
        ]];

        foreach ($fields as $i => $field) {
            self::$trklib->replace_tracker_field(
                self::$trackerId,
                0,
                $field['name'],
                $field['type'],
                'y', // isMain
                'y',
                'y',
                'y',
                $field['isHidden'],
                $field['isMandatory'],
                ($i + 1) * 10,
                $field['options'] ?? '',
                '',
                $field['isMultilingual'],
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
        }
    }

    public static function tearDownAfterClass(): void
    {
        global $prefs;
        $prefs['feature_trackers'] = self::$old_pref_trackers;
        $prefs['feature_multilingual'] = self::$old_pref_multilingual;

        parent::tearDownAfterClass();
        self::$trklib->remove_tracker(self::$trackerId);
    }

    protected function setUp(): void
    {
        parent::setUp();
        global $prefs;
        $this->originalLanguage = $prefs['language'] ?? 'en';
    }

    protected function tearDown(): void
    {
        global $prefs;
        $prefs['language'] = $this->originalLanguage;
        parent::tearDown();
    }

    public function testMultilingualMainTitleOutput(): void
    {
        $definition = Tracker_Definition::get(self::$trackerId);
        $fields = $definition->getFields();

        $fields[0]['value'] = json_encode(['en' => 'English Title', 'fr' => 'Titre Français']);

        $itemId = self::$trklib->replace_item(self::$trackerId, 0, ['data' => $fields], 'o');
        $this->assertNotEmpty($itemId);

        // Test English
        $GLOBALS['prefs']['language'] = 'en';
        $outputEn = self::$trklib->get_isMain_value(self::$trackerId, $itemId);
        $this->assertEquals('English Title', $outputEn, "Main title should return the English value");
        $outputEnNoTracker = self::$trklib->get_isMain_value(null, $itemId);
        $this->assertEquals('English Title', $outputEnNoTracker, "Main title should return English even when trackerId is omitted");

        // Test French
        $GLOBALS['prefs']['language'] = 'fr';
        $outputFr = self::$trklib->get_isMain_value(self::$trackerId, $itemId);
        $this->assertEquals('Titre Français', $outputFr, "Main title should return the French value");
        $outputFrNoTracker = self::$trklib->get_isMain_value(null, $itemId);
        $this->assertEquals('Titre Français', $outputFrNoTracker, "Main title should return French even when trackerId is omitted");
    }
}
