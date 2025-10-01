<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Handler class for Slider field
 *
 * Letter key: ~n~
 *
 */
class Tracker_Field_Slider extends Tracker_Field_Numeric implements \Tracker\Field\SynchronizableInterface, Search_FacetProvider_Interface, \Tracker\Field\ExportableInterface, \Tracker\Field\FilterableInterface
{
    public static function getManagedTypesInfo(): array
    {
        return [
            'SLIDER' => [
                'name' => tr('Slider'),
                'description' => tr('A slider field for selecting a value within a range.'),
                'readonly' => false,
                'help' => 'Slider-Tracker-Field',
                'prefs' => ['trackerfield_slider', 'feature_elementplus'],
                'tags' => ['advanced'],
                'default' => 'y',
                'supported_changes' => ['n'],
                'params' => [
                    'min' => [
                        'name' => tr('Minimum Value'),
                        'description' => tr('The minimum value of the slider.'),
                        'filter' => 'int',
                        'default' => 0,
                        'legacy_index' => 0,
                    ],
                    'max' => [
                        'name' => tr('Maximum Value'),
                        'description' => tr('The maximum value of the slider.'),
                        'filter' => 'int',
                        'default' => 100,
                        'legacy_index' => 1,
                    ],
                    'orientantion' => [
                        'name' => tr('Orientation'),
                        'description' => tr('The orientation of the slider.'),
                        'filter' => 'text',
                        'options' => [
                            'horizontal' => tr('Horizontal'),
                            'vertical' => tr('Vertical'),
                        ],
                        'default' => 'horizontal',
                        'legacy_index' => 2,
                    ],
                    'step' => [
                        'name' => tr('Step Size'),
                        'description' => tr('The step size for the slider.'),
                        'filter' => 'int',
                        'default' => 1,
                        'legacy_index' => 3,
                    ],
                    'breakpoints' => [
                        'name' => tr('Show breakpoints'),
                        'description' => tr('Show breakpoints on the slider.'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => 0,
                        'legacy_index' => 4,
                    ],
                    'range' => [
                        'name' => tr('Range'),
                        'description' => tr('Allow selection of a range of values.'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => 0,
                        'legacy_index' => 6,
                    ],
                    'inputBox' => [
                        'name' => tr('Show input box'),
                        'description' => tr('Show an input box for manual entry of the value.'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => 0,
                        'depends' => [
                            'field' => 'range',
                            'value' => "0",
                        ],
                        'legacy_index' => 5,
                    ],
                ],
            ],
        ];
    }

    public function renderInput($context = [])
    {
        TikiLib::lib('header')->add_js_module('import "@vue-widgets/el-slider"');

        $min = $this->trackerField->getOption('min');
        $max = $this->trackerField->getOption('max');
        $step = $this->trackerField->getOption('step');
        $breakpoints = $this->trackerField->getOption('breakpoints') ? 'true' : 'false';
        $inputBox = $this->trackerField->getOption('inputBox') ? 'true' : 'false';
        $range = $this->trackerField->getOption('range') ? 'true' : 'false';
        $vertical = $this->trackerField->getOption('orientation') === 'vertical';
        $value = $this->getValue();

        TikiLib::lib('header')->add_js_module(<<<JS
             import('@tiki/ui-utils').then(({ handleSlider }) => {
                handleSlider('#{$this->getInsertId()}', '{$this->getHTMLFieldName()}');
            });
        JS);

        return <<<HTML
        <el-slider id="{$this->getInsertId()}" min="{$min}" max="{$max}" step="{$step}" show-stops="{$breakpoints}" show-input="{$inputBox}" range="{$range}" vertical="{$vertical}" value='{$value}'></el-slider>
        HTML;
    }

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        if ($this->trackerField->getOption('range')) {
            $value = $this->getValue();
            $baseKey = $this->getBaseKey();
            return [$baseKey => $typeFactory->multivalue(explode(',', (string) $value))];
        }

        return parent::getDocumentPart($typeFactory);
    }


    public function getTabularSchema()
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());

        $permName = $this->trackerField->getPermName();
        if ($this->trackerField->getOption('range')) {
            $schema->addNew($permName, 'default')
            ->setLabel($this->trackerField->getName())
            ->setRenderTransform(function ($value) {
                return $value[0] . ' - ' . $value[1];
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = $value;
            })
            ;
        } else {
            $schema->addNew($permName, 'default')
                ->setLabel($this->trackerField->getName())
                ->setRenderTransform(function ($value) {
                    return $value;
                })
                ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                    $info['fields'][$permName] = $value;
                })
                ;
        }

        return $schema;
    }
}
