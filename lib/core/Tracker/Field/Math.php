<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tracker\Field\AbstractItemField;

/**
 * Handler to perform a calculation for the tracker entry.
 *
 * Letter key: ~math~
 *
 */
class Tracker_Field_Math extends \Tracker\Field\AbstractItemField implements \Tracker\Field\SynchronizableInterface, \Tracker\Field\IndexableInterface, \Tracker\Field\ExportableInterface, \Tracker\Field\FilterableInterface
{
    private static $runner;
    /**
     * Store the original base key of the mirrof field so we can replace it in the output. */
    private ?string $mirrorFieldBaseKey = null;
    public static function getManagedTypesInfo(): array
    {
        return [
            'math' => [
                'name' => tr('Mathematical Calculation'),
                'description' => tr('Perform a calculation upon saving the item based on other fields within the same item.'),
                'help' => 'Mathematical-Calculation-Field',
                'prefs' => ['trackerfield_math'],
                'tags' => ['advanced'],
                'default' => 'y',
                'params' => [
                    'calculation' => [
                        'name' => tr('Calculation'),
                        'type' => 'textarea',
                        'description' => tr('Calculation in the Rating Language'),
                        'filter' => 'text',
                        'legacy_index' => 0,
                    ],
                    'recalculate' => [
                        'name' => tr('Re-calculation event'),
                        'type' => 'list',
                        'description' => tr('Set this to "Indexing" to update the value during reindexing as well as when saving. Selection of indexing is useful for dynamic score fields that will not be displayed.'),
                        'filter' => 'word',
                        'options' => [
                            'save' => tr('Save'),
                            'index' => tr('Indexing'),
                        ],
                    ],
                    'mirrorField' => [
                        'name' => tr('Mirror field'),
                        'description' => tr('Field ID from any tracker that governs the output of this calculation. Useful if you want to mimic the behavior and output of a specific field but with value coming from a calculation: e.g. currency calculations, itemlink fields.'),
                        'filter' => 'int',
                        'profile_reference' => 'tracker_field',
                        'sort_order' => 'title',
                    ],
                    'sortField' => [
                        'name' => tr('Type of sort'),
                        'type' => 'list',
                        'description' => tr('Allows you to choose between sorting based on numbers or on strings. If you sort numbers based on strings, 2 will be larger than 12'),
                        'filter' => 'word',
                        'options' => [
                            'numeric_sort' => tr('Numeric'),
                            'text_sort' => tr('Text'),
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = []): array
    {
        global $tiki_p_admin, $tiki_p_admin_trackers;

        if (isset($requestData[$this->getInsertId()])) {
            $value = $requestData[$this->getInsertId()];
        } else {
            $value = $this->getValue();
        }

        return [
            'value' => $value,
        ];
    }

    public function renderInput($context = [])
    {
        return tr('Value will be re-calculated on save. Current value: %0', $this->getValue());
    }

    public function renderOutput($context = [])
    {
        $mirroredFieldInstance = $this->getMirroredHandler();

        if ($mirroredFieldInstance) {
            return $mirroredFieldInstance->renderOutput($context);
        } else {
            return $this->getValue();
        }
    }

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

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory, $mode = '')
    {
        $value = $this->getValue();

        if ('index' == $this->getOption('recalculate') && $mode !== 'formatting') {
            $value = $this->recalculate();
        }

        $handler = $this->getMirroredHandler();
        $out = [];

        if ($handler && $handler instanceof \Tracker\Field\IndexableInterface) {
            $out = $handler->getDocumentPart($typeFactory);
            $out = $this->replaceBaseKeyInArrayKeys($out);
        } else {
            $baseKey = $this->getBaseKey();
            $out[$baseKey] = $typeFactory->sortable($value);
        }

        return $out;
    }

    public function getProvidedFields(): array
    {
        $providedFields = [];
        $handler = $this->getMirroredHandler();
        if ($handler && $handler instanceof \Tracker\Field\IndexableInterface) {
            $mirrorProvidedFields = $handler->getProvidedFields();
            $providedFields = $this->replaceBaseKeyInArrayValues($mirrorProvidedFields);
        }
        $baseKey = $this->getBaseKey();
        array_push($providedFields, $this->getBaseKey());
        return $providedFields;
    }

    public function getProvidedFieldTypes(): array
    {
        $handler = $this->getMirroredHandler();
        if ($handler && $handler instanceof \Tracker\Field\IndexableInterface) {
            return $this->replaceBaseKeyInArrayKeys($handler->getProvidedFieldTypes());
        } else {
            $baseKey = $this->getBaseKey();
            return [$baseKey => 'sortable'];
        }
    }

    public function getGlobalFields(): array
    {
        return [];
    }

    /**
     * Recalculate formula after saving all other fields in the tracker item
     * @param array $data - field values to save - passed by reference as
     * prepareFieldValues might add ItemsList field reference values here
     * and make them available for other Math fields in the same item, thus
     * greatly speeding up the process.
     */
    public function handleFinalSave(array &$data)
    {
        try {
            $this->prepareFieldValues($data);
            $data = array_merge($data, $this->calcMetadata());
            $runner = $this->getFormulaRunner();
            $runner->setVariables($data);

            return (string)$runner->evaluate();
        } catch (Math_Formula_Exception $e) {
            return $e->getMessage();
        }
    }

    public function getTabularSchema()
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());

        $permName = $this->getConfiguration('permName');
        $handler = $this->getMirroredHandler();

        if ($handler && $handler instanceof \Tracker\Field\ExportableInterface) {
            $sub = $handler->getTabularSchema();
            foreach ($sub->getColumns() as $column) {
                $column->setPermName($permName)
                    ->setLabel($this->getConfiguration('name'));
                $schema->addActualColumn($column);
            }
        } else {
            $schema->addNew($permName, 'default')
            ->setLabel($this->getConfiguration('name'))
            ->setRenderTransform(function ($value) {
                return $value;
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = $value;
            })
            ;
            $schema->addNew($permName, 'default-recalc')
            ->setLabel($this->getConfiguration('name'))
            ->setRenderTransform(function ($value) {
                return $value;
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = $value;
                $data = $info['fields'];
                if (! empty($info['itemId']) && ! isset($data['old_values_by_permname'])) {
                    $data['old_values_by_permname'] = [];
                    $currentItem = (new Services_Tracker_Utilities())->getItem($this->getTrackerDefinition()->getConfiguration('trackerId'), $info['itemId']);
                    foreach ($currentItem['fields'] as $fieldId => $val) {
                        $field = $this->getTrackerDefinition()->getField($fieldId);
                        if ($field) {
                            $data['old_values_by_permname'][$field['permName']] = $val;
                        }
                    }
                }
                $info['fields'][$permName] = $this->handleFinalSave($data);
            })
            ;
        }

        return $schema;
    }

    public function getFilterCollection()
    {
        $collection = new Tracker\Filter\Collection($this->getTrackerDefinition());
        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');
        $baseKey = $this->getBaseKey();
        $handler = $this->getMirroredHandler();

        if ($handler && $handler instanceof \Tracker\Field\FilterableInterface) {
            $sub = $handler->getFilterCollection();
            foreach ($sub->getFilters() as $subfilter) {
                $subfilter->setLabel($name);
            }
            $collection->addCloned($permName, $sub);
        } else {
            $collection->addNew($permName, 'fulltext')
            ->setLabel($name)
            ->setHelp(tr('Full-text search of the content of the field.'))
            ->setControl(new Tracker\Filter\Control\TextField("tf_{$permName}_ft"))
            ->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
                $value = $control->getValue();

                if ($value) {
                    $query->filterContent($value, $baseKey);
                }
            });
            $collection->addNew($permName, 'initial')
            ->setLabel($name)
            ->setHelp(tr('Search for a value prefix.'))
            ->setControl(new Tracker\Filter\Control\TextField("tf_{$permName}_init"))
            ->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
                $value = $control->getValue();

                if ($value) {
                    $query->filterInitial($value, $baseKey);
                }
            });
            $collection->addNew($permName, 'exact')
            ->setLabel($name)
            ->setHelp(tr('Search for a precise value.'))
            ->setControl(new Tracker\Filter\Control\TextField("tf_{$permName}_em"))
            ->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
                $value = $control->getValue();

                if ($value) {
                    $query->filterIdentifier($value, $baseKey);
                }
            });
        }

        return $collection;
    }

    /**
     * @see Tracker_Item::prepareFieldValues
     */
    private function prepareFieldValues(&$data)
    {
        $item = Tracker_Item::fromId($this->getItemId());
        $item->prepareFieldValues($data);
    }

    private function getFormulaRunner()
    {
        $globalperms = Perms::get();
        static $cache = [];
        $fieldId = $this->getConfiguration('fieldId');
        if (! isset($cache[$fieldId])) {
            if (! empty($this->getOption('calculation')) && ! ctype_space($this->getOption('calculation'))) {
                $cache[$fieldId] = $this->getOption('calculation');
            } else {
                if ($globalperms->admin || $globalperms->admin_trackers) {
                    throw new Math_Formula_Runner_Exception(tra('The field option, Calculation is empty or filled with whitespace.'));
                } else {
                    throw new Math_Formula_Runner_Exception(tra(''));
                }
            }
        }

        $runner = self::getRunner();

        $cache[$fieldId] = $runner->setFormula($cache[$fieldId]);

        return $runner;
    }

    public static function getRunner()
    {
        if (! self::$runner) {
            self::$runner = new Math_Formula_Runner(
                [
                'Math_Formula_Function_' => '',
                'Tiki_Formula_Function_' => '',
                ]
            );
        }

        return self::$runner;
    }

    public static function resetRunner()
    {
        self::$runner = null;
    }

    public function getMirroredHandler(): AbstractItemField | false
    {
        $mirrorFieldId = $this->getOption('mirrorField');
        $handler = false;

        if ($mirrorFieldId && $mirrorFieldId != $this->getFieldId()) {
            $mirrorFieldInfo = TikiLib::lib('trk')->get_field_info($mirrorFieldId);
            if ($mirrorFieldInfo) {
                $item = TikiLib::lib('trk')->get_tracker_item($this->getItemId());
                // use calculated value as the mirrored field value to allow handler produce results based on the math calculation
                //Note that this is still VERY dependant on legacy internal implementation of how field data is cached, but for 27.x it will have to be sufficient - benoitg - 2024-09-04
                if (isset($item[$this->getFieldId()])) {
                    $item[$mirrorFieldId] = $item[$this->getFieldId()];
                } elseif ($item) {
                    $item[$mirrorFieldId] = $this->getData($this->getConfiguration('permName'));
                }
                //var_dump($mirrorFieldInfo, $item);
                $handler = TikiLib::lib('trk')->get_field_handler($mirrorFieldInfo, $item);
                $this->mirrorFieldBaseKey = $handler->getBaseKey();
            }
        }

        return $handler;
    }

    private function replaceBaseKeyInArrayValues($array): array
    {
        $oldKey = $this->mirrorFieldBaseKey;
        $newKey = $this->getBaseKey();

        if (array_search($oldKey, $array) === false) {
            var_dump($oldKey, $array);
            throw new Error("Sanity-check:  Unable to find the expected mirrorField basekey");
        }

        $newValues = array_map(function ($value) use ($oldKey, $newKey) {
            return str_replace($oldKey, $newKey, $value);
        }, $array);

        return $newValues;
    }

    private function replaceBaseKeyInArrayKeys($array): array
    {
        $oldKeys = array_keys($array);
        $newKeys = $this->replaceBaseKeyInArrayValues($oldKeys);

        return array_combine($newKeys, $array);
    }

    /** Be careful, this does NOT return an object */
    private function getItemField($permName)
    {
        $field = $this->getTrackerDefinition()->getFieldFromPermName($permName);

        if ($field) {
            $id = $field['fieldId'];

            return $this->getData($id);
        }
    }

    public function recalculate()
    {
        try {
            $runner = $this->getFormulaRunner();
            $data = $this->calcMetadata();

            foreach ($runner->inspect() as $fieldName) {
                if (is_string($fieldName) || is_numeric($fieldName)) {
                    if (isset($data[$fieldName])) {
                        continue;
                    }
                    $data[$fieldName] = $this->getItemField($fieldName);
                }
            }

            $this->prepareFieldValues($data);
            // get it again as runner is a static property and could have overridden formula by preparing other field values
            $runner = $this->getFormulaRunner();
            $runner->setVariables($data);

            $value = (string)$runner->evaluate();
        } catch (Math_Formula_Exception $e) {
            $value = $e->getMessage();
            trigger_error("Error in Math field calculation: " . $value, E_USER_WARNING);
        }

        if ($value !== $this->getValue()) {
            $trklib = TikiLib::lib('trk');
            $trklib->modify_field($this->getItemId(), $this->getConfiguration('fieldId'), $value);
        }

        return $value;
    }

    private function calcMetadata()
    {
        global $url_host, $base_url;

        return [
        'itemId' => $this->getItemId(),
        'trackerId' => $this->getTrackerDefinition()->getConfiguration('trackerId'),
        'creation_date' => $this->getData('created'),
        'created_by' => $this->getData('createdBy'),
        'modification_date' => $this->getData('lastModif'),
        'last_modified_by' => $this->getData('lastModifBy'),
        'domain' => $url_host,
        'base_url' => $base_url . (substr($base_url, -1) == '/' ? '' : '/'),
        ];
    }
}
