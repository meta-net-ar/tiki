<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiTests;

use PHPUnit\Framework\TestCase;
use Search_Type_Factory_Interface;

class TrackerFieldSecretTest extends TestCase
{
    private function makeField(?string $maxOption = null): \Tracker_Field_Secret
    {
        $mock = $this->getMockBuilder(\Tracker_Field_Secret::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOption', 'getValue'])
            ->getMock();

        $mock->method('getOption')->willReturnCallback(
            function ($key) use ($maxOption) {
                return ($key === 'max') ? $maxOption : null;
            }
        );

        return $mock;
    }

    private function makeFieldWithValue(?string $storedValue, string $insertId = 'tracker_123'): \Tracker_Field_Secret
    {
        $mock = $this->getMockBuilder(\Tracker_Field_Secret::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOption', 'getValue', 'getInsertId'])
            ->getMock();

        $mock->method('getValue')->willReturn($storedValue);
        $mock->method('getInsertId')->willReturn($insertId);

        return $mock;
    }

    // --- handleSave: keep-on-blank guard ---

    public function testHandleSaveEmptyStringPreservesOldValue(): void
    {
        $result = $this->makeField()->handleSave('', 'stored_secret');
        $this->assertSame(['value' => 'stored_secret'], $result);
    }

    public function testHandleSaveNullPreservesOldValue(): void
    {
        $result = $this->makeField()->handleSave(null, 'stored_secret');
        $this->assertSame(['value' => 'stored_secret'], $result);
    }

    public function testHandleSaveNewValueReplacesOldValue(): void
    {
        $result = $this->makeField()->handleSave('new_secret', 'old_secret');
        $this->assertSame(['value' => 'new_secret'], $result);
    }

    // --- handleSave: max-length enforcement ---

    public function testHandleSaveMaxLengthTruncatesValue(): void
    {
        $result = $this->makeField('5')->handleSave('123456789', 'old');
        $this->assertSame(['value' => '12345'], $result);
    }

    public function testHandleSaveMaxLengthZeroAllowsUnlimitedLength(): void
    {
        $long = str_repeat('x', 500);
        $result = $this->makeField('0')->handleSave($long, 'old');
        $this->assertSame(['value' => $long], $result);
    }

    public function testHandleSaveMaxLengthNullAllowsUnlimitedLength(): void
    {
        $long = str_repeat('y', 500);
        $result = $this->makeField(null)->handleSave($long, 'old');
        $this->assertSame(['value' => $long], $result);
    }

    public function testHandleSaveMaxLengthRespectsMbCharacters(): void
    {
        // 3 multibyte characters; max=2 should keep exactly 2
        $result = $this->makeField('2')->handleSave('héllo', 'old');
        $this->assertSame(['value' => 'hé'], $result);
    }

    // --- getFieldData ---

    public function testGetFieldDataReadsFromRequestData(): void
    {
        $field  = $this->makeFieldWithValue('stored', 'tracker_123');
        $result = $field->getFieldData(['tracker_123' => 'from_request']);
        $this->assertSame(['value' => 'from_request'], $result);
    }

    public function testGetFieldDataFallsBackToStoredValue(): void
    {
        $field  = $this->makeFieldWithValue('stored_secret', 'tracker_123');
        $result = $field->getFieldData([]);
        $this->assertSame(['value' => 'stored_secret'], $result);
    }

    public function testGetFieldDataFallsBackToNullStoredValue(): void
    {
        $field  = $this->makeFieldWithValue(null, 'tracker_123');
        $result = $field->getFieldData([]);
        $this->assertSame(['value' => null], $result);
    }

    public function testHandleSaveNullOldValuePreservesNull(): void
    {
        $result = $this->makeField()->handleSave('', null);
        $this->assertSame(['value' => null], $result);
    }

    public function testHandleSaveNonStringOldValueIsCastToString(): void
    {
        // $oldValue arriving as int (e.g. from a corrupt row) must be cast rather than returned raw
        $result = $this->makeField()->handleSave('', 42);
        $this->assertSame(['value' => '42'], $result);
    }

    // --- handleSave: encrypted field keep-on-blank skips the write ---

    // For an encrypted field, $oldValue reaches handleSave() as the raw stored ciphertext
    // (replace_item reads old values straight from the database) and modify_field()
    // re-encrypts whatever handleSave() returns, so passing the old value through would
    // double-encrypt it on every untouched save. 'value' => false skips the write instead.

    private function makeEncryptedField(string $insertId = 'tracker_123'): \Tracker_Field_Secret
    {
        $mock = $this->getMockBuilder(\Tracker_Field_Secret::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOption', 'getValue', 'getInsertId', 'getConfiguration'])
            ->getMock();

        $mock->method('getInsertId')->willReturn($insertId);
        $mock->method('getConfiguration')->willReturnCallback(
            function ($key, $default = false) {
                return ($key === 'encryptionKeyId') ? 7 : $default;
            }
        );

        return $mock;
    }

    public function testHandleSaveEncryptedEmptySubmissionSkipsWrite(): void
    {
        $result = $this->makeEncryptedField()->handleSave('', 'stored_ciphertext');
        $this->assertSame(['value' => false], $result);
    }

    public function testHandleSaveEncryptedNullSubmissionSkipsWrite(): void
    {
        $result = $this->makeEncryptedField()->handleSave(null, 'stored_ciphertext');
        $this->assertSame(['value' => false], $result);
    }

    public function testHandleSaveEncryptedNewValuePassesThrough(): void
    {
        // A real submission still travels the normal save path (modify_field encrypts it).
        $result = $this->makeEncryptedField()->handleSave('new_secret', 'stored_ciphertext');
        $this->assertSame(['value' => 'new_secret'], $result);
    }

    public function testHandleSaveEncryptedClearRequestStillWipes(): void
    {
        // An explicit clear must keep writing '' — only keep-on-blank skips the write.
        $field = $this->makeEncryptedField();
        $data  = $field->getFieldData(['tracker_123_clear' => '1']);
        $this->assertSame(['value' => ''], $field->handleSave($data['value'], 'stored_ciphertext'));
    }

    // --- clear stored value: explicit per-item clear ---

    // These exercise the real save pipeline: getFieldData() and handleSave() run on different
    // handler instances, so the clear intent must survive through getFieldData()'s returned value
    // rather than instance state. Each test feeds getFieldData()'s output into handleSave().

    public function testClearRequestWipesValueIgnoringSubmittedInput(): void
    {
        $field = $this->makeFieldWithValue('stored', 'tracker_123');
        // A new value is submitted alongside the clear flag; clear must win.
        $data = $field->getFieldData(['tracker_123' => 'new_secret', 'tracker_123_clear' => '1']);
        $this->assertSame(['value' => ''], $field->handleSave($data['value'], 'stored'));
    }

    public function testClearRequestWipesValueOnEmptySubmission(): void
    {
        $field = $this->makeFieldWithValue('stored', 'tracker_123');
        $data  = $field->getFieldData(['tracker_123_clear' => '1']);
        $this->assertSame(['value' => ''], $field->handleSave($data['value'], 'stored'));
    }

    public function testNoClearFlagPreservesKeepOnBlankBehavior(): void
    {
        $field = $this->makeFieldWithValue('stored', 'tracker_123');
        $data  = $field->getFieldData(['tracker_123' => '']);
        $this->assertSame(['value' => 'stored'], $field->handleSave($data['value'], 'stored'));
    }

    public function testEmptyClearFlagDoesNotTriggerClear(): void
    {
        $field = $this->makeFieldWithValue('stored', 'tracker_123');
        // An unchecked checkbox submits no '_clear' key (or an empty one) and must not clear.
        $data = $field->getFieldData(['tracker_123' => '', 'tracker_123_clear' => '']);
        $this->assertSame(['value' => 'stored'], $field->handleSave($data['value'], 'stored'));
    }

    // --- duplication: clone-source marker + handleClone (server-side copy) ---

    // The "Copy" duplication rule never renders the secret into the form. getFieldData()
    // surfaces only the source item id (carried by a hidden 'ins_<id>_clone_source' input)
    // so the controller can copy the stored value at the database level after insert.

    public function testGetFieldDataCloneSourceReturnsEmptyValueAndMarker(): void
    {
        $field  = $this->makeFieldWithValue('stored', 'tracker_123');
        $result = $field->getFieldData(['tracker_123_clone_source' => '42']);
        $this->assertSame(['value' => '', 'cloneSource' => 42], $result);
    }

    public function testGetFieldDataCloneSourceZeroFallsBackToStoredValue(): void
    {
        $field  = $this->makeFieldWithValue('stored', 'tracker_123');
        $result = $field->getFieldData(['tracker_123_clone_source' => '0']);
        $this->assertSame(['value' => 'stored'], $result);
    }

    public function testGetFieldDataTypedValueWinsOverCloneSource(): void
    {
        // A value typed into the duplicate form replaces the copy: the marker is
        // dropped so the controller's database-level copy is skipped too.
        $field  = $this->makeFieldWithValue('stored', 'tracker_123');
        $result = $field->getFieldData(['tracker_123' => 'user_typed', 'tracker_123_clone_source' => '42']);
        $this->assertSame(['value' => 'user_typed'], $result);
    }

    public function testGetFieldDataBlankSubmissionKeepsCloneSourceMarker(): void
    {
        // The duplicate form submits the field explicitly blank (not absent).
        $field  = $this->makeFieldWithValue('stored', 'tracker_123');
        $result = $field->getFieldData(['tracker_123' => '', 'tracker_123_clone_source' => '42']);
        $this->assertSame(['value' => '', 'cloneSource' => 42], $result);
    }

    public function testGetFieldDataClearTakesPrecedenceOverCloneSource(): void
    {
        $field = $this->makeFieldWithValue('stored', 'tracker_123');
        $data  = $field->getFieldData(['tracker_123_clear' => '1', 'tracker_123_clone_source' => '42']);
        $this->assertArrayNotHasKey('cloneSource', $data);
        // The clear sentinel still wipes the value through handleSave.
        $this->assertSame(['value' => ''], $field->handleSave($data['value'], 'stored'));
    }

    public function testHandleCloneReturnsEmptyValueSoSavePathDoesNotCarrySecret(): void
    {
        $this->assertSame(['value' => ''], $this->makeField()->handleClone());
        $this->assertSame(['value' => ''], $this->makeField()->handleClone(true));
    }

    // --- isMandatorySubmissionEmpty: required-field check for renders-empty submissions ---

    // TrackerLib::check_field_values() delegates the mandatory decision for SEC fields here,
    // because an empty submission may be backed by a real value the form never renders:
    // the duplication source item (copied at database level after insert) or this item's
    // own stored value (keep-on-blank). The callable is the backing-value lookup; TrackerLib
    // passes one that re-checks view permissions and returns '' when denied.

    private static function backingValueMustNotBeQueried(): callable
    {
        return function (): string {
            self::fail('Backing value lookup must not run for this case');
        };
    }

    public function testMandatoryCheckSubmittedValueIsNotEmpty(): void
    {
        $result = \Tracker_Field_Secret::isMandatorySubmissionEmpty(
            ['value' => 'typed_secret'],
            0,
            self::backingValueMustNotBeQueried()
        );
        $this->assertFalse($result);
    }

    public function testMandatoryCheckFreshCreateWithNoBackingIsEmpty(): void
    {
        $result = \Tracker_Field_Secret::isMandatorySubmissionEmpty(
            ['value' => ''],
            0,
            self::backingValueMustNotBeQueried()
        );
        $this->assertTrue($result);
    }

    public function testMandatoryCheckDuplicationWithStoredSourceValuePasses(): void
    {
        $result = \Tracker_Field_Secret::isMandatorySubmissionEmpty(
            ['value' => '', 'cloneSource' => 42],
            0,
            function (int $backingItemId): string {
                $this->assertSame(42, $backingItemId);
                return 'stored_in_source_item';
            }
        );
        $this->assertFalse($result);
    }

    public function testMandatoryCheckDuplicationWithEmptySourceValueIsEmpty(): void
    {
        // Also covers the permission-denied case: the TrackerLib callable returns ''
        // when the caller may not view the source item or field.
        $result = \Tracker_Field_Secret::isMandatorySubmissionEmpty(
            ['value' => '', 'cloneSource' => 42],
            0,
            fn (): string => ''
        );
        $this->assertTrue($result);
    }

    public function testMandatoryCheckEditWithStoredValuePasses(): void
    {
        // Keep-on-blank: editing an item whose secret is stored submits an empty input.
        $result = \Tracker_Field_Secret::isMandatorySubmissionEmpty(
            ['value' => ''],
            7,
            function (int $backingItemId): string {
                $this->assertSame(7, $backingItemId);
                return 'stored_on_this_item';
            }
        );
        $this->assertFalse($result);
    }

    public function testMandatoryCheckEditWithoutStoredValueIsEmpty(): void
    {
        $result = \Tracker_Field_Secret::isMandatorySubmissionEmpty(
            ['value' => ''],
            7,
            fn (): string => ''
        );
        $this->assertTrue($result);
    }

    public function testMandatoryCheckCloneSourceTakesPrecedenceOverItemId(): void
    {
        $queried = null;
        \Tracker_Field_Secret::isMandatorySubmissionEmpty(
            ['value' => '', 'cloneSource' => 42],
            7,
            function (int $backingItemId) use (&$queried): string {
                $queried = $backingItemId;
                return 'x';
            }
        );
        $this->assertSame(42, $queried);
    }

    public function testMandatoryCheckClearRequestIsAlwaysEmpty(): void
    {
        // A mandatory field may not be cleared: the clear sentinel (produced by
        // getFieldData) counts as missing even when a stored value backs the item.
        $field = $this->makeFieldWithValue('stored', 'tracker_123');
        $data  = $field->getFieldData(['tracker_123_clear' => '1']);
        $result = \Tracker_Field_Secret::isMandatorySubmissionEmpty(
            ['value' => $data['value']],
            7,
            self::backingValueMustNotBeQueried()
        );
        $this->assertTrue($result);
    }

    // --- importRemote / exportRemote / importRemoteField ---

    public function testImportRemotePassesThroughValue(): void
    {
        $result = $this->makeField()->importRemote('plain_secret');
        $this->assertSame('plain_secret', $result);
    }

    public function testImportRemotePassesThroughNull(): void
    {
        $result = $this->makeField()->importRemote(null);
        $this->assertNull($result);
    }

    public function testExportRemotePassesThroughValue(): void
    {
        $result = $this->makeField()->exportRemote('plain_secret');
        $this->assertSame('plain_secret', $result);
    }

    public function testImportRemoteFieldReturnsInfoUnchanged(): void
    {
        $info     = ['type' => 'SEC', 'name' => 'API Key'];
        $syncInfo = ['source' => 'remote'];
        $result   = $this->makeField()->importRemoteField($info, $syncInfo);
        $this->assertSame($info, $result);
    }

    // --- renderInnerOutput: delegates to Smarty template (integration-level) ---
    // renderInnerOutput() calls renderTemplate('trackeroutput/secret.tpl') which requires
    // a live Smarty/TikiLib environment; covered by browser/integration tests instead.

    // --- getDocumentPart: search exclusion ---

    public function testGetDocumentPartReturnsEmptyArray(): void
    {
        $typeFactory = $this->createMock(Search_Type_Factory_Interface::class);
        $result = $this->makeField()->getDocumentPart($typeFactory);
        $this->assertSame([], $result);
    }

    // --- getManagedTypesInfo: registration ---

    public function testManagedTypesInfoRegistersSecTypeCode(): void
    {
        $types = \Tracker_Field_Secret::getManagedTypesInfo();
        $this->assertArrayHasKey('SEC', $types, 'Field must register under type code "SEC"');
    }

    public function testManagedTypesInfoPrefKeyIsTrackerFieldSecret(): void
    {
        $types = \Tracker_Field_Secret::getManagedTypesInfo();
        $this->assertSame('trackerfield_secret', $types['SEC']['prefs'][0]);
    }

    public function testManagedTypesInfoHasMaxParam(): void
    {
        $types = \Tracker_Field_Secret::getManagedTypesInfo();
        $this->assertArrayHasKey('max', $types['SEC']['params']);
        $this->assertSame('int', $types['SEC']['params']['max']['filter']);
    }
}
