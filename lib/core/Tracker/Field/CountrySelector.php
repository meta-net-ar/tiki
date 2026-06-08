<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Handler class for CountrySelector
 *
 * Letter key: ~y~
 *
 */
class Tracker_Field_CountrySelector extends \Tracker\Field\AbstractItemField implements \Tracker\Field\SynchronizableInterface, \Tracker\Field\ExportableInterface, \Tracker\Field\FilterableInterface, \Tracker\Field\EnumerableInterface
{
    private function getValueLabel($value)
    {
        $possibilities = $this->getPossibleItemValues();
        if (isset($possibilities[$value])) {
            return $possibilities[$value];
        } else {
            return $value;
        }
    }

    public static function getManagedTypesInfo(): array
    {
        return [
            'y' => [
                'name' => tr('Country Selector'),
                'description' => tr('Enable a selection from a specified list of countries'),
                'help' => 'Country-Selector',
                'prefs' => ['trackerfield_countryselector'],
                'tags' => ['basic'],
                'default' => 'y',
                'params' => [
                    'name_flag' => [
                        'name' => tr('Display'),
                        'description' => tr('Specify the rendering type for the field'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('Name and flag'),
                            1 => tr('Name only'),
                            2 => tr('Flag only'),
                        ],
                        'legacy_index' => 0,
                    ],
                    'sortorder' => [
                        'name' => tr('Sort order'),
                        'description' => tr('Determines whether the ordering should be based on the translated name or the English name.'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('Translated name'),
                            1 => tr('English name'),
                        ],
                        'legacy_index' => 1,
                    ],
                    'multiple' => [
                        'name' => tr('Multiple selection'),
                        'description' => tr('Allow selection of multiple countries from the list.'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes '),
                        ],
                        'default' => 0,
                    ],
                    'inputtype' => [
                        'name' => tr('Select Type'),
                        'description' => tr('User interface control to be used.'),
                        'default' => 'm',
                        'filter' => 'alpha',
                        'options' => [
                            'm' => tr('List box'),
                            't' => tr('Transfer')
                        ],
                        'depends' => [
                            'field' => 'multiple',
                            'value' => '1',
                        ],
                    ],
                    'filterable' => [
                        'name' => tr('Filterable'),
                        'description' => tr('Allow the user to filter items within the transfer list'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'depends' => [
                            'field' => 'inputtype',
                            'value' => 't'
                        ],
                    ],
                    'filterPlaceholder' => [
                        'name' => tr('Filter Placeholder'),
                        'description' => tr('Placeholder text for the filter input'),
                        'filter' => 'text',
                        'depends' => [
                            'field' => 'filterable',
                            'value' => '1'
                        ],
                    ],
                    'sourceListTitle' => [
                        'name' => tr('Source List Title'),
                        'description' => tr('Title for the source list'),
                        'filter' => 'text',
                        'depends' => [
                            'field' => 'inputtype',
                            'value' => 't'
                        ],
                    ],
                    'targetListTitle' => [
                        'name' => tr('Target List Title'),
                        'description' => tr('Title for the target list'),
                        'filter' => 'text',
                        'depends' => [
                            'field' => 'inputtype',
                            'value' => 't'
                        ],
                    ],
                    'ordering' => [
                        'name' => tr('Ordering'),
                        'description' => tr('Allow re-ordering of items in the list'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'depends' => [
                            'field' => 'inputtype',
                            'value' => 't'
                        ],
                    ],
                    'labelasplaceholder' => [
                        'name' => tr('Use label as placeholder'),
                        'description' => tr('Display the field name as a placeholder in the input field instead of separate label.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'default' => 0,
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getPossibleItemValues()
    {
        return TikiLib::lib('trk')->get_flags(true, true, ($this->getOption('sortorder') != 1));
    }

    public function canHaveMultipleValues()
    {
        return $this->getOption('multiple');
    }

    public function getFieldData(array $requestData = []): array
    {
        $ins_id = $this->getInsertId();

        $value = $requestData[$ins_id] ?? $this->getValue();

        if ($this->canHaveMultipleValues() && ! is_array($value)) {
            $value = explode(',', $value);
        }

        return [
            'value' => $value,
            'flags' => $this->getPossibleItemValues(),
            'defaultvalue' => 'None'
        ];
    }

    public function handleSave($value, $oldValue)
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        return ['value' => $value];
    }

    public function renderInnerOutput($context = [])
    {
        $flags = $this->getConfiguration('flags');
        $current = $this->getConfiguration('value');

        if (empty($current)) {
            return '';
        }
        $out = '';

        if (! is_array($current)) {
            $current = explode(',', $current);
        }

        foreach ($current as $index => $value) {
            $label = $flags[$value] ?? '';

            if ($context['list_mode'] != 'csv') {
                if ($this->getOption('name_flag') != 1) {
                    $out .= $this->renderImage($value, $label);
                }
                if ($this->getOption('name_flag') == 0) {
                    $out .= ' ';
                }
            }
            $out .= $label;
            if ($index < count($current) - 1) {
                $out .= $context['list_mode'] != 'csv' ? '<br />' : ', ';
            }
        }

        return $out;
    }

    /**
     * Return plain text representation of selected countries
     *
     * @param array $context
     * @return string
     */
    public function renderText($context = [])
    {
        $current = $this->getConfiguration('value');

        if (empty($current)) {
            return '';
        }

        return $current;
    }

    private function renderImage($code, $label)
    {
        $smarty = TikiLib::lib('smarty');
        return '<img src="img/flags/' . smarty_modifier_escape($code) . '.png" title="' . smarty_modifier_escape($label) . '" alt="' . smarty_modifier_escape($label) . '" />';
    }

    public function renderInput($context = [])
    {
        return $this->renderTemplate('trackerinput/countryselector.tpl', $context);
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

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        $value = $this->getValue();
        $baseKey = $this->getBaseKey();
        $data = [
            $baseKey => $typeFactory->identifier($value),
        ];

        if ($this->canHaveMultipleValues()) {
            $values = array_filter(explode(',', $value), 'strlen');
            $labels = array_map([$this, 'getValueLabel'], $values);
            $data["{$baseKey}_text"] = $typeFactory->sortable(implode(',', $labels));
            $data["{$baseKey}_multi"] = $typeFactory->multivalue($values);
        } else {
            $label = $this->getValueLabel($value);
            $data["{$baseKey}_text"] = $typeFactory->sortable($label);
        }
        return $data;
    }

    public function getProvidedFields(): array
    {
        $baseKey = $this->getBaseKey();
        $data = [$baseKey, $baseKey . '_text'];

        if ($this->canHaveMultipleValues()) {
            $data[] = "{$baseKey}_multi";
        }

        return $data;
    }

    public function getProvidedFieldTypes(): array
    {
        $baseKey = $this->getBaseKey();
        $data = [
            $baseKey           => 'identifier',
            $baseKey . '_text' => 'sortable'
        ];

        if ($this->canHaveMultipleValues()) {
            $data["{$baseKey}_multi"] = 'multivalue';
        }

        return $data;
    }

    public function getGlobalFields(): array
    {
        $baseKey = $this->getBaseKey();
        return ["{$baseKey}_text" => true];
    }

    public function getTabularSchema()
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());

        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');

        $possibilities = $this->getPossibleItemValues();
        $invert = array_flip($possibilities);

        $schema->addNew($permName, 'code')
            ->setLabel($name)
            ->setRenderTransform(function ($value) {
                return $value;
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = $value;
            })
            ;

        $schema->addNew($permName, 'text')
            ->setLabel($name)
            ->addIncompatibility($permName, 'code')
            ->setRenderTransform(function ($value) use ($possibilities) {
                $value = array_filter(explode(',', $value), function ($v) use ($possibilities) {
                    return isset($possibilities[$v]);
                });
                if (count($value)) {
                    return implode(',', $value);
                }
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName, $invert) {
                $value = explode(',', $value);
                $value = array_filter($value, function ($v) use ($invert) {
                    return isset($invert[$v]);
                });
                if (count($value)) {
                    $info['fields'][$permName] = implode(',', $value);
                }
            })
            ;

        $schema->addNew($permName, 'flag')
            ->setLabel($name)
            ->setPlainReplacement('text')
            ->setRenderTransform(function ($value) use ($possibilities) {
                $value = explode(',', $value);
                $value = array_filter($value, function ($v) use ($possibilities) {
                    return isset($possibilities[$v]);
                });
                if (count($value)) {
                    return implode(' ', array_map(function ($v) use ($possibilities) {
                        return $this->renderImage($v, $possibilities[$v]);
                    }, $value));
                }
            })
            ;

        $schema->addNew($permName, 'flag-and-text')
            ->setLabel($name)
            ->setPlainReplacement('text')
            ->setRenderTransform(function ($value) use ($possibilities) {
                $value = explode(',', $value);
                $value = array_filter($value, function ($v) use ($possibilities) {
                    return isset($possibilities[$v]);
                });
                if (count($value)) {
                    return implode(',', array_map(function ($v) use ($possibilities) {
                        $label = $possibilities[$v];
                        return $this->renderImage($v, $possibilities[$v]) . ' ' . smarty_modifier_escape($label);
                    }, $value));
                }
            })
            ;

        return $schema;
    }

    public function getFilterCollection()
    {
        $filters = new Tracker\Filter\Collection($this->getTrackerDefinition());
        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');
        $baseKey = $this->getBaseKey();

        $possibilities = $this->getPossibleItemValues();
        $possibilities['-Blank (no data)-'] = tr('-Blank (no data)-');

        if ($this->canHaveMultipleValues()) {
            $filters->addNew($permName, 'multiselect')
                ->setLabel($name)
                ->setControl(new Tracker\Filter\Control\MultiSelect("tf_{$permName}_ms", $possibilities))
                ->setApplyCondition(function ($control, Search_Query $query) use ($permName, $baseKey) {
                    $values = $control->getValues();

                    if (! empty($values)) {
                        $sub = $query->getSubQuery("ms_$permName");

                        foreach ($values as $v) {
                            if ($v === '-Blank (no data)-') {
                                $sub->filterIdentifier('', $baseKey . '_text');
                            } elseif ($v) {
                                $sub->filterMultivalue((string) $v, $baseKey);
                            }
                        }
                    }
                });
        } else {
            $filters->addNew($permName, 'dropdown')
                ->setLabel($name)
                ->setControl(new Tracker\Filter\Control\DropDown("tf_{$permName}_dd", $possibilities))
                ->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
                    $value = $control->getValue();

                    if ($value === '-Blank (no data)-') {
                        $query->filterIdentifier('', $baseKey . '_text');
                    } elseif ($value) {
                        $query->filterIdentifier($value, $baseKey);
                    }
                });

            $filters->addNew($permName, 'multiselect')
                ->setLabel($name)
                ->setControl(new Tracker\Filter\Control\MultiSelect("tf_{$permName}_ms", $possibilities))
                ->setApplyCondition(function ($control, Search_Query $query) use ($permName, $baseKey) {
                    $values = $control->getValues();

                    if (! empty($values)) {
                        $sub = $query->getSubQuery("ms_$permName");

                        foreach ($values as $v) {
                            if ($v === '-Blank (no data)-') {
                                $sub->filterIdentifier('', $baseKey . '_text');
                            } elseif ($v) {
                                $sub->filterIdentifier((string) $v, $baseKey);
                            }
                        }
                    }
                });
        }

        return $filters;
    }
}
