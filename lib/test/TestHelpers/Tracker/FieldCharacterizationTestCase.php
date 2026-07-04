<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\TestHelpers\Tracker;

use Tiki\Lib\Test\TestHelpers\GuardedSetUpTrait;

/**
 * Base class for tracker field-type characterization tests.
 *
 * A characterization test records what a field type does today — including
 * oddities — so that later refactoring can be verified against pinned
 * behavior. Expected values are recorded from actual runs, not designed.
 *
 * A concrete subclass only declares data:
 *
 *   - typeCode() — the field type letter/code (e.g. 't', 'd')
 *   - fieldSpecs() — fields to create (TrackerFixtureFactory::addField specs);
 *     the FIRST spec is the field under test
 *   - inputCaseProvider() — getFieldData() cases
 *   - roundTripCaseProvider() — persistence cases (creation path)
 *   - renderCaseProvider() — rendering cases
 *
 * and should usually also declare (each skips with a reminder when left
 * undeclared — an intentional gap is waived in the test docblock instead):
 *
 *   - updateCaseProvider() — save-over-existing-item cases; the only path on
 *     which handleSave() sees a non-null $oldValue
 *   - storedFallbackCaseProvider() — getFieldData([]) on an item holding a
 *     non-empty value; what an edit form shows when the field is not
 *     submitted at all
 *
 * and may override extraPrefs(), createSupportFixtures() and
 * expectedEnumeration() when the type needs them.
 *
 * Preference toggles required by the field type are read from
 * Tracker_Field_Factory::getFieldInfo()['prefs'] — the same list the factory
 * itself enforces — set to 'y' for the class, and restored afterwards.
 *
 * Determinism: the display/server timezone prefs and PHP's default timezone
 * are pinned to UTC for the class (restored afterwards), so date-dependent
 * pins cannot silently depend on the machine running the test. A type whose
 * timezone handling is itself under characterization can override the prefs
 * via extraPrefs().
 *
 * In provider expectations, a scalar/array is compared with assertSame(); a
 * \Closure is called with the actual value and must do its own assertions
 * (escape hatch for non-deterministic outputs like DateTime's now-default).
 *
 * File is named *TestCase.php on purpose: PHPUnit only discovers *Test.php
 * files, so this base never runs by itself.
 */
abstract class FieldCharacterizationTestCase extends \TikiTestCase
{
    use GuardedSetUpTrait;

    /** @var TrackerFixtureFactory */
    protected static $fixtures;

    /** @var int */
    protected static $trackerId;

    /** @var array map of permName => fieldId */
    protected static $fieldIds = [];

    /** @var array prefs touched by this class: key => previous value (null = was unset) */
    private static $prefSnapshot = [];

    /** @var string|null PHP default timezone before the class pinned it to UTC */
    private static $timezoneSnapshot = null;

    /**
     * The field type code under test (e.g. 't', 'd', 'f').
     */
    abstract protected static function typeCode(): string;

    /**
     * Field specs for TrackerFixtureFactory::addFields(). The first spec is
     * the field under test; add support fields (e.g. a title) after it.
     */
    abstract protected static function fieldSpecs(): array;

    /**
     * getFieldData() cases: name => [requestData, expectedSubset].
     *
     * In requestData keys, the placeholder {{ins}} is replaced with the real
     * insert id ('ins_<fieldId>') of the field under test — so '{{ins}}',
     * 'other_{{ins}}' and '{{ins}}_old' all resolve correctly.
     * expectedSubset asserts only the listed keys of the returned array.
     */
    abstract public static function inputCaseProvider(): array;

    /**
     * Persistence cases: name => [valueToStore, expectedStoredRaw, expectedGetValue].
     * valueToStore goes through TrackerFixtureFactory::createItem (the
     * replace_item path); expectedStoredRaw is checked against
     * trklib->get_tracker_item(); expectedGetValue against the bound
     * handler's getValue().
     */
    abstract public static function roundTripCaseProvider(): array;

    /**
     * Rendering cases: name => [storedValue, expectedCsv, inputMarkers, outputMarkers].
     * expectedCsv is an EXACT match of renderInnerOutput(['list_mode' => 'csv'])
     * (the Smarty-free path). inputMarkers/outputMarkers are substrings that
     * must appear in renderInput()/renderOutput(); empty arrays still exercise
     * the render call (a Smarty error in the template fails the test).
     */
    abstract public static function renderCaseProvider(): array;

    /**
     * Update-path cases: name => [initialValue, updateValue, expectedStoredRaw,
     * expectedGetValue]. The item is created with initialValue, then saved
     * again with updateValue through the same replace_item path — the only
     * fixture path on which handleSave() receives a non-null $oldValue.
     * Types whose save behavior branches on creation vs update (counters,
     * autoassign, anything checking is_null($oldValue)) live or die here.
     *
     * Default [] makes testUpdateRoundTrip() skip with a reminder; a type for
     * which the update path is genuinely equivalent to creation waives it in
     * the test docblock instead of silently skipping.
     */
    public static function updateCaseProvider(): array
    {
        return [];
    }

    /**
     * Stored-value fallback cases: name => [storedValue, expectedSubset].
     * Pins getFieldData([]) on a handler bound to an item that already holds
     * storedValue — what an edit form shows when the field is not submitted
     * at all (hidden by permissions, partial API update, section editing).
     * The "absent input" case in inputCaseProvider() only proves the
     * empty-stored-value variant of this fallback.
     *
     * Default [] makes testStoredValueFallback() skip with a reminder; waive
     * in the test docblock when intentionally out of scope.
     */
    public static function storedFallbackCaseProvider(): array
    {
        return [];
    }

    /**
     * Environmental prefs beyond the factory's gating list (e.g. date
     * formats). Gating prefs are read from the factory automatically.
     */
    protected static function extraPrefs(): array
    {
        return [];
    }

    /**
     * Hook for per-type support fixtures (categories, galleries, linked
     * trackers…). Runs after prefs are set, before the tracker is created.
     */
    protected static function createSupportFixtures(): void
    {
    }

    /**
     * Expected getPossibleItemValues() for Enumerable types. Types that do
     * not implement EnumerableInterface never have this called.
     */
    protected static function expectedEnumeration(): array
    {
        static::fail('expectedEnumeration() must be overridden by Enumerable field type tests');
    }

    public static function setUpBeforeClass(): void
    {
        self::guardStaticSetUp(function () {
            global $prefs;

            $info = \Tracker_Field_Factory::getFieldInfo(static::typeCode());
            if (! $info) {
                throw new \RuntimeException('Unknown tracker field type code: ' . static::typeCode());
            }

            // extraPrefs() may override the UTC defaults (a type whose
            // timezone handling is under characterization), never the gating
            // prefs ('+' keeps the left operand's keys).
            $wanted = ['feature_trackers' => 'y']
                + array_fill_keys($info['prefs'] ?? [], 'y')
                + static::extraPrefs()
                + ['display_timezone' => 'UTC', 'server_timezone' => 'UTC'];

            self::$prefSnapshot = [];
            foreach ($wanted as $key => $value) {
                self::$prefSnapshot[$key] = $prefs[$key] ?? null;
                $prefs[$key] = $value;
            }

            self::$timezoneSnapshot = date_default_timezone_get();
            date_default_timezone_set('UTC');

            parent::setUpBeforeClass();

            self::$fixtures = new TrackerFixtureFactory();
            static::createSupportFixtures();

            self::$trackerId = self::$fixtures->createTracker(
                'Characterization ' . static::typeCode() . ' ' . uniqid()
            );
            self::$fieldIds = self::$fixtures->addFields(self::$trackerId, static::fieldSpecs());
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

        if (self::$fixtures) {
            self::$fixtures->cleanup();
            self::$fixtures = null;
        }

        foreach (self::$prefSnapshot as $key => $previous) {
            if ($previous === null) {
                unset($prefs[$key]);
            } else {
                $prefs[$key] = $previous;
            }
        }
        self::$prefSnapshot = [];

        if (self::$timezoneSnapshot !== null) {
            date_default_timezone_set(self::$timezoneSnapshot);
            self::$timezoneSnapshot = null;
        }

        parent::tearDownAfterClass();
    }

    /**
     * @dataProvider inputCaseProvider
     */
    public function testGetFieldData(array $requestData, array $expectedSubset): void
    {
        $handler = $this->handlerWithoutItem();
        $data = $handler->getFieldData($this->resolveRequestData($requestData, $handler));

        foreach ($expectedSubset as $key => $expected) {
            $this->assertArrayHasKey($key, $data, "getFieldData() result is missing key '$key'");
            $this->assertExpected($expected, $data[$key], "getFieldData()['$key']");
        }
    }

    /**
     * @dataProvider roundTripCaseProvider
     */
    public function testRoundTrip($valueToStore, $expectedStoredRaw, $expectedGetValue): void
    {
        $permName = $this->permNameUnderTest();
        $itemId = self::$fixtures->createItem(self::$trackerId, [$permName => $valueToStore]);

        $raw = \TikiLib::lib('trk')->get_tracker_item($itemId);
        $fieldId = self::$fieldIds[$permName];
        $this->assertExpected($expectedStoredRaw, $raw[$fieldId] ?? null, "raw stored value (fieldId $fieldId)");

        $handler = $this->handlerForItem($itemId);
        $this->assertExpected($expectedGetValue, $handler->getValue(), 'getValue() after storage');
    }

    /**
     * @dataProvider renderCaseProvider
     */
    public function testRender($storedValue, $expectedCsv, array $inputMarkers, array $outputMarkers): void
    {
        $permName = $this->permNameUnderTest();
        $itemId = self::$fixtures->createItem(self::$trackerId, [$permName => $storedValue]);
        $handler = $this->renderHandlerForItem($itemId);

        // Exact pin of the Smarty-free CSV path. Visibility of
        // renderInnerOutput varies across types (protected in the base,
        // public in some overrides), so go through Reflection uniformly.
        $method = new \ReflectionMethod($handler, 'renderInnerOutput');
        $csv = $method->invoke($handler, ['list_mode' => 'csv']);
        $this->assertExpected($expectedCsv, $csv, "renderInnerOutput(['list_mode' => 'csv'])");

        // Full renders go through Smarty; exact-HTML pinning is too fragile
        // (documented cross-test render state bleed), so assert markers only.
        // The call itself is still the test: a template error fails here.
        $inputHtml = $handler->renderInput();
        $this->assertIsString($inputHtml);
        foreach ($inputMarkers as $marker) {
            $this->assertStringContainsString(
                $this->resolveMarker($marker, $handler),
                $inputHtml,
                'renderInput() marker'
            );
        }

        // renderOutput() reads $_GET['page'] directly; keep the test
        // independent of request state.
        $pageWasSet = array_key_exists('page', $_GET);
        $previousPage = $_GET['page'] ?? null;
        unset($_GET['page']);
        try {
            $outputHtml = $handler->renderOutput();
        } finally {
            if ($pageWasSet) {
                $_GET['page'] = $previousPage;
            }
        }
        $this->assertIsString($outputHtml);
        foreach ($outputMarkers as $marker) {
            $this->assertStringContainsString(
                $this->resolveMarker($marker, $handler),
                $outputHtml,
                'renderOutput() marker'
            );
        }
    }

    /**
     * Update-path round trip — see updateCaseProvider(). Not provider-driven
     * (PHPUnit reports an empty data provider as an error, and the empty case
     * here means "not declared yet", which must read as a skip, not a failure;
     * the annotation is also parsed anywhere in a docblock, so it cannot even
     * be named here).
     */
    public function testUpdateRoundTrip(): void
    {
        $cases = static::updateCaseProvider();
        if (! $cases) {
            $this->markTestSkipped(
                'No update-path cases declared. Override updateCaseProvider() '
                . '(handleSave() sees a non-null $oldValue only on this path), '
                . 'or waive the update path explicitly in the test docblock.'
            );
        }

        $permName = $this->permNameUnderTest();
        $fieldId = self::$fieldIds[$permName];

        foreach ($cases as $name => [$initial, $update, $expectedStoredRaw, $expectedGetValue]) {
            $itemId = self::$fixtures->createItem(self::$trackerId, [$permName => $initial]);
            self::$fixtures->updateItem(self::$trackerId, $itemId, [$permName => $update]);

            $raw = \TikiLib::lib('trk')->get_tracker_item($itemId);
            $this->assertExpected($expectedStoredRaw, $raw[$fieldId] ?? null, "[$name] raw stored value after update");

            $handler = $this->handlerForItem($itemId);
            $this->assertExpected($expectedGetValue, $handler->getValue(), "[$name] getValue() after update");
        }
    }

    /**
     * Stored-value fallback — see storedFallbackCaseProvider(). Same
     * skip-not-fail rationale as testUpdateRoundTrip().
     */
    public function testStoredValueFallback(): void
    {
        $cases = static::storedFallbackCaseProvider();
        if (! $cases) {
            $this->markTestSkipped(
                'No stored-fallback cases declared. Override storedFallbackCaseProvider() '
                . '(getFieldData([]) on a non-empty stored value — the edit-form fallback), '
                . 'or waive it explicitly in the test docblock.'
            );
        }

        foreach ($cases as $name => [$storedValue, $expectedSubset]) {
            $itemId = self::$fixtures->createItem(self::$trackerId, [
                $this->permNameUnderTest() => $storedValue,
            ]);
            $handler = $this->handlerForItem($itemId);
            $data = $handler->getFieldData([]);

            foreach ($expectedSubset as $key => $expected) {
                $this->assertArrayHasKey($key, $data, "[$name] getFieldData([]) result is missing key '$key'");
                $this->assertExpected($expected, $data[$key], "[$name] getFieldData([])['$key']");
            }
        }
    }

    public function testIndexableDocumentPart(): void
    {
        $cases = static::roundTripCaseProvider();
        $firstCase = reset($cases);
        $itemId = self::$fixtures->createItem(self::$trackerId, [
            $this->permNameUnderTest() => $firstCase[0],
        ]);
        $handler = $this->handlerForItem($itemId);

        $typeFactory = new \Search_Type_Factory_Direct();
        $documentPart = $handler->getDocumentPart($typeFactory);

        $baseKey = $handler->getBaseKey();
        $this->assertSame('tracker_field_' . $this->permNameUnderTest(), $baseKey);
        $this->assertArrayHasKey($baseKey, $documentPart, 'getDocumentPart() must provide the base key');
        $this->assertContains($baseKey, $handler->getProvidedFields());
    }

    public function testCapabilities(): void
    {
        $cases = static::roundTripCaseProvider();
        $firstCase = reset($cases);
        $itemId = self::$fixtures->createItem(self::$trackerId, [
            $this->permNameUnderTest() => $firstCase[0],
        ]);
        $handler = $this->handlerForItem($itemId);

        if ($handler instanceof \Tracker\Field\ExportableInterface) {
            $schema = $handler->getTabularSchema();
            $this->assertInstanceOf(\Tracker\Tabular\Schema::class, $schema);
            $this->assertNotEmpty($schema->getColumns(), 'Exportable type must declare tabular columns');
        }

        if ($handler instanceof \Tracker\Field\FilterableInterface) {
            $collection = $handler->getFilterCollection();
            $this->assertNotNull($collection, 'Filterable type must return a filter collection');
        }

        if ($handler instanceof \Tracker\Field\EnumerableInterface) {
            $this->assertSame(static::expectedEnumeration(), $handler->getPossibleItemValues());
            $this->assertIsBool($handler->canHaveMultipleValues());
        }
    }

    /**
     * permName of the field under test (first entry of fieldSpecs()).
     */
    protected function permNameUnderTest(): string
    {
        $specs = static::fieldSpecs();
        return $specs[0]['permName'];
    }

    /**
     * Handler with no item bound — the state getFieldData() sees when a new
     * item form is submitted. Fails loudly (instead of the factory's silent
     * Feedback::error + null) when a gating pref is off.
     *
     * Pass a permName to get a handler for an option-variant field declared
     * in fieldSpecs() (e.g. the same type configured date-only); defaults to
     * the field under test.
     */
    protected function handlerWithoutItem(?string $permName = null): \Tracker\Field\AbstractItemField
    {
        $definition = \Tracker_Definition::get(self::$trackerId, false);
        $this->assertNotNull($definition, 'No tracker definition for the characterization tracker');

        $field = $definition->getFieldFromPermName($permName ?? $this->permNameUnderTest());
        $this->assertNotNull($field, 'Field under test not found in the tracker definition');

        $handler = $definition->getFieldFactory()->getHandler($field);
        $info = \Tracker_Field_Factory::getFieldInfo(static::typeCode());
        $this->assertNotNull(
            $handler,
            'Factory returned no handler for type ' . static::typeCode()
            . ' — check gating prefs: ' . implode(', ', $info['prefs'] ?? [])
        );

        return $handler;
    }

    /**
     * Handler bound to a saved item (Tracker_Item path) — the state
     * getValue(), getDocumentPart() and the capability methods see.
     */
    protected function handlerForItem(int $itemId, ?string $permName = null): \Tracker\Field\AbstractItemField
    {
        $item = \Tracker_Item::fromId($itemId);
        $this->assertNotNull($item, "Tracker_Item::fromId($itemId) returned null");

        $handler = $item->getFieldFromPermName($permName ?? $this->permNameUnderTest());
        $this->assertNotNull($handler, 'No handler for the field under test on item ' . $itemId);

        return $handler;
    }

    /**
     * Handler prepared the way production prepares a field for rendering:
     * merge getFieldData() into the field definition, add ins_id and
     * html_name, and rebuild the handler (Tracker_Item::prepareFieldInput
     * and the TrackerLib field-render path both do this). The templates
     * rely on the merged keys: text.tpl reads ins_id, dropdown.tpl reads
     * html_name for the input name, Dropdown output reads 'selected', …
     */
    protected function renderHandlerForItem(int $itemId, ?string $permName = null): \Tracker\Field\AbstractItemField
    {
        $permName = $permName ?? $this->permNameUnderTest();

        $definition = \Tracker_Definition::get(self::$trackerId, false);
        $this->assertNotNull($definition, 'No tracker definition for the characterization tracker');
        $field = $definition->getFieldFromPermName($permName);
        $this->assertNotNull($field, 'Field under test not found in the tracker definition');

        $itemInfo = \TikiLib::lib('trk')->get_tracker_item($itemId);
        $factory = $definition->getFieldFactory();

        $handler = $factory->getHandler($field, $itemInfo);
        $this->assertNotNull($handler, 'Factory returned no handler for the field under test');

        $field = array_merge($field, $handler->getFieldData());
        $field['ins_id'] = $handler->getInsertId();
        $field['html_name'] = $handler->getHTMLFieldName();

        $handler = $factory->getHandler($field, $itemInfo);
        $this->assertNotNull($handler, 'Factory returned no handler for the prepared field');

        return $handler;
    }

    /**
     * Replace the {{ins}} placeholder in request-data keys with the real
     * insert id of the field under test.
     */
    protected function resolveRequestData(array $requestData, \Tracker\Field\AbstractItemField $handler): array
    {
        $resolved = [];
        foreach ($requestData as $key => $value) {
            $resolved[str_replace('{{ins}}', $handler->getInsertId(), $key)] = $value;
        }

        return $resolved;
    }

    /**
     * Replace the {{ins}} placeholder in render markers, so specs can assert
     * on the input name attribute without knowing the fieldId.
     */
    protected function resolveMarker(string $marker, \Tracker\Field\AbstractItemField $handler): string
    {
        return str_replace('{{ins}}', $handler->getInsertId(), $marker);
    }

    /**
     * assertSame() for scalars/arrays; \Closure expectations are called with
     * the actual value and do their own assertions.
     */
    protected function assertExpected($expected, $actual, string $what): void
    {
        if ($expected instanceof \Closure) {
            $expected($actual);
        } else {
            $this->assertSame($expected, $actual, $what);
        }
    }
}
