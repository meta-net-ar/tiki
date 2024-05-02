<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/*
The Relation field type create a Many-to-Many relationship through the table tiki_object_relations (also used by wiki pages and other objects) and the table tracker_item_fields

The data saved in tiki_object_relations by this code has the following structure:

    relationId: int, the primary key of the related pair.
    relation: string, the relation namespace. Variously named type or name in the code and namespace in the web docs.
    source_type: string, In the case of tracker relations, always 'trackeritem'
    source_itemId: string, id of the source tracker item
    source_fieldId: int, id of the source tracker item field
    target_type: string, 'trackeritem', 'wiki page', 'user' depending on the target object type
    target_itemId: string, actual value may be an int (ex: tracker items) or a string  (ex: Wiki page)
    metadata_itemId: int, id of the optional tracker item used to describe this relation

As of 2023-03-15, this structure is never edited, always replaced.  That is removing a link to an object, saving, and adding it back will result in a row with a new relationId.

No data is saved in tracker_item_fields value column. Canonical representation of the relation data should be retrieved from tiki_object_relations. If there are 3 links, there will be 3 rows in tiki_object_relations.

 A lot of this code is hard to grasp by inspection, because it is spread out in multiple places:
 - Some of it lives here
 - Some of the code is implemented in the class RelationLib  (lib/attributes/relationlib.php) relationlib.php and is referenced as "relation" through db/config/tiki.xml
 - Some of the code lives directly in the class TikiLib lib/tikilib.php (such as replace_link())
 - Some of the code lives in Services_Relation_Controller (lib/core/Services/Relation/Controller.php)

Part of the documentation is at https://dev.tiki.org/Object+Attributes+and+Relations and https://doc.tiki.org/Relations-Tracker-Field
*/

use Tiki\Relation\ObjectRelation;

class Tracker_Field_Relation extends \Tracker\Field\AbstractField implements \Tracker\Field\ExportableInterface, \Tracker\Field\FilterableInterface
{
    private const OPT_RELATION = 'relation';
    private const OPT_FILTER = 'filter';
    private const OPT_READONLY = 'readonly';
    private const OPT_INVERT = 'invert';

    public static $refreshedTargets = [];

    public static function getManagedTypesInfo(): array
    {
        return [
            'REL' => [
                'name' => tr('Relations'),
                'description' => tr('Allow arbitrary relations to be created between the trackers and other objects in the system.'),
                'prefs' => ['trackerfield_relation'],
                'tags' => ['advanced'],
                'default' => 'y',
                'help' => 'Relations-Tracker-Field',
                'params' => [
                    'relation' => [
                        'name' => tr('Relation'),
                        'description' => tr('Mandatory.  Relation qualifier. Must be a three-part qualifier containing letters and separated by dots.  The field will silently refuse to save values if it\'s empty.'),
                        'filter' => 'attribute_type',
                        'legacy_index' => 0
                    ],
                    'filter' => [
                        'name' => tr('Filter'),
                        'description' => tr('URL-encoded list of filters to be applied on object selection.'),
                        'filter' => 'url',
                        'legacy_index' => 1,
                        'profile_reference' => 'search_urlencoded',
                    ],
                    'format' => [
                        'name' => tr('Format'),
                        'description' => tr('Customize display of search results of object selection. Default is {title} listing the object title. Note that including other fields in the format will make search look up exactly those fields intead of the title field.'),
                        'filter' => 'text',
                    ],
                    'relationshipTrackerId' => [
                        'name' => tr('Relationship Tracker ID'),
                        'description' => tr('Optionally describe the relationship with a metadata system tracker. You can create predefined trackers from %0admin trackers%1 or define any existing tracker from its properties.', '<a href="tiki-admin.php?page=trackers#content-admin1-4" target="_blank">', '</a>'),
                        'filter' => 'int',
                        'profile_reference' => 'tracker',
                        'selector_filter' => 'relationship-trackers'
                    ],
                    'readonly' => [
                        'name' => tr('Read-only'),
                        'description' => tr('Only display the incoming relations instead of manipulating them.'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'legacy_index' => 2,
                    ],
                    'invert' => [
                        'name' => tr('Include Invert'),
                        'description' => tr('Include invert relations in the list'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'legacy_index' => 3,
                    ],
                    'display' => [
                        'name' => tr('Display'),
                        'description' => tr('Control how the relations are displayed in view mode'),
                        'filter' => 'word',
                        'options' => [
                            'list' => tr('List'),
                            'count' => tr('Count'),
                            'toggle' => tr('Count with toggle for list'),
                        ],
                        'legacy_index' => 4,
                    ],
                    'refresh' => [
                        'name' => tr('Force Refresh'),
                        'description' => tr('Re-save related tracker items.'),
                        'filter' => 'alpha',
                        'options' => [
                            '' => tr('No'),
                            'save' => tr('On Save'),
                        ],
                    ],
                    'parentFilter' => [
                        'name' => tr('Extra Filter Field'),
                        'description' => tr('Filter objects by value of another field. Use a jQuery seletor to target the element in the page.'),
                        'filter' => 'text',
                    ],
                    'parentFilterKey' => [
                        'name' => tr('Extra Filter Key'),
                        'description' => tr('Key to filter objects by using the value from the Extra Filter Field above.'),
                        'filter' => 'text',
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = [])
    {
        $insertId = $this->getInsertId();

        $data = [];
        $relations = [];
        $meta = [];
        if (! $this->getOption(self::OPT_READONLY) && isset($requestData[$insertId])) {
            $selector = TikiLib::lib('objectselector');
            if (is_array($requestData[$insertId])) {
                $entries = $selector->readMultiple($requestData[$insertId]['objects']);
                $meta = json_decode($requestData[$insertId]['meta'], true);
            } else {
                $entries = $selector->readMultiple($requestData[$insertId]);
            }
            $data = array_map('strval', $entries);
        } else {
            $relation = $this->getOption(self::OPT_RELATION);
            $relations = TikiLib::lib('relation')->getObjectRelations('trackeritem', $this->getItemId(), $relation);
            if ($this->getOption(self::OPT_INVERT)) {
                $relations = array_merge($relations, TikiLib::lib('relation')->getObjectRelations('trackeritem', $this->getItemId(), $relation, true));
            }
            foreach ($relations as $rel) {
                $data[] = strval($rel->target);
                if ($rel->metadata) {
                    $meta[strval($rel->target)] = $rel->metadata->itemId;
                }
            }
            $data = array_unique($data);
            if (empty($data) && $this->getValue()) {
                $data = array_filter(array_map(function ($line) {
                    if (! strstr($line, ':')) {
                        return null;
                    } else {
                        return trim($line);
                    }
                }, explode("\n", $this->getValue())));
            }
        }

        return [
            'value' => implode("\n", $data),
            'relations' => $relations,
            'meta' => $meta,
        ];
    }

    public function addValue($value)
    {
        $existing = explode("\n", $this->getValue());
        if (! in_array($value, $existing)) {
            $existing[] = $value;
        }
        return implode("\n", $existing);
    }

    public function removeValue($value)
    {
        $existing = explode("\n", $this->getValue());
        $existing = array_filter($existing, function ($v) use ($value) {
            return $v != $value;
        });
        return implode("\n", $existing);
    }

    public function renderInput($context = [])
    {
        if ($this->getOption(self::OPT_READONLY)) {
            return tra('Read-only');
        }

        $data = $this->getFieldData();

        $filter = $this->buildFilter();

        if (
            isset($filter['tracker_id']) &&
                $this->getConfiguration('trackerId') == $filter['tracker_id'] &&
                ! isset($filter['object_id']) && $this->getItemId()
        ) {
            $filter['object_id'] = 'NOT ' . $this->getItemId(); // exclude this item if we are related to the same tracker_id
        }

        $params = [
            'existing' => $data['value'],
            'relations' => $data['relations'],
            'meta' => json_encode($data['meta']),
            'filter' => $filter,
            'format' => $this->getOption('format'),
            'parent' => $this->getOption('parentFilter'),
            'parentkey' => $this->getOption('parentFilterKey'),
        ];

        $relationshipTracker = $this->getRelationshipTracker();
        $params['relationshipTracker'] = $relationshipTracker;
        if ($relationshipTracker) {
            $params['relationshipTrackerId'] = $relationshipTracker->getConfiguration('trackerId');
            $params['relationshipBehaviour'] = $relationshipTracker->getRelationshipBehaviour($this->getOption(self::OPT_RELATION));
        }

        return $this->renderTemplate(
            'trackerinput/relation.tpl',
            $context,
            $params
        );
    }

    public function renderInnerOutput($context = [])
    {
        if ($context['list_mode'] === 'csv') {
            // use field value instead of relation storage because of history logs
            $fieldId = $this->getConfiguration('fieldId');
            if (! empty($fieldId)) {
                $itemData = $this->getData($fieldId);
                $items = preg_split('/[\s]+/', $itemData);

                $returnValue = '';
                foreach ($items as $itemValue) {
                    $itemFieldValue = explode(':', $itemValue);
                    if (! empty($itemFieldValue[0]) && ! empty($itemFieldValue[1])) {
                        $objectLib = TikiLib::lib('object');
                        $value = $objectLib->get_title($itemFieldValue[0], $itemFieldValue[1]);
                    } else {
                        $value = $itemValue;
                    }

                    $returnValue .= ! empty($returnValue) ? ', ' . $value : $value;
                }

                return ! empty($returnValue) ? $returnValue : $itemData;
            }
            return $this->getConfiguration('value');
        } elseif ($context['list_mode'] === 'text') {
            return implode(
                "\n",
                array_map(
                    function ($rel) {
                        return $rel->target->getTitle($this->getOption('format'));
                    },
                    $this->getConfiguration('relations')
                )
            );
        } else {
            // TODO: render metadata as well
            return implode(
                "<br/>",
                array_map(
                    function ($rel) {
                        return $rel->target->getTitle($this->getOption('format'));
                    },
                    $this->getConfiguration('relations')
                )
            );
        }
    }

    public function renderOutput($context = [])
    {
        $list_mode = $context['list_mode'] ?? '';
        if ($list_mode === 'csv' || $list_mode === 'text') {
            return $this->renderInnerOutput($context);
        } else {
            $display = $this->getOption('display');
            if (! in_array($display, ['list', 'count', 'toggle'])) {
                $display = 'list';
            }

            $relations = $this->getConfiguration('relations');
            foreach ($relations as $key => $rel) {
                if ($rel->target->type != 'trackeritem') {
                    continue;
                }
                $item = Tracker_Item::fromId($rel->target->itemId);
                if ($item && ! $item->canView()) {
                    unset($relations[$key]);
                }
            }
            $relations = array_values($relations);

            return $this->renderTemplate(
                'trackeroutput/relation.tpl',
                $context,
                [
                    'display' => $display,
                    'relations' => $relations,
                    'format' => $this->getOption('format')
                ]
            );
        }
    }

    public function handleSpecialSave($field)
    {
        $value = $field['value'] ?? null;
        if ($value && ! is_array($value)) {
            $target = explode("\n", trim($value));
        } elseif ($value) {
            $target = $value;
        } else {
            $target = [];
        }

        // saved items should not refresh themselves later => solves odd issues with relation disappearing
        self::$refreshedTargets[] = 'trackeritem:' . $this->getItemId();

        if ($this->getOption(self::OPT_READONLY)) {
            if ($this->getOption('refresh') == 'save') {
                $this->prepareRefreshRelated($target);
            }

            return [
                'value' => '',
            ];
        }

        $relationlib = TikiLib::lib('relation');
        $relation = $this->getOption(self::OPT_RELATION);
        $current = TikiLib::lib('relation')->getObjectRelations('trackeritem', $this->getItemId(), $relation);
        if ($this->getOption(self::OPT_INVERT)) {
            $current = array_merge($current, TikiLib::lib('relation')->getObjectRelations('trackeritem', $this->getItemId(), $relation, true));
        }
        $map = [];
        foreach ($current as $rel) {
            $key = strval($rel->target);
            $map[$key] = $rel;
        }

        $toRemove = array_diff(array_keys($map), $target);
        $toAdd = array_diff($target, array_keys($map));

        foreach ($toRemove as $v) {
            $relation = $map[$v];
            $relationlib->remove_relation($relation->id);
        }

        foreach ($toAdd as $key) {
            list($type, $id) = explode(':', $key, 2);

            if ($type == 'trackeritem' && ! TikiLib::lib('trk')->get_item_info($id)) {
                continue;
            }

            $metadataItemId = $field['meta'][$key] ?? $field['meta'][$this->getInsertId() . '[objects]'] ?? null;

            $relationlib->add_relation($this->getOption(self::OPT_RELATION), 'trackeritem', $this->getItemId(), $type, $id, false, $this->getFieldId(), $metadataItemId);
        }

        if (! empty($field['meta'])) {
            $toKeep = array_intersect(array_keys($map), $target);
            foreach ($toKeep as $key) {
                $relation = $map[$key];
                $metadataItemId = $field['meta'][$key] ?? $field['meta'][$this->getInsertId() . '[objects]'] ?? null;
                if ($relation->getMetadataItemId() != $metadataItemId) {
                    $relationlib->updateMetadataItemId($relation->id, $metadataItemId);
                }
            }
        }

        if ($this->getOption('refresh') == 'save') {
            $this->prepareRefreshRelated(array_merge($target, $toRemove));
        }

        return [
            'value' => $value,
        ];
    }

    public function watchCompare($old, $new)
    {
        return parent::watchCompareList(explode("\n", $old), explode("\n", $new), function ($item) {
            list($type, $object) = explode(':', $item, 2);
            return TikiLib::lib('object')->get_title($type, $object, $this->getOption('format'));
        });
    }

    /**
     * Update existing data in relations table when changing the relation name.
     * Used when updating field options.
     *
     * @param $params - array of field options
     */
    public function convertFieldOptions($params)
    {
        if (empty($params['relation'])) {
            return;
        }
        if ($params['relation'] != $this->getOption(self::OPT_RELATION)) {
            $relationlib = TikiLib::lib('relation');
            $relationlib->update_relation($this->getOption(self::OPT_RELATION), $params['relation'], $this->getFieldId());
        }
    }

    public function handleFieldSave($data)
    {
        $trackerId = $this->getConfiguration('trackerId');
        $options = json_decode($data['options'], true);

        if (preg_match("/tracker_id=[^&]*{$trackerId}/", $options['filter']) && $options['invert'] && $options['refresh']) {
            Feedback::warning(tr('Self-related fields with Include Invert option set to Yes should not have Force Refresh option on save.'));
        }
    }

    /**
     * When Relation field is removed, clean up the relations table.
     */
    public function handleFieldRemove()
    {
        $trackerId = $this->getTrackerDefinition()->getConfiguration('trackerId');
        $relationlib = TikiLib::lib('relation');
        $relationlib->remove_relation_type($this->getOption(self::OPT_RELATION), $this->getFieldId());
    }

    private function prepareRefreshRelated($target)
    {
        $itemId = $this->getItemId();
        // After saving the field, bind a temporary event on save to refresh child elements

        TikiLib::events()->bind('tiki.trackeritem.save', function ($args) use ($itemId, $target) {
            if ($args['type'] == 'trackeritem' && $args['object'] == $itemId) {
                $utilities = new Services_Tracker_Utilities();

                foreach ($target as $key) {
                    if (in_array($key, self::$refreshedTargets)) {
                        continue;
                    }
                    self::$refreshedTargets[] = $key;

                    list($type, $id) = explode(':', $key, 2);

                    if ($type == 'trackeritem') {
                        $utilities->resaveItem($id);
                    }
                }
            }
        });
    }

    public static function syncRelationAdded($args)
    {
        self::syncContentUpdate($args, 'add');
    }

    public static function syncRelationRemoved($args)
    {
        self::syncContentUpdate($args, 'remove');
    }

    /**
     * Update field content based on relation add/remove event
     * @param array $args event arguments
     * @param string $action add/remove
     */
    public static function syncContentUpdate(array $args, string $action): void
    {
        $trklib = TikiLib::lib('trk');
        if (! empty($args['sourcefield'])) {
            $field = $trklib->get_field_info($args['sourcefield']);
            $handler = $trklib->get_field_handler($field);
            $relation = $handler->getOption(self::OPT_RELATION);
            if (substr($relation, -7) === '.invert') {
                $straight = false;
            } else {
                $straight = true;
            }
        } else {
            $straight = true;
        }
        if ($args['sourcetype'] == 'trackeritem') {
            if ($straight) {
                $relation = $args['relation'];
            } else {
                $relation = $args['relation'] . '.invert';
            }
            $trackerId = TikiLib::lib('trk')->get_tracker_for_item($args['sourceobject']);
            if (! $trackerId) {
                return;
            }
            $definition = Tracker_Definition::get($trackerId);
            if (! $definition) {
                return;
            }
            if ($fieldId = $definition->getRelationField($relation)) {
                $itemId = $args['sourceobject'];
                $value = $old_value = explode("\n", TikiLib::lib('trk')->get_item_value($trackerId, $itemId, $fieldId));
                $other = $args['type'] . ':' . $args['object'];
                if (! in_array($other, $value)) {
                    if ($action == 'add') {
                        $value[] = $other;
                    } else {
                        $value = array_diff($value, [$other]);
                    }
                }
                if ($value != $old_value) {
                    $value = implode("\n", $value);
                    TikiLib::lib('trk')->modify_field($itemId, $fieldId, $value);
                }
            }
        }
        if ($args['type'] == 'trackeritem') {
            if ($straight) {
                $relation = $args['relation'] . '.invert';
            } else {
                $relation = $args['relation'];
            }
            $trackerId = TikiLib::lib('trk')->get_tracker_for_item($args['object']);
            if (! $trackerId) {
                return;
            }
            $definition = Tracker_Definition::get($trackerId);
            if (! $definition) {
                return;
            }
            if ($fieldId = $definition->getRelationField($relation)) {
                $itemId = $args['object'];
                $value = $old_value = explode("\n", TikiLib::lib('trk')->get_item_value($trackerId, $itemId, $fieldId));
                $other = $args['sourcetype'] . ':' . $args['sourceobject'];
                if (! in_array($other, $value)) {
                    if ($action == 'add') {
                        $value[] = $other;
                    } else {
                        $value = array_diff($value, [$other]);
                    }
                }
                if ($value != $old_value) {
                    $value = implode("\n", $value);
                    TikiLib::lib('trk')->modify_field($itemId, $fieldId, $value);
                }
            }
        }
    }

    private function buildFilter()
    {
        parse_str($this->getOption(self::OPT_FILTER), $filter);
        return $filter;
    }

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory, $mode = '')
    {
        $baseKey = $this->getBaseKey();

        $data = $this->getFieldData();
        $value = $this->getValue();

        // we don't have all the data in the field definition at this point, so just render the labels here
        $objectLib = TikiLib::lib('object');
        $format = $this->getOption('format');
        $labels = [];
        static $cache = [];
        if ($mode !== 'formatting') {
            foreach ($data['relations'] as $rel) {
                $type = $rel->target->type;
                $object = $rel->target->itemId;
                if (isset($cache[$type . $object . $format])) {
                    // prevent circular-reference calls to objectlib->get_title method as getDocumentPart is used to populate the
                    // search results with field values which is called in get_title itself
                    // only happens for bi-directional tracker item relation
                    $labels[] = $cache[$type . $object . $format];
                    continue;
                }
                $label = $objectLib->get_title($type, $object, $format);
                $cache[$type . $object . $format] = $label;
                $labels[] = $label;
            }
        }

        if (TikiLib::lib('tiki')->isMemoryLow()) {
            $cache = [];
        }

        $plain = implode(', ', $labels);

        $text = '';
        $count = count($labels);
        for ($i = 0; $i < $count; $i++) {
            $text .= $labels[$i];
            if ($i === $count - 2) {
                $text .= ' ' . tr('and') . ' ';
            } elseif ($i < $count - 1) {
                $text .= ', ';
            }
        }

        $meta = $this->aggregateAllRelatedHandlers($data['relations'], function ($handler) use ($typeFactory) {
            return $handler->getDocumentPart($typeFactory);
        });

        return array_merge([
            $baseKey => $typeFactory->sortable($value),
            "{$baseKey}_multi" => $typeFactory->multivalue(explode("\n", $value)),
            "{$baseKey}_plain" => $typeFactory->plainmediumtext($plain),
            "{$baseKey}_text" => $typeFactory->plainmediumtext($text),
        ], $meta);
    }

    public function getProvidedFields(): array
    {
        $baseKey = $this->getBaseKey();
        $data = $this->getFieldData();
        $meta = $this->aggregateAllRelatedHandlers($data['relations'], function ($handler) {
            return $handler->getProvidedFields();
        });
        return array_merge([
            $baseKey,               // comma separated object_type:object_id
            "{$baseKey}_multi",     // array [object_type:object_id]
            "{$baseKey}_plain",     // comma separated formatted object titles
            "{$baseKey}_text",      // comma separated formatted object titles with "and" before the last one
        ], $meta);
    }

    public function getProvidedFieldTypes(): array
    {
        $baseKey = $this->getBaseKey();
        $data = $this->getFieldData();
        $meta = $this->aggregateAllRelatedHandlers($data['relations'], function ($handler) {
            return $handler->getProvidedFieldTypes();
        });
        return array_merge([
            $baseKey => 'sortable',
            "{$baseKey}_multi" => 'multivalue',
            "{$baseKey}_plain" => 'plainmediumtext',
            "{$baseKey}_text" => 'plainmediumtext',
        ], $meta);
    }

    public function getGlobalFields(): array
    {
        $baseKey = $this->getBaseKey();
        return ["{$baseKey}_plain" => true];    // index contents with the object titles
    }

    protected function aggregateAllRelatedHandlers(array $relations, callable $cb)
    {
        $meta = [];
        foreach ($relations as $rel) {
            if ($rel->metadata) {
                if ($item = $rel->metadata->getItem()) {
                    $definition = $item->getDefinition();
                    foreach ($definition->getFields() as $field) {
                        $handler = TikiLib::lib('trk')->get_field_handler($field, $item->getInfo());
                        $handler->setBaseKeyPrefix($this->getConfiguration('permName') . '_meta');
                        $meta = array_merge($meta, $cb($handler));
                    }
                }
            }
        }
        return $meta;
    }

    /**
     * Get related items' formatted values in an array. Useful in
     * Math calculations where individual field values are needed.
     * @return array associated array of field names and values
     */
    public function getItemValues()
    {
        $lib = TikiLib::lib('unifiedsearch');

        $itemsValues = [];

        $data = $this->getFieldData();
        $objects = array_map(function ($rel) {
            list($object_type, $object_id) = [$rel->target->type, $rel->target->itemId];
            return compact('object_type', 'object_id');
        }, $data['relations']);

        $format = $this->getOption('format');
        foreach ($objects as $object) {
            $query = $lib->buildQuery($object);
            $result = $query->search($lib->getIndex());
            $result->applyTransform(function ($item) use ($format) {
                $values = [];
                preg_replace_callback('/\{(\w+)\}/', function ($matches) use ($item, $format, &$values) {
                    $key = $matches[1];
                    $value_key = str_replace('tracker_field_', '', $key);
                    if (isset($item[$key])) {
                        $values[$value_key] = $item[$key];
                    } else {
                        $values[$value_key] = '';
                    }
                }, $format);
                return $values;
            });
            // result might be empty if item is no longer in db or user does not have permission to see it
            if ($result->count() > 0) {
                $itemsValues[] = $result->getArrayCopy()[0];
            }
        }
        return $itemsValues;
    }

    public function getTabularSchema()
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());
        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');
        $format = $this->getOption('format');

        $schema->addNew($permName, 'raw')
            ->setLabel($name)
            ->setRenderTransform(function ($value) {
                return $value;
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = $value;
            })
            ;


        $fullLookup = new Tracker\Tabular\Schema\CachedLookupHelper();
        $fullLookup->setLookup(function ($value) use ($format) {
            $relations = explode("\n", $value);
            $labels = [];
            foreach ($relations as $identifier) {
                if (empty($identifier)) {
                    continue;
                }
                list($type, $object) = explode(':', $identifier);
                if (empty($type) || empty($object)) {
                    continue;
                }
                $labels[] = TikiLib::lib('object')->get_title($type, $object, $format);
            }
            return implode(", ", $labels);
        });
        $schema->addNew($permName, 'lookup')
            ->setLabel($name)
            ->setReadOnly(true)
            ->addQuerySource('text', "tracker_field_{$permName}_plain")
            ->setRenderTransform(function ($value, $extra) use ($fullLookup) {
                if (isset($extra['text'])) {
                    return $extra['text'];
                } else {
                    return $fullLookup->get($value);
                }
            })
            ;

        $schema->addNew($permName, 'full')
            ->setLabel($name)
            ->setRenderTransform(function ($value) use ($format) {
                $relations = explode("\n", $value);
                $data = [];
                foreach ($relations as $identifier) {
                    if (empty($identifier)) {
                        continue;
                    }
                    list($type, $object) = explode(':', $identifier);
                    if (empty($type) || empty($object)) {
                        continue;
                    }
                    $label = TikiLib::lib('object')->get_title($type, $object, $format);
                    $data[$identifier] = $label;
                }
                return json_encode($data);
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $values = [];
                $data = json_decode($value, true);
                if (is_array($data)) {
                    foreach ($data as $identifier => $label) {
                        $values[] = $identifier;
                    }
                }
                $info['fields'][$permName] = implode("\n", $values);
            })
            ;

        return $schema;
    }

    public function getFilterCollection()
    {
        $collection = new Tracker\Filter\Collection($this->getTrackerDefinition());
        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');
        $baseKey = $this->getBaseKey();

        $osParams = [
            '_filter' => $this->buildFilter(),
            '_format' => $this->getOption('format'),
        ];

        $collection->addNew($permName, 'selector')
            ->setLabel($name)
            ->setControl(new Tracker\Filter\Control\ObjectSelector("tf_{$permName}_os", $osParams))
            ->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
                $value = $control->getValue();

                if ($value) {
                    $query->filterMultivalue((string) $value, "{$baseKey}_multi");
                }
            })
            ;

        $collection->addNew($permName, 'multiselect')
            ->setLabel($name)
            ->setControl(new Tracker\Filter\Control\ObjectSelector("tf_{$permName}_ms", $osParams, true))
            ->setApplyCondition(function ($control, Search_Query $query) use ($permName, $baseKey, $multivalue) {
                $value = $control->getValue();

                if ($value) {
                    $sub = $query->getSubQuery("ms_$permName");
                    foreach ($value as $v) {
                        if ($v) {
                            $sub->filterMultivalue((string) $v, "{$baseKey}_multi");
                        }
                    }
                }
            });

        return $collection;
    }

    protected function getRelationshipTracker(): Tracker_Definition|null
    {
        $relationshipTracker = null;
        if ($trackerId = $this->getOption('relationshipTrackerId')) {
            $relationshipTracker = Tracker_Definition::get($trackerId);
        }
        return $relationshipTracker;
    }
}
