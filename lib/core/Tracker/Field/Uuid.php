<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Ramsey\Uuid\Uuid;

/**
 * Handler class for UUID field
 *
 * Letter key: ~uuid~
 * Generates and stores a universally unique identifier (UUID) for each tracker item.
 */
class Tracker_Field_Uuid extends Tracker_Field_AutoIncrement implements \Tracker\Field\ExportableInterface, \Tracker\Field\FilterableInterface
{
    public static function getManagedTypesInfo(): array
    {
        return [
            'uuid' => [
                'name'              => tr('UUID'),
                'description'       => tr('Store a universally unique identifier for each item'),
                'help'              => 'UUID Tracker Field',
                'prefs'             => ['trackerfield_uuid'],
                'tags'              => ['advanced'],
                'default'           => 'n',
                'params'            => [
                    'version' => [
                        'name'        => tr('UUID Version'),
                        'description' => tr('Select which UUID version to generate'),
                        'default'     => 4,
                        'filter' => 'int',
                        'options' => [
                            1 => 'v1',
                            2 => 'v2',
                            3 => 'v3',
                            4 => 'v4',
                            5 => 'v5',
                            6 => 'v6',
                            7 => 'v7',
                        ],
                        'legacy_index' => 0,
                    ],
                    'format' => [
                        'name'        => tr('Format'),
                        'description' => tr('With or without hyphens'),
                        'default'     => 'hyphenated',
                        'filter'      => 'alpha',
                        'options'     => [
                            'hyphenated'   => tr('With hyphens'),
                            'plain'        => tr('Without hyphens'),
                        ],
                        'legacy_index' => 1,
                    ],
                    'case' => [
                        'name'        => tr('Case'),
                        'description' => tr('Output lowercase or uppercase'),
                        'default'     => 'lower',
                        'filter'      => 'alpha',
                        'options'     => [
                            'lower' => tr('Lowercase'),
                            'upper' => tr('Uppercase'),
                        ],
                        'legacy_index' => 2,
                    ],
                    'readonly' => [
                        'name'        => tr('Read-only'),
                        'description' => tr('Prevent manual editing of the UUID'),
                        'default'     => 'y',
                        'filter'      => 'alpha',
                        'options'     => [
                            'y' => tr('Yes'),
                            'n' => tr('No'),
                        ],
                        'legacy_index' => 3,
                    ],
                    'update' => [
                        'name'        => tr('Backfill Existing Items'),
                        'description' => tr('Generate UUIDs for items created before this field was added'),
                        'default'     => 'n',
                        'filter'      => 'alpha',
                        'options'     => [
                            'y' => tr('Yes'),
                            'n' => tr('No'),
                        ],
                        'legacy_index' => 4,
                    ],
                ],
            ],
        ];
    }

    public function renderInput($context = []): string
    {
        return $this->renderTemplate('trackerinput/uuid.tpl', $context);
    }

    protected function renderInnerOutput($context = []): string
    {
        return htmlspecialchars((string)$this->getValue(), ENT_QUOTES, 'UTF-8');
    }

    public function handleSave($value, $oldValue): array
    {
        $isReadonly = $this->getOption('readonly') === 'y';

        // 1) If there *is* a submitted value, and override is allowed, keep it:
        if (! empty($value) && ! $isReadonly) {
            return ['value' => (string)$value];
        }

        // 2) If read-only and we’re editing an existing item, preserve old:
        if ($oldValue && $isReadonly) {
            return ['value' => $oldValue];
        }

        // 3) Otherwise (new item or override allowed), generate per version:
        if (is_null($oldValue) || ! $isReadonly) {
            $version = $this->getOption('version', 4);
            switch ($version) {
                case 1:
                    $uuidObj = Uuid::uuid1();
                    break;
                case 2:
                    $uuidObj = Uuid::uuid2(Uuid::NAMESPACE_DNS);
                    break;
                case 3:
                    $uuidObj = Uuid::uuid3(Uuid::NAMESPACE_DNS, 'example.com');
                    break;
                case 5:
                    $uuidObj = Uuid::uuid5(Uuid::NAMESPACE_DNS, 'example.com');
                    break;
                case 6:
                    $uuidObj = Uuid::uuid6();
                    break;
                case 7:
                    $uuidObj = Uuid::uuid7();
                    break;
                case 4:
                default:
                    $uuidObj = Uuid::uuid4();
                    break;
            }
            $uuid = $uuidObj->toString();

            if ($this->getOption('format', 'hyphenated') === 'plain') {
                $uuid = str_replace('-', '', $uuid);
            }

            if ($this->getOption('case', 'lower') === 'upper') {
                $uuid = strtoupper($uuid);
            } else {
                $uuid = strtolower($uuid);
            }

            return ['value' => $uuid];
        }

        return ['value' => $oldValue];
    }

    public function handleFieldSave($data)
    {
        if ($this->getOption('update') !== 'y') {
            return;
        }

        $trackerId = $this->getConfiguration('trackerId');
        $fieldId   = $this->getConfiguration('fieldId');

        $db = TikiDb::get();

        $itemIds = $db->table('tiki_tracker_items')
            ->fetchColumn(
                'itemId',
                ['trackerId' => $trackerId],
                -1,
                0,
                ['created' => 'ASC']
            );

        $existing = $db->table('tiki_tracker_item_fields')
            ->fetchMap(
                'itemId',
                'value',
                ['fieldId' => $fieldId]
            );

        foreach ($itemIds as $itemId) {
            if (! empty($existing[$itemId])) {
                continue;
            }
            $newUuid = $this->handleSave(null, null)['value'];
            TikiLib::lib('trk')->modify_field($itemId, $fieldId, $newUuid);
        }
    }

    public function getProvidedFields(): array
    {
        return [$this->getBaseKey()];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            $this->getBaseKey() => 'identifier',
        ];
    }

    public function isValid(array $ins_fields_data): bool
    {
        $insertId = $this->getInsertId();
        $value    = $ins_fields_data[$insertId] ?? $this->getValue();

        if ($value === '' || $value === null) {
            return true;
        }

        if (! Uuid::isValid($value)) {
            Feedback::error(tr('The provided value is not a valid UUID.'));
            return false;
        }

        // Check for uniqueness in the tracker
        $itemId    = $this->getItemId() ?: 0;
        $db = TikiDb::get();
        $existing = $db->table('tiki_tracker_item_fields')
            ->fetchMap(
                'itemId',
                'value',
                [
                    'fieldId'   => $this->getConfiguration('fieldId'),
                    'value'     => $value,
                ]
            );
        if (isset($existing[$itemId]) && $existing[$itemId] === $value) {
            return true;
        }
        if (! empty($existing)) {
            Feedback::error(tr('This UUID is already in use by another item in this tracker.'));
            return false;
        }

        return true;
    }
}
