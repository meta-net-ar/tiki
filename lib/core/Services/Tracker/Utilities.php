<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_Tracker_Utilities
{
    /**
     * Create a new tracker item from normalized item data.
     *
     * Use this when a service/controller already has a tracker definition and
     * needs to insert an item using status, raw fields, optional processed fields,
     * validation flags, bulk-import flags, sync flags, and deleted file metadata.
     *
     * @param Tracker_Definition $definition Tracker definition containing field metadata and tracker configuration.
     * @param array $item Item payload. Expected keys include status, fields, and optional processedFields, validate, bulk_import, skip_sync, deletedFiles.
     *
     * @return int|false The created tracker item ID, or false when validation or saving fails.
     */
    public function insertItem($definition, $item)
    {
        return $this->replaceItem($definition, 0, $item['status'], $item['fields'], $item['processedFields'] ?? [], [
            'validate' => $item['validate'] ?? true,
            'skip_categories' => false,
            'bulk_import' => $item['bulk_import'] ?? false,
            'skip_sync' => $item['skip_sync'] ?? false,
            'deleted_files' => $item['deletedFiles'] ?? [],
            'notify_watchers' => $item['notify_watchers'] ?? null,
        ]);
    }

    /**
     * Update an existing tracker item from normalized item data.
     *
     * Use this when saving edits to an existing item and you need the same
     * replacement pipeline as insertItem(), including validation, bulk import,
     * synchronization control, deleted files, and watcher notification options.
     *
     * @param Tracker_Definition $definition Tracker definition containing field metadata and tracker configuration.
     * @param array $item Item payload. Expected keys include itemId, status, fields, and optional processedFields, validate, bulk_import, skip_sync, deletedFiles, notify_watchers.
     *
     * @return int|false The updated tracker item ID/result, or false when validation or saving fails.
     */
    public function updateItem($definition, $item)
    {
        return $this->replaceItem($definition, $item['itemId'], $item['status'], $item['fields'], $item['processedFields'] ?? [], [
            'validate' => $item['validate'] ?? true,
            'skip_categories' => false,
            'bulk_import' => $item['bulk_import'] ?? false,
            'skip_sync' => $item['skip_sync'] ?? false,
            'deleted_files' => $item['deletedFiles'] ?? [],
            'notify_watchers' => $item['notify_watchers'] ?? null
        ]);
    }

    /**
     * Re-save an existing tracker item without changing submitted field values.
     *
     * Use this to trigger tracker item save-side effects such as recalculation,
     * reindexing, field handlers, or synchronization-safe refreshes while skipping
     * validation, category processing, and external sync.
     *
     * @param $itemId Tracker item ID to re-save.
     *
     * @return void
     */
    public function resaveItem($itemId): void
    {
        $tracker = TikiLib::lib('trk')->get_item_info($itemId);
        if (! $tracker) {
            return;
        }
        $definition = Tracker_Definition::get($tracker['trackerId']);
        if (! $definition) {
            return;
        }
        $this->replaceItem($definition, $itemId, null, [], [], [
            'validate' => false,
            'skip_categories' => true,
            'bulk_import' => true,
            'skip_sync' => true,
        ]);
    }

    /**
     * Validate a tracker item field map against tracker field rules.
     *
     * Use this before saving tracker item data when you need user-readable
     * validation messages for missing mandatory fields or invalid field values.
     *
     * @param Tracker_Definition $definition Tracker definition used for validation rules.
     * @param array $item Item data containing itemId and fields.
     * @param array $fields Optional initialized field data. When empty, fields are initialized from $item.
     *
     * @return array List of translated validation error messages. Empty array means valid.
     */
    public function validateItem($definition, $item, $fields = [])
    {
        $trackerId = $definition->getConfiguration('trackerId');
        if (! $fields) {
            $fields = $this->initializeItemFields($definition, $item['itemId'], $item['fields']);
        }

        $trklib = TikiLib::lib('trk');
        $categorizedFields = $definition->getCategorizedFields();
        $itemErrors = $trklib->check_field_values(['data' => $fields], $categorizedFields, $trackerId, $item['itemId'] ? $item['itemId'] : '');

        $errors = [];

        if (count($itemErrors['err_mandatory']) > 0) {
            $names = [];
            foreach ($itemErrors['err_mandatory'] as $f) {
                $names[] = $f['name'];
            }
            $errors[] = tr('The following mandatory fields are missing: %0', implode(', ', $names));
        }

        foreach ($itemErrors['err_value'] as $f) {
            if (! empty($f['errorMsg'])) {
                $errors[] = tr('Invalid value in %0: %1', $f['name'], $f['errorMsg']);
            } else {
                $errors[] = tr('Invalid value in %0', $f['name']);
            }
        }
        return $errors;
    }

    /**
     * Insert or update a tracker item using Tiki's tracker library.
     *
     * Use this internal method as the common persistence pipeline for item creation,
     * update, and re-save operations. It initializes field values, merges processed
     * field metadata, optionally validates, optionally removes categorized fields,
     * then delegates to trklib->replace_item().
     *
     * @param Tracker_Definition $definition Tracker definition containing fields and tracker configuration.
     * @param int|string $itemId Existing item ID, or 0 to create a new item.
     * @param string|null $status Tracker item status.
     * @param array $fieldMap Submitted field values keyed by field ID, ins_FIELDID, or permanent name.
     * @param array $processedFields Field data already processed by field handlers.
     * @param array $options Save options: validate, skip_categories, bulk_import, skip_sync, deleted_files, notify_watchers.
     *
     * @return int|false Tracker item ID/result, or false on validation/save failure.
     */
    private function replaceItem($definition, $itemId, $status, $fieldMap, $processedFields, array $options)
    {
        $trackerId = $definition->getConfiguration('trackerId');
        $fields = $this->initializeItemFields($definition, $itemId, $fieldMap);

        foreach ($processedFields as $field) {
            if (isset($fields[$field['fieldId']])) {
                foreach ($field as $key => $val) {
                    if (! isset($fields[$field['fieldId']][$key])) {
                        $fields[$field['fieldId']][$key] = $val;
                    }
                }
            }
        }

        $trklib = TikiLib::lib('trk');

        if ($options['validate']) {
            $errors = $this->validateItem($definition, ['itemId' => $itemId, 'fields' => $fieldMap], $fields);
        }

        if ($options['skip_categories']) {
            $categorizedFields = $definition->getCategorizedFields();
            foreach ($categorizedFields as $fieldId) {
                unset($fields[$fieldId]);
            }
        }
        if (! $options['validate'] || count($errors) == 0) {
            $newItem = $trklib->replace_item($trackerId, $itemId, ['data' => $fields], $status, 0, $options['bulk_import'], $options['skip_sync'], $options['deleted_files'], $options['notify_watchers']);
            return $newItem;
        }

        foreach ($errors as $err) {
            Feedback::error($err);
        }
        return false;
    }

    /**
     * Convert a submitted field map into the full tracker field data structure.
     *
     * Use this before validation or saving to normalize user input into the format
     * expected by trklib. It supports field IDs, legacy ins_FIELDID keys, and
     * permanent field names, then fills missing fields with existing item values.
     *
     * @param Tracker_Definition $definition Tracker definition used to resolve fields.
     * @param int|string $itemId Existing item ID, or 0 for a new item.
     * @param array $fieldMap Submitted field values.
     *
     * @return array Field data keyed by field ID.
     */
    private function initializeItemFields($definition, $itemId, $fieldMap)
    {
        $fields = [];
        foreach ($fieldMap as $key => $value) {
            if (preg_match('/ins_/', $key)) { //make compatible with the 'ins_' keys
                $id = (int)str_replace('ins_', '', $key);
                if ($field = $definition->getField($id)) {
                    $field['value'] = $value;
                    $fields[$field['fieldId']] = $field;
                }
            } elseif ($field = $definition->getField($key)) {
                $field['value'] = $value;
                $fields[$field['fieldId']] = $field;
            } elseif ($field = $definition->getFieldFromPermName($key)) {
                $field['value'] = $value;
                $fields[$field['fieldId']] = $field;
            }
        }

        if ($itemId) {
            $item = $this->getItem($definition->getConfiguration('trackerId'), $itemId);
            $initialData = new JitFilter($item['fields']);
        } else {
            $initialData = new JitFilter([]);
        }

        // Add unspecified fields for the validation to work correctly
        foreach ($definition->getFields() as $field) {
            $fieldId = $field['fieldId'];
            if (! isset($fields[$fieldId])) {
                $permName = $field['permName'];
                $field['value'] = $initialData->$permName->none();
                $fields[$fieldId] = $field;
            }
        }
        return $fields;
    }

    /**
     * Create a new tracker field.
     *
     * Use this when adding a field to an existing tracker from service code.
     * If it is the first field in the tracker, it is automatically configured
     * as main, visible in tables, and mandatory by default.
     *
     * @param array $data Field definition data including trackerId, name, type, description, permName, and optional field properties.
     *
     */
    public function createField(array $data)
    {
        $definition = Tracker_Definition::get($data['trackerId']);

        $isFirst = 0 === count($definition->getFields());

        $trklib = TikiLib::lib('trk');
        return $trklib->replace_tracker_field(
            $data['trackerId'],
            0,
            $data['name'],
            $data['type'],
            ($isFirst ? 'y' : 'n'),
            'n',
            ($isFirst ? 'y' : 'n'),
            'y',
            $data['isHidden'] ?? 'n',
            isset($data['isMandatory']) ? ($data['isMandatory'] ? 'y' : 'n') : ($isFirst ? 'y' : 'n'),
            $trklib->get_last_position($data['trackerId']) + 10,
            $data['options'] ?? '',
            $data['description'],
            '',
            null,
            '',
            null,
            null,
            $data['descriptionIsParsed'] ? 'y' : 'n',
            '',
            '',
            '',
            $data['permName'],
            null,
            null,
            false,
            $data['visibleInViewMode'] ?? 'y',
            $data['visibleInEditMode'] ?? 'y',
            $data['visibleInHistoryMode'] ?? 'y',
            $data['excludeFromTrackerItemLastModificationDate'] ?? 'n',
        );
    }

    /**
     * Update an existing tracker field or import a field with a preserved ID.
     *
     * Use this to modify tracker field metadata while keeping unspecified
     * properties from the current field definition. It also supports importing
     * fields where the field ID may not yet exist locally.
     *
     * @param int|string $trackerId Tracker ID containing the field.
     * @param int|string $fieldId Field ID to update, or 0 when creating/importing a new field.
     * @param array $properties Field properties to override.
     *
     * @return void
     */
    public function updateField($trackerId, $fieldId, array $properties)
    {
        $definition = Tracker_Definition::get($trackerId);

        //$fieldId = 0 when is a new field, e.g. when importing tracker structure
        try {
            $field = ($fieldId === 0) ? [] : $definition->getField($fieldId);
        } catch (RuntimeException $e) {
            if ($fieldId > 0) {
                // importing tracker field keeping the fieldId
                $field = [];
            } else {
                throw $e;
            }
        }
        $trklib = TikiLib::lib('trk');
        $trklib->replace_tracker_field(
            $trackerId,
            $fieldId,
            $properties['name'] ?? $field['name'] ?? null,
            $properties['type'] ?? $field['type'] ?? null,
            $properties['isMain'] ?? $field['isMain'] ?? null,
            $properties['isSearchable'] ?? $field['isSearchable'] ?? null,
            $properties['isTblVisible'] ?? $field['isTblVisible'] ?? null,
            $properties['isPublic'] ?? $field['isPublic'] ?? null,
            $properties['isHidden'] ?? $field['isHidden'] ?? null,
            $properties['isMandatory'] ?? $field['isMandatory'] ?? null,
            $properties['position'] ?? $field['position'] ?? null,
            $properties['options'] ?? $field['options'] ?? null,
            $properties['description'] ?? $field['description'] ?? null,
            $properties['isMultilingual'] ?? $field['isMultilingual'] ?? null,
            '', // itemChoices
            $properties['errorMsg'] ?? $field['errorMsg'] ?? null,
            $properties['visibleBy'] ?? $field['visibleBy'] ?? null,
            $properties['editableBy'] ?? $field['editableBy'] ?? null,
            $properties['descriptionIsParsed'] ?? $field['descriptionIsParsed'] ?? null,
            $properties['validation'] ?? $field['validation'] ?? null,
            $properties['validationParam'] ?? $field['validationParam'] ?? null,
            $properties['validationMessage'] ?? $field['validationMessage'] ?? null,
            $properties['permName'] ?? $field['permName'] ?? null,
            $properties['rules'] ?? $field['rules'] ?? null,
            $properties['encryptionKeyId'] ?? $field['encryptionKeyId'] ?? null,
            $properties['excludeFromNotification'] ?? $field['excludeFromNotification'] ?? null,
            $properties['visibleInViewMode'] ?? $field['visibleInViewMode'] ?? null,
            $properties['visibleInEditMode'] ?? $field['visibleInEditMode'] ?? null,
            $properties['visibleInHistoryMode'] ?? $field['visibleInHistoryMode'] ?? null,
            $properties['excludeFromTrackerItemLastModificationDate'] ?? $field['excludeFromTrackerItemLastModificationDate'] ?? 'n'
        );
    }

    /**
     * Fetch tracker items matching conditions and return selected fields by permanent name.
     *
     * Use this for service-level list/export operations where item IDs, status,
     * and a normalized fields array are needed. Supports status filtering,
     * item ID filtering, modified-since filtering, pagination, and selected fields.
     *
     * @param array $conditions Query conditions. Must include trackerId. Optional: status, modifiedSince, itemId.
     * @param int $maxRecords Maximum records to fetch. -1 means all.
     * @param int $offset Offset for pagination.
     * @param array $fields Optional list of field permanent names to include.
     *
     * @return array List of items with itemId, status, and fields.
     */
    public function getItems(array $conditions, $maxRecords = -1, $offset = -1, $fields = [])
    {
        $keyMap = [];
        $definition = Tracker_Definition::get($conditions['trackerId']);
        foreach ($definition->getFields() as $field) {
            if (! empty($field['permName']) && (empty($fields) || in_array($field['permName'], $fields))) {
                $keyMap[$field['fieldId']] = $field['permName'];
            }
        }

        $table = TikiDb::get()->table('tiki_tracker_items');

        if (! empty($conditions['status'])) {
            $conditions['status'] = $table->in(str_split($conditions['status'], 1));
        } else {
            unset($conditions['status']);
        }

        if (! empty($conditions['modifiedSince'])) {
            $conditions['lastModif'] = $table->greaterThan($conditions['modifiedSince']);
        }

        if (! empty($conditions['itemId'])) {
            $conditions['itemId'] = $table->in((array) $conditions['itemId']);
        }

        unset($conditions['modifiedSince']);

        $items = $table->fetchAll(['itemId', 'status'], $conditions, $maxRecords, $offset);

        foreach ($items as & $item) {
            $item['fields'] = $this->getItemFields($item['itemId'], $keyMap);
        }

        return $items;
    }

    /**
     * Fetch a single tracker item by tracker ID and item ID.
     *
     * Use this when a complete normalized item structure is needed for an
     * existing tracker item, including its field values keyed by permanent name.
     *
     * @param int $trackerId Tracker ID.
     * @param int $itemId Tracker item ID.
     *
     * @return array|false Item data, or false when not found.
     */
    public function getItem($trackerId, $itemId)
    {
        $items = $this->getItems(
            [
                'trackerId' => $trackerId,
                'itemId' => $itemId,
            ],
            1,
            0
        );
        $item = reset($items);

        return $item;
    }

    /**
     * Build the display title for a tracker item from its main fields.
     *
     * Use this when a tracker item needs a human-readable label based on the
     * fields marked as main in the tracker definition.
     *
     * @param Tracker_Definition $definition Tracker definition.
     * @param array $item Item data containing fields keyed by permanent name.
     *
     * @return string Concatenated title parts.
     */
    public function getTitle($definition, $item)
    {
        $parts = [];

        foreach ($definition->getFields() as $field) {
            if ($field['isMain'] == 'y') {
                $permName = $field['permName'];
                $parts[] = $item['fields'][$permName];
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Process raw tracker field values into rendered/normalized values.
     *
     * Use this when item values should be converted through each field handler,
     * for example before display, export, or downstream service usage.
     *
     * @param Tracker_Definition $definition Tracker definition.
     * @param array $item Item data containing fields keyed by permanent name.
     *
     * @return array Item data with processed field values.
     */
    public function processValues($definition, $item)
    {
        $trklib = TikiLib::lib('trk');

        foreach ($item['fields'] as $permName => $rawValue) {
            $field = $definition->getFieldFromPermName($permName);
            $field['value'] = $rawValue;
            $item['fields'][$permName] = $trklib->field_render_value(
                [
                    'field' => $field,
                    'process' => 'y',
                ]
            );
        }

        return $item;
    }

    /**
     * Fetch field values for one tracker item using a field ID to a permanent-name map.
     *
     * Use this internal helper when building compact item payloads where only
     * selected fields are needed and values should come from tracker field handlers.
     *
     * @param int|string $itemId Tracker item ID.
     * @param array $keyMap Map of fieldId => permanent name.
     *
     * @return array Field values keyed by permanent name.
     */
    private function getItemFields($itemId, $keyMap)
    {
        $trklib = TikiLib::lib('trk');
        $item = $trklib->get_tracker_item($itemId);

        $out = [];
        foreach ($keyMap as $fieldId => $name) {
            $info = $trklib->get_field_info($fieldId);
            $handler = $trklib->get_field_handler($info, $item);
            $data = $handler->getFieldData();
            $out[$name] = $data['value'] ?? null; // some handlers like Header don't return values
        }

        return $out;
    }

    /**
     * Create a new tracker.
     *
     * Use this when service code needs to create a tracker shell with name,
     * description, and description parsing configuration.
     *
     * @param array $data Tracker data including name, description, and descriptionIsParsed.
     *
     * @return int|mixed New tracker ID or library result.
     */
    public function createTracker($data)
    {
        $trklib = TikiLib::lib('trk');
        return $trklib->replace_tracker(
            0,
            $data['name'],
            $data['description'],
            [],
            $data['descriptionIsParsed']
        );
    }

    /**
     * Update tracker metadata and options.
     *
     * Use this when changing a tracker's name, description, parsed-description
     * flag, or other tracker option values.
     *
     * @param int $trackerId Tracker ID to update.
     * @param array $data Tracker data including name, description, descriptionIsParsed, plus option values.
     *
     * @return mixed Result returned by trklib->replace_tracker().
     */
    public function updateTracker($trackerId, $data)
    {
        $trklib = TikiLib::lib('trk');
        $name = $data['name'];
        $description = $data['description'];
        $descriptionIsParsed = $data['descriptionIsParsed'];

        unset($data['name']);
        unset($data['description']);
        unset($data['descriptionIsParsed']);

        return $trklib->replace_tracker($trackerId, $name, $description, $data, $descriptionIsParsed);
    }

    /**
     * Delete all items from a tracker and reset import-sync metadata when present.
     *
     * Use this for tracker clearing operations where the tracker definition remains,
     * but all contained items should be removed.
     *
     * @param int $trackerId Tracker ID to clear.
     *
     * @return int Number of successfully removed items.
     */
    public function clearTracker($trackerId)
    {
        $table = TikiDb::get()->table('tiki_tracker_items');

        $items = $table->fetchColumn(
            'itemId',
            ['trackerId' => $trackerId,]
        );
        $success = 0;
        foreach ($items as $itemId) {
            $result = $this->removeItem($itemId);
            if ($result && $result->numRows()) {
                $success++;
            }
        }

        $trklib = TikiLib::lib('trk');
        $options = $trklib->get_tracker_options($trackerId);
        if (! empty($options['tabularSyncLastImport'])) {
            $trklib->replace_tracker_option($trackerId, 'tabularSyncLastImport', null);
        }

        return $success;
    }

    /**
     * Import one tracker field from filtered input.
     *
     * Use this during tracker structure imports to create or update a field,
     * optionally preserving the original field ID and position. Required field
     * type preferences are enabled automatically when needed.
     *
     * @param int $trackerId Destination tracker ID.
     * @param JitFilter $field Filtered imported field data.
     * @param bool $preserve Whether to preserve the imported field ID.
     * @param int $lastposition Whether to append to the end when set to 1.
     *
     * @return void
     */
    public function importField($trackerId, $field, $preserve, $lastposition = 0)
    {
        if ($lastposition == 1 || ! $field->position->int()) {
            // No position parameter was provided, or user requested that new fields are added to the bottom
            $trklib = TikiLib::lib('trk');
            $position = $trklib->get_last_position($trackerId) + 10;
        } else {
            $position = $field->position->int();
        }

        if (! $preserve) {
            $fieldId = 0;
        } else {
            $fieldId = $field->fieldId->int();
        }

        $description = $field->descriptionStaticText->text();
        if (! $description) {
            $description = $field->description->text();
        }

        $data = [
                'name' => $field->name->text(),
                'permName' => $field->permName->word(),
                'type' => $field->type->word(),
                'position' => $position,
                'options' => $field->options->none(),

                'isMain' => $field->isMain->alpha(),
                'isSearchable' => $field->isSearchable->alpha(),
                'isTblVisible' => $field->isTblVisible->alpha(),
                'isPublic' => $field->isPublic->alpha(),
                'isHidden' => $field->isHidden->alpha(),
                'isMandatory' => $field->isMandatory->alpha(),
                'isMultilingual' => $field->isMultilingual->alpha(),

                'description' => $description,
                'descriptionIsParsed' => $field->descriptionIsParsed->alpha(),

                'validation' => $field->validation->word(),
                'validationParam' => $field->validationParam->none(),
                'validationMessage' => $field->validationMessage->text(),

                'itemChoices' => '',

                'editableBy' => $field->editableBy->groupname(),
                'visibleBy' => $field->visibleBy->groupname(),
                'errorMsg' => $field->errorMsg->text(),

                'rules' => $field->rules->text(),

                'excludeFromTrackerItemLastModificationDate' => $field->excludeFromTrackerItemLastModificationDate->alpha() ?: 'n',

                'visibleInViewMode' => $field->visibleInViewMode->alpha(),
                'visibleInEditMode' => $field->visibleInEditMode->alpha(),
                'visibleInHistoryMode' => $field->visibleInHistoryMode->alpha(),
        ];

        // enable prefs for imported fields if required
        $completeList = Tracker_Field_Factory::getFieldTypes();

        if (! $this->isEnabled($completeList[$data['type']])) {
            foreach ($completeList[$data['type']]['prefs'] as $pref) {
                TikiLib::lib('tiki')->set_preference($pref, 'y');
            }
        }

        $this->updateField($trackerId, $fieldId, $data);
    }

    /**
     * Export a tracker field definition as INI-style text.
     *
     * Use this when serializing tracker field configuration for structure export
     * or migration tooling.
     *
     * @param array $field Tracker field data.
     *
     * @return string INI-style exported field definition.
     */
    public function exportField($field)
    {
        return <<<EXPORT
[FIELD{$field['fieldId']}]
fieldId = {$field['fieldId']}
name = {$field['name']}
permName = {$field['permName']}
position = {$field['position']}
type = {$field['type']}
options = {$field['options']}
isMain = {$field['isMain']}
isTblVisible = {$field['isTblVisible']}
isSearchable = {$field['isSearchable']}
isPublic = {$field['isPublic']}
isHidden = {$field['isHidden']}
isMandatory = {$field['isMandatory']}
description = {$field['description']}
descriptionIsParsed = {$field['descriptionIsParsed']}
rules = {$field['rules']}
encryptionKeyId = {$field['encryptionKeyId']}
excludeFromNotification = {$field['excludeFromNotification']}
excludeFromTrackerItemLastModificationDate = {$field['excludeFromTrackerItemLastModificationDate']}
visibleInViewMode = {$field['visibleInViewMode']}
visibleInEditMode = {$field['visibleInEditMode']}
visibleInHistoryMode = {$field['visibleInHistoryMode']}
isMultilingual = {$field['isMultilingual']}

EXPORT;
    }

    /**
     * Serialize field option input according to a tracker field type definition.
     *
     * Use this when converting option form input into the compact serialized
     * string stored on tracker field definitions.
     *
     * @param array|JitFilter $input Option input values.
     * @param string|array $typeInfo Field type code or field type metadata.
     *
     * @return string Serialized tracker options.
     */
    public function buildOptions($input, $typeInfo)
    {
        if (is_string($typeInfo)) {
            $types = $this->getFieldTypes();
            $typeInfo = $types[$typeInfo];
        }

        if (is_array($input)) {
            $input = new JitFilter($input);
        }

        $options = Tracker_Options::fromInput($input, $typeInfo);
        return $options->serialize();
    }

    /**
     * Parse serialized tracker field options into structured parameters.
     *
     * Use this when displaying or editing stored tracker field options.
     *
     * @param string $raw      Serialized option string.
     * @param array  $typeInfo Field type metadata.
     *
     * @return array Parsed option parameters.
     */
    public function parseOptions($raw, $typeInfo)
    {
        $options = Tracker_Options::fromSerialized($raw, $typeInfo);

        return $options->getAllParameters();
    }

    /**
     * List tracker field types whose required preferences are disabled.
     *
     * Use this to show unavailable field types or warnings during tracker
     * administration/import.
     *
     * @return array Disabled field types keyed by type code.
     */
    public function getFieldTypesDisabled(): array
    {
        $completeList = Tracker_Field_Factory::getFieldTypes();

        $list = [];

        foreach ($completeList as $code => $info) {
            if ($this->isEnabled($info) == false) {
                $list[$code] = $info;
            }
        }

        return $list;
    }

    /**
     * List enabled tracker field types, optionally filtered by type code.
     *
     * Use this to populate field type selectors with only field types whose
     * required preferences are active.
     *
     * @param array $filter Optional list of field type codes to include.
     *
     * @return array Enabled field types keyed by type code.
     */
    public function getFieldTypes($filter = []): array
    {
        $completeList = Tracker_Field_Factory::getFieldTypes();

        if (! empty($filter)) {
            $completeList = array_intersect_key($completeList, array_flip($filter));
        }

        $list = [];

        foreach ($completeList as $code => $info) {
            if ($this->isEnabled($info)) {
                $list[$code] = $info;
            }
        }

        return $list;
    }

    /**
     * Check whether all preferences required by a field type are enabled.
     *
     * Use this internal helper to decide whether a tracker field type can be
     * created or selected in the current site configuration.
     *
     * @param array $info Field type metadata containing a prefs list.
     *
     * @return bool True when all required preferences are enabled.
     */
    private function isEnabled($info): bool
    {
        global $prefs;

        foreach ($info['prefs'] as $p) {
            if ($prefs[$p] != 'y') {
                return false;
            }
        }
        return true;
    }

    /**
     * Resolve multiple tracker field IDs to field definitions.
     *
     * Use this when an operation receives field IDs and needs validated field
     * metadata before continuing.
     *
     * @param Tracker_Definition $definition Tracker definition.
     * @param array $fieldIds   Field IDs to resolve.
     *
     * @return array Field definitions.
     * @throws Services_Exception When any field does not exist.
     */
    public function getFieldsFromIds($definition, $fieldIds): array
    {
        $fields = [];
        foreach ($fieldIds as $fieldId) {
            $field = $definition->getField($fieldId);

            if (! $field) {
                throw new Services_Exception(tr('Field %0 does not exist in tracker', $fieldId), 404);
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Remove a tracker item.
     *
     * Use this for deleting one tracker item through the tracker library while
     * enabling the library's cleanup behavior.
     *
     * @param int $itemId Tracker item ID.
     *
     * @return mixed Result returned by trklib->remove_tracker_item().
     */
    public function removeItem($itemId)
    {
        $trklib = TikiLib::lib('trk');
        return $trklib->remove_tracker_item($itemId, true);
    }

    /**
     * Remove a tracker item and update references that point to it.
     *
     * Use this when deleting an item that may be referenced by other tracker
     * items. Field handlers get a chance to handle deletion before references
     * are replaced and the item is removed inside a transaction.
     *
     * @param Tracker_Definition $definition  Tracker definition for the item being removed.
     * @param Tracker_Item $itemObject  Item object to remove.
     * @param array $uncascaded  Reference metadata containing itemIds and fieldIds.
     * @param mixed $replacement Replacement value for references.
     *
     * @return void
     */
    public function removeItemAndReferences($definition, $itemObject, $uncascaded, $replacement): void
    {
        $tx = TikiDb::get()->begin();

        $itemData = $itemObject->getData();
        foreach ($definition->getFields() as $field) {
            $handler = $definition->getFieldFactory()->getHandler($field, $itemData);
            if (method_exists($handler, 'handleDelete')) {
                $handler->handleDelete();
            }
        }

        TikiLib::lib('trk')->replaceItemReferences($replacement, $uncascaded['itemIds'], $uncascaded['fieldIds']);

        $this->removeItem($itemObject->getId());

        $tx->commit();
    }

    /**
     * Remove an entire tracker.
     *
     * Use this for tracker deletion operations where the tracker itself and its
     * associated configuration/items should be removed by the tracker library.
     *
     * @param int $trackerId Tracker ID to remove.
     *
     * @return void
     */
    public function removeTracker($trackerId): void
    {
        $trklib = TikiLib::lib('trk');
        $trklib->remove_tracker($trackerId);
    }

    /**
     * Duplicate a tracker, optionally copying categories and object permissions.
     *
     * Use this when creating a new tracker based on an existing tracker structure,
     * with optional category assignment and permission cloning.
     *
     * @param int $trackerId Source tracker ID.
     * @param string $name Name for the duplicated tracker.
     * @param int|bool $duplicateCategories Whether to copy tracker categories.
     * @param int|bool $duplicatePermissions Whether to copy tracker object permissions.
     *
     * @return int New tracker ID.
     */
    public function duplicateTracker($trackerId, $name, $duplicateCategories, $duplicatePermissions)
    {
        $trklib = TikiLib::lib('trk');
        $newTrackerId = $trklib->duplicate_tracker($trackerId, $name, '', 'n');

        if ($duplicateCategories) {
            $categlib = TikiLib::lib('categ');
            $cats = $categlib->get_object_categories('tracker', $trackerId);
            $catObjectId = $categlib->add_categorized_object('tracker', $newTrackerId, '', $name, "tiki-view_tracker.php?trackerId=$newTrackerId");
            foreach ($cats as $cat) {
                $categlib->categorize($catObjectId, $cat);
            }
        }

        if ($duplicatePermissions) {
            $userlib = TikiLib::lib('user');
            $userlib->copy_object_permissions($trackerId, $newTrackerId, 'tracker');
        }

        return $newTrackerId;
    }

    /**
     * Clone a tracker item and optionally cascade cloning to configured child items.
     *
     * Use this when duplicating one tracker item while allowing field handlers to
     * transform cloned values and cascading duplication to child items whose item
     * link field allows duplicateCascade.
     *
     * @param Tracker_Definition $definition Tracker definition of the source item.
     * @param array $itemData Source item data prepared for insertion.
     * @param int $itemId Source item ID.
     * @param bool $strict Whether field handlers should clone in strict mode.
     *
     * @return Tracker_Item|bool return new tracker item object, or false when cloning fails.
     * @throws Exception
     */
    public function cloneItem($definition, $itemData, $itemId, $strict = false)
    {
        $transaction = TikiLib::lib('tiki')->begin();

        foreach ($definition->getFields() as $field) {
            $handler = $definition->getFieldFactory()->getHandler($field, $itemData);
            if (method_exists($handler, 'handleClone')) {
                $newData = $handler->handleClone($strict);
                $itemData['fields'][$field['permName']] = $newData['value'];
            }
        }

        $id = $this->insertItem($definition, $itemData);
        if ($id === false) {
            $transaction->commit(); // there is no rollback
            return false;
        }
        $insertIds = [$id];

        $itemObject = Tracker_Item::fromId($id);

        if ($this->cascadeChildItems($itemId, $id, $strict, $insertIds)) {
            foreach ($insertIds as $insertedId) {
                $this->removeItem($insertedId);
            }
            $transaction->commit(); // there is no rollback
            return false;
        }

        $transaction->commit();

        return $itemObject;
    }

    public function cascadeChildItems(int $sourceItemId, int $newParentId, bool $strict = false, array &$insertIds = []): bool
    {
        foreach (TikiLib::lib('trk')->get_child_items($sourceItemId) as $info) {
            $field = TikiLib::lib('trk')->get_tracker_field($info['field']);
            $options = Tracker_Options::fromSerialized($field['options'], Tracker_Field_Factory::getFieldInfo($field['type']));
            if (! $options->getParam('duplicateCascade')) {
                continue;
            }

            $childItem = Tracker_Item::fromId($info['itemId']);
            if (! $childItem->canView()) {
                continue;
            }

            $childItem->asNew();
            $data = $childItem->getData();
            $data['fields'][$info['field']] = $newParentId;

            $childDefinition = $childItem->getDefinition();
            foreach ($childDefinition->getFields() as $childField) {
                $handler = $childDefinition->getFieldFactory()->getHandler($childField, $data);
                if (method_exists($handler, 'handleClone')) {
                    $newData = $handler->handleClone($strict);
                    $data['fields'][$childField['permName']] = $newData['value'];
                }
            }

            $new = $this->insertItem($childDefinition, $data);
            if ($new === false) {
                return true;
            }
            $insertIds[] = $new;
        }
        return false;
    }

    /**
     * Convert an amount from a supplied currency to the default currency.
     *
     * Use this as a calculation helper when tracker data stores an amount,
     * currency, and date and needs normalization to the configured default
     * currency based on exchange rates.
     *
     * @param array $data Conversion data with amount, currency, and date.
     *
     * @return float|int Converted amount in the default currency.
     */
    public static function convertToDefaultCurrency($data)
    {
        $trk = TikiLib::lib('trk');
        $rates = $trk->exchange_rates($data['date']);

        $defaultCurrency = array_search(1, $rates);
        if (empty($defaultCurrency)) {
            $defaultCurrency = 'USD';
        }

        $currency = new Math_Formula_Currency($data['amount'], $data['currency'], $rates);
        return $currency->convertTo($defaultCurrency)->getAmount();
    }

    /**
     * A form cannot submit a literal tab, so that option arrives as the word "tab"
     * (or as an escaped "\t" from forms that have not been updated yet).
     *
     * @param string $separator
     * @return string
     */
    public static function normalizeCsvSeparator(string $separator): string
    {
        if ($separator === 'tab' || $separator === '\t') {
            return "\t";
        }

        return $separator !== '' ? $separator : ',';
    }

    /**
     * Convert an uploaded TSV file into a temporary CSV file.
     *
     * Use this before CSV import handling when the uploaded source file is
     * tab-separated. The method preserves empty trailing fields and escapes
     * CSV-sensitive values.
     *
     * @param string $filename Key in the $_FILES array.
     *
     * @return void
     */
    public static function parseTsvContentToCsv($filename)
    {
        $fileContent = file_get_contents($_FILES[$filename]['tmp_name']);
        $rows = explode("\n", $fileContent);
        $csvRows = [];

        foreach ($rows as $row) {
            if (trim($row) === '') {
                continue;
            }
            // since field may have comma or quotes or tab in it
            $fields = str_getcsv($row, "\t", escape: TikiLib::TIKI_GLOBAL_CSV_ESCAPE_CHAR);
            if (str_ends_with($row, "\t")) {
                $fields[] = '';
            }
            $pFields = []; // escape processed fields

            foreach ($fields as $field) {
                $field = $field ?? '';
                $hasCommaOrQuoteOrTab = (
                    strpos($field, ',') !== false ||
                    strpos($field, '"') !== false ||
                    strpos($field, "\t") !== false
                );

                if ($hasCommaOrQuoteOrTab) {
                    $pFields[] = '"' . str_replace('"', '""', $field) . '"';
                } else {
                    $pFields[] = $field;
                }
            }
            $csvRows[] = implode(',', $pFields);
        }
        $_FILES[$filename]['tmp_name'] = tempnam(sys_get_temp_dir(), 'modified_');
        file_put_contents($_FILES[$filename]['tmp_name'], implode("\n", $csvRows));
    }
}
