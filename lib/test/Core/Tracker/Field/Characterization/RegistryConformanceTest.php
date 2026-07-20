<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Tracker\Field\Characterization;

use TikiLib;
use TikiTestCase;
use Tracker_Field_Factory;

/**
 * Pins the tracker field-type registry and its required metadata.
 *
 * Every field type code registered by Tracker_Field_Factory is matched to
 * the class expected to serve it. Required type information is checked
 * without instantiating handlers, so external-service types do not perform
 * any I/O.
 *
 * @group integration
 * @group tracker
 */
class RegistryConformanceTest extends TikiTestCase
{
    /**
     * Field classes that only get this conformance coverage, and why they
     * are excluded from per-type characterization tests. The capability
     * matrix report reads these reasons.
     */
    public const CONFORMANCE_ONLY = [
        'Tracker_Field_Action' => 'posts the item form to an external URL',
        'Tracker_Field_BigBlueButton' => 'requires a BigBlueButton server API',
        'Tracker_Field_Kaltura' => 'requires a Kaltura video service',
        'Tracker_Field_Ldap' => 'requires an LDAP server',
        'Tracker_Field_PeerTube' => 'requires a PeerTube instance',
        'Tracker_Field_ShowTikiOrg' => 'requires the show.tiki.org remote service (experimental)',
        'Tracker_Field_TikiManager' => 'requires a Tiki Manager instance (remote shell)',
        'Tracker_Field_WebService' => 'calls an arbitrary external webservice',
    ];

    /**
     * Every registered type code and the class expected to serve it.
     * Recorded from Tracker_Field_Factory on 2026-07-04.
     */
    private const EXPECTED_CODE_TO_CLASS = [
        '*' => 'Tracker_Field_Rating',
        'A' => 'Tracker_Field_File',
        'C' => 'Tracker_Field_Computed',
        'CAL' => 'Tracker_Field_CalendarItem',
        'D' => 'Tracker_Field_Dropdown',
        'DUR' => 'Tracker_Field_Duration',
        'EF' => 'Tracker_Field_EmailFolder',
        'F' => 'Tracker_Field_Freetags',
        'FG' => 'Tracker_Field_Files',
        'G' => 'Tracker_Field_Location',
        'GF' => 'Tracker_Field_GeographicFeature',
        'I' => 'Tracker_Field_Ip',
        'L' => 'Tracker_Field_Url',
        'LANG' => 'Tracker_Field_Language',
        'M' => 'Tracker_Field_Dropdown',
        'N' => 'Tracker_Field_InGroup',
        'P' => 'Tracker_Field_Ldap',
        'R' => 'Tracker_Field_Dropdown',
        'REL' => 'Tracker_Field_Relation',
        'S' => 'Tracker_Field_StaticText',
        'SLIDER' => 'Tracker_Field_Slider',
        'STARS' => 'Tracker_Field_Rating',
        'STO' => 'Tracker_Field_ShowTikiOrg',
        'TM' => 'Tracker_Field_TikiManager',
        'U' => 'Tracker_Field_UserSubscription',
        'W' => 'Tracker_Field_WebService',
        'a' => 'Tracker_Field_TextArea',
        'articles' => 'Tracker_Field_Articles',
        'b' => 'Tracker_Field_Currency',
        'barcode' => 'Tracker_Field_BarCode',
        'bbb' => 'Tracker_Field_BigBlueButton',
        'c' => 'Tracker_Field_Checkbox',
        'd' => 'Tracker_Field_Dropdown',
        'e' => 'Tracker_Field_Category',
        'f' => 'Tracker_Field_DateTime',
        'g' => 'Tracker_Field_GroupSelector',
        'h' => 'Tracker_Field_Heading',
        'i' => 'Tracker_Field_Image',
        'icon' => 'Tracker_Field_Icon',
        'j' => 'Tracker_Field_JsCalendar',
        'k' => 'Tracker_Field_PageSelector',
        'kaltura' => 'Tracker_Field_Kaltura',
        'l' => 'Tracker_Field_ItemsList',
        'm' => 'Tracker_Field_Email',
        'math' => 'Tracker_Field_Math',
        'n' => 'Tracker_Field_Numeric',
        'p' => 'Tracker_Field_UserPreference',
        'peertube' => 'Tracker_Field_PeerTube',
        'q' => 'Tracker_Field_AutoIncrement',
        'r' => 'Tracker_Field_ItemLink',
        's' => 'Tracker_Field_Rating',
        't' => 'Tracker_Field_Text',
        'u' => 'Tracker_Field_UserSelector',
        'usergroups' => 'Tracker_Field_UserGroups',
        'w' => 'Tracker_Field_DynamicList',
        'wiki' => 'Tracker_Field_Wiki',
        'x' => 'Tracker_Field_Action',
        'y' => 'Tracker_Field_CountrySelector',
    ];

    public static function setUpBeforeClass(): void
    {
        global $prefs;
        parent::setUpBeforeClass();
        // The factory caches its type map; a stale cache from an earlier run
        // could mask a newly added or removed type.
        TikiLib::lib('cache')->invalidate('fieldtypes.' . $prefs['language']);
    }

    public function testRegisteredTypeCodesMatchExpected(): void
    {
        $actual = [];
        foreach (Tracker_Field_Factory::getFieldTypes() as $code => $info) {
            $actual[$code] = Tracker_Field_Factory::getTrackerItemFieldClassFromType($code);
        }
        ksort($actual);

        $expected = self::EXPECTED_CODE_TO_CLASS;
        ksort($expected);

        $this->assertSame(
            $expected,
            $actual,
            'The field-type registry changed. If a type was added or removed on purpose,'
            . ' update EXPECTED_CODE_TO_CLASS, add a characterization test (or a'
            . ' CONFORMANCE_ONLY exclusion with a reason), and regenerate the capability'
            . ' matrix report (lib/test/tools/tracker-capability-matrix.php).'
        );
    }

    public function testEveryTypeInfoHasContractKeys(): void
    {
        foreach (Tracker_Field_Factory::getFieldTypes() as $code => $info) {
            $this->assertArrayHasKey('name', $info, "type '$code' info has no name");
            $this->assertArrayHasKey('prefs', $info, "type '$code' info has no prefs list");
            $this->assertIsArray($info['prefs'], "type '$code' prefs is not an array");
        }
    }

    public function testConformanceOnlyClassesAreRegistered(): void
    {
        foreach (self::CONFORMANCE_ONLY as $class => $reason) {
            $this->assertContains(
                $class,
                self::EXPECTED_CODE_TO_CLASS,
                "CONFORMANCE_ONLY names '$class' which is not in the registry ($reason)"
            );
        }
    }
}
