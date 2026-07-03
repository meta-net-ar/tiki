<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Handler class for Secret
 *
 * Letter key: ~SEC~
 *
 */
// phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase,PSR1.Classes.ClassDeclaration.MissingNamespace
class Tracker_Field_Secret extends \Tracker\Field\AbstractItemField implements \Tracker\Field\SynchronizableInterface
{
    // Sentinel written into the value channel by getFieldData() when the submitter explicitly
    // asks to clear the stored value, and recognised by handleSave(). It must travel through the
    // returned value (not instance state): getFieldData() and handleSave() run on *different*
    // handler instances during a save, so any property set in getFieldData() is gone by the time
    // handleSave() runs. The sentinel distinguishes "empty submission means leave unchanged" from
    // "empty submission means clear". It is converted to '' in handleSave() and never persisted.
    private const CLEAR_REQUEST = '__tiki_secret_clear_request__';

    public static function getManagedTypesInfo(): array
    {
        return [
            'SEC' => [
                'name'        => tr('Secret Field'),
                'description' => tr('Masked text field with show/hide toggle. Supports encryption via SSS keys.'),
                'help'        => 'Secret-Tracker-Field',
                'prefs'       => ['trackerfield_secret'],
                'tags'        => ['basic'],
                'default'     => 'y',
                'params'      => [
                    'max' => [
                        'name'         => tr('Maximum length'),
                        'description'  => tr('Maximum number of characters allowed. 0 means unlimited.'),
                        'filter'       => 'int',
                        'default'      => 0,
                        'legacy_index' => 0,
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = []): array
    {
        $insertId = $this->getInsertId();
        if (! empty($requestData[$insertId . '_clear'])) {
            // Clear wins over any submitted value; carry the intent through the value channel.
            return ['value' => self::CLEAR_REQUEST];
        }
        $cloneSource = (int) ($requestData[$insertId . '_clone_source'] ?? 0);
        $submitted = $requestData[$insertId] ?? '';
        if ($cloneSource > 0 && (! is_string($submitted) || $submitted === '')) {
            // Item duplication "copy" with the field left blank: the stored value is
            // copied at the database level after the new item exists (see
            // Controller::action_insert_item), so the plaintext (or ciphertext) never
            // travels through the rendered form. The field submits empty; the source
            // item id is surfaced to the template, which round-trips it through a
            // hidden input. A value typed into the duplicate form wins over the copy —
            // it falls through to the normal submission path below.
            return ['value' => '', 'cloneSource' => $cloneSource];
        }
        $value = $requestData[$insertId] ?? $this->getValue();
        return ['value' => $value];
    }

    public function renderInput($context = [])
    {
        \TikiLib::lib('header')->add_js_module('import "@tiki/tracker-fields/secret";');
        return $this->renderTemplate('trackerinput/secret.tpl', $context);
    }

    public function renderInnerOutput($context = [])
    {
        \TikiLib::lib('header')->add_js_module('import "@tiki/tracker-fields/secret";');
        return $this->renderTemplate('trackeroutput/secret.tpl', $context);
    }

    // Item duplication. The stored value is copied at the database level
    // (TrackerLib::copyItemFieldValueRaw) after the new item is created, so
    // it works for encrypted fields without the key in session and never
    // re-encrypts a ciphertext. Returning an empty value here keeps the normal
    // (encrypting) save path from carrying the secret.
    public function handleClone($strict = false)
    {
        return ['value' => ''];
    }

    /**
     * Mandatory-check helper for TrackerLib::check_field_values(). A Secret
     * field never renders its stored value back into the form, so an empty
     * submission can still be backed by a real value: the source item copied
     * at database level after insert during duplication (cloneSource), or this
     * item's own stored value preserved by handleSave()'s keep-on-blank guard.
     * A mandatory field is only "missing" when no backing value exists. An
     * explicit clear request empties the field and therefore always counts as
     * missing — a mandatory field may not be cleared.
     *
     * @param array    $f               field data as seen by check_field_values ('value', optional 'cloneSource')
     * @param int      $itemId          item being saved (0 on insert)
     * @param callable $getBackingValue fn (int $backingItemId): ?string — raw stored value lookup,
     *                                  expected to return '' when the caller may not view the item/field
     */
    public static function isMandatorySubmissionEmpty(array $f, int $itemId, callable $getBackingValue): bool
    {
        $value = $f['value'] ?? '';
        if ($value === self::CLEAR_REQUEST) {
            return true;
        }
        if (is_array($value) || strlen((string) $value) > 0) {
            return false;
        }
        $backingItemId = ! empty($f['cloneSource']) ? (int) $f['cloneSource'] : $itemId;
        if ($backingItemId <= 0) {
            return true;
        }
        return strlen((string) $getBackingValue($backingItemId)) == 0;
    }

    public function handleSave($value, $oldValue)
    {
        // Explicit clear: wipe this single item's value, regardless of what was submitted.
        if ($value === self::CLEAR_REQUEST) {
            return ['value' => ''];
        }
        if ($value === '' || $value === null) {
            if (! empty($this->getConfiguration('encryptionKeyId'))) {
                // Keep-on-blank for an encrypted field must skip the write entirely:
                // $oldValue arrives as the raw stored ciphertext (replace_item reads it
                // straight from the database) and modify_field() re-encrypts whatever is
                // returned here, so passing it through would double-encrypt on every save.
                // false makes replace_item leave the stored row untouched.
                return ['value' => false];
            }
            return ['value' => $oldValue === null ? null : (string) $oldValue];
        }
        $max = (int) $this->getOption('max');
        if ($max > 0) {
            $value = mb_substr($value, 0, $max);
        }
        return ['value' => $value];
    }

    // Secret fields must not appear in search indexes
    public function getDocumentPart(\Search_Type_Factory_Interface $typeFactory)
    {
        return [];
    }

    // Tracker sync transfers the raw stored value in plaintext — no masking applied.
    public function importRemote($value)
    {
        return $value;
    }

    public function exportRemote($value)
    {
        return $value;
    }

    public function importRemoteField(array $info, array $syncInfo)
    {
        return $info;
    }
}
