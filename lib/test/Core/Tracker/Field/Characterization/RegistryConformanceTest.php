<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Tracker\Field\Characterization;

use ReflectionClass;
use ReflectionMethod;
use TikiLib;
use TikiTestCase;
use Tracker_Field_Factory;

/**
 * Pins the tracker field-type registry and its capability matrix.
 *
 * This is the executable form of the capability matrix: every field type
 * code registered by Tracker_Field_Factory, the class it maps to, and the
 * capability interfaces each class implements. If a field type is added,
 * removed, or gains/loses an interface, this test fails so the change is
 * made consciously (and the matrix report regenerated).
 *
 * No field class is instantiated here — everything is reflection — so the
 * types that depend on external services (see CONFORMANCE_ONLY) are covered
 * without any I/O.
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

    /**
     * Capability interfaces each class implements, beyond ItemFieldInterface
     * and IndexableInterface which every field inherits from
     * AbstractItemField. Recorded on 2026-07-04.
     */
    private const EXPECTED_CAPABILITIES = [
        'Tracker_Field_Action' => [],
        'Tracker_Field_Articles' => [],
        'Tracker_Field_AutoIncrement' => ['Exportable', 'Filterable'],
        'Tracker_Field_BarCode' => ['Exportable', 'Filterable', 'Synchronizable'],
        'Tracker_Field_BigBlueButton' => ['Filterable'],
        'Tracker_Field_CalendarItem' => ['Exportable', 'Filterable', 'Synchronizable'],
        'Tracker_Field_Category' => ['Exportable', 'Filterable', 'Synchronizable'],
        'Tracker_Field_Checkbox' => ['Exportable', 'Filterable', 'Synchronizable'],
        'Tracker_Field_Computed' => [],
        'Tracker_Field_CountrySelector' => ['Enumerable', 'Exportable', 'Filterable', 'Synchronizable'],
        'Tracker_Field_Currency' => ['Exportable', 'Filterable', 'Synchronizable'],
        'Tracker_Field_DateTime' => ['Exportable', 'Filterable', 'Synchronizable'],
        'Tracker_Field_Dropdown' => ['Enumerable', 'Exportable', 'Filterable', 'Synchronizable'],
        'Tracker_Field_Duration' => ['Exportable', 'Filterable', 'Synchronizable'],
        'Tracker_Field_DynamicList' => ['Enumerable', 'Exportable', 'Filterable'],
        'Tracker_Field_Email' => ['Exportable', 'Filterable', 'Synchronizable'],
        'Tracker_Field_EmailFolder' => ['Exportable'],
        'Tracker_Field_File' => [],
        'Tracker_Field_Files' => ['Exportable'],
        'Tracker_Field_Freetags' => ['Exportable', 'Filterable', 'Synchronizable'],
        'Tracker_Field_GeographicFeature' => ['Exportable', 'Synchronizable'],
        'Tracker_Field_GroupSelector' => ['Exportable', 'Filterable'],
        'Tracker_Field_Heading' => ['Synchronizable'],
        'Tracker_Field_Icon' => [],
        'Tracker_Field_Image' => [],
        'Tracker_Field_InGroup' => [],
        'Tracker_Field_Ip' => ['Synchronizable'],
        'Tracker_Field_ItemLink' => ['Enumerable', 'Exportable', 'Filterable', 'Synchronizable'],
        'Tracker_Field_ItemsList' => ['Exportable', 'Filterable'],
        'Tracker_Field_JsCalendar' => ['Exportable', 'Filterable', 'Synchronizable'],
        'Tracker_Field_Kaltura' => ['Synchronizable'],
        'Tracker_Field_Language' => ['Exportable', 'Synchronizable'],
        'Tracker_Field_Ldap' => [],
        'Tracker_Field_Location' => ['Exportable', 'Synchronizable'],
        'Tracker_Field_Math' => ['Exportable', 'Filterable', 'Synchronizable'],
        'Tracker_Field_Numeric' => ['Exportable', 'Filterable', 'Synchronizable'],
        'Tracker_Field_PageSelector' => [],
        'Tracker_Field_PeerTube' => ['Synchronizable'],
        'Tracker_Field_Rating' => [],
        'Tracker_Field_Relation' => ['Exportable', 'Filterable'],
        'Tracker_Field_ShowTikiOrg' => [],
        'Tracker_Field_Slider' => ['Exportable', 'Filterable', 'Synchronizable'],
        'Tracker_Field_StaticText' => ['Synchronizable'],
        'Tracker_Field_Text' => ['Exportable', 'Filterable', 'Synchronizable'],
        'Tracker_Field_TextArea' => ['Exportable', 'Filterable', 'Synchronizable'],
        'Tracker_Field_TikiManager' => [],
        'Tracker_Field_Url' => ['Exportable', 'Synchronizable'],
        'Tracker_Field_UserGroups' => ['Filterable'],
        'Tracker_Field_UserPreference' => [],
        'Tracker_Field_UserSelector' => ['AutoSyncable', 'Enumerable', 'Exportable', 'Filterable', 'Synchronizable'],
        'Tracker_Field_UserSubscription' => [],
        'Tracker_Field_WebService' => [],
        'Tracker_Field_Wiki' => ['Exportable', 'Filterable', 'Synchronizable'],
    ];

    private const CAPABILITY_INTERFACES = [
        'AutoSyncable' => 'Tracker\Field\AutoSyncableInterface',
        'Enumerable' => 'Tracker\Field\EnumerableInterface',
        'Exportable' => 'Tracker\Field\ExportableInterface',
        'Filterable' => 'Tracker\Field\FilterableInterface',
        'Synchronizable' => 'Tracker\Field\SynchronizableInterface',
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

    public function testCapabilityInterfacesMatchExpected(): void
    {
        $actual = [];
        foreach (array_unique(self::EXPECTED_CODE_TO_CLASS) as $class) {
            $reflection = new ReflectionClass($class);
            $capabilities = [];
            foreach (self::CAPABILITY_INTERFACES as $label => $interface) {
                if ($reflection->implementsInterface($interface)) {
                    $capabilities[] = $label;
                }
            }
            $actual[$class] = $capabilities;

            // The two base capabilities every field must have.
            $this->assertTrue(
                $reflection->implementsInterface('Tracker\Field\ItemFieldInterface'),
                "$class does not implement ItemFieldInterface"
            );
            $this->assertTrue(
                $reflection->implementsInterface('Tracker\Field\IndexableInterface'),
                "$class does not implement IndexableInterface"
            );
        }
        ksort($actual);

        $expected = self::EXPECTED_CAPABILITIES;
        ksort($expected);

        $this->assertSame(
            $expected,
            $actual,
            'A field class gained or lost a capability interface. If intentional, update'
            . ' EXPECTED_CAPABILITIES and regenerate the capability matrix report.'
        );
    }

    public function testEveryClassHasConformingGetFieldData(): void
    {
        foreach (array_unique(self::EXPECTED_CODE_TO_CLASS) as $class) {
            $method = new ReflectionMethod($class, 'getFieldData');

            $this->assertFalse($method->isAbstract(), "$class does not implement getFieldData()");
            $this->assertSame(
                'array',
                (string) $method->getReturnType(),
                "$class getFieldData() must declare an array return type"
            );

            $parameters = $method->getParameters();
            $this->assertCount(1, $parameters, "$class getFieldData() must take exactly one parameter");
            $this->assertSame(
                'array',
                (string) $parameters[0]->getType(),
                "$class getFieldData() parameter must be typed array"
            );
            $this->assertTrue(
                $parameters[0]->isDefaultValueAvailable(),
                "$class getFieldData() parameter must have a default value"
            );
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
