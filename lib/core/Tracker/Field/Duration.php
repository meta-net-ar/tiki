<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Handler class for duration field type
 *
 * Letter key: ~DUR~
 *
 */
class Tracker_Field_Duration extends \Tracker\Field\AbstractField implements \Tracker\Field\SynchronizableInterface, \Tracker\Field\ExportableInterface, \Tracker\Field\FilterableInterface
{
    public static function getManagedTypesInfo(): array
    {
        return [
            'DUR' => [
                'name' => tr('Duration'),
                'description' => tr('Provide a convenient way to enter time duration in different units. It is highly advisable to turn Vue.js integration on for a better user interface.'),
                'help' => 'Duration-Tracker-Field',
                'prefs' => ['trackerfield_duration'],
                'tags' => ['basic'],
                'default' => 'y',
                'supported_changes' => ['DUR', 'n'],
                'params' => [
                    'seconds' => [
                        'name' => tr('Seconds'),
                        'description' => tr('Allow selection of seconds.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => 0,
                        'legacy_index' => 1,
                    ],
                    'minutes' => [
                        'name' => tr('Minutes'),
                        'description' => tr('Allow selection of minutes.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => 1,
                        'legacy_index' => 2,
                    ],
                    'hours' => [
                        'name' => tr('Hours'),
                        'description' => tr('Allow selection of hours.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => 1,
                        'legacy_index' => 3,
                    ],
                    'days' => [
                        'name' => tr('Days'),
                        'description' => tr('Allow selection of days.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => 0,
                        'legacy_index' => 4,
                    ],
                    'weeks' => [
                        'name' => tr('Weeks'),
                        'description' => tr('Allow selection of weeks.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => 0,
                        'legacy_index' => 5,
                    ],
                    'months' => [
                        'name' => tr('Months'),
                        'description' => tr('Allow selection of months.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => 0,
                        'legacy_index' => 6,
                    ],
                    'years' => [
                        'name' => tr('Years'),
                        'description' => tr('Allow selection of years.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => 0,
                        'legacy_index' => 7,
                    ],
                    'chronometer' => [
                        'name' => tr('Chronometer'),
                        'description' => tr('Add chronometer type of UI with start/stop and reset buttons to count elapsed time.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => 0,
                        'legacy_index' => 8,
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = [])
    {
        $ins_id = $this->getInsertId();

        if (isset($requestData[$ins_id]) && is_array($requestData[$ins_id])) {
            $value = json_encode($requestData[$ins_id]);
        } elseif (isset($requestData[$ins_id])) {
            $value = $requestData[$ins_id];
        } else {
            $value = $this->getValue();
        }

        return ['value' => $value];
    }

    public function renderInnerOutput($context = [])
    {
        return $this->humanize();
    }

    public function renderInput($context = [], $params = [])
    {
        global $prefs;

        if ($prefs['vuejs_enable'] === 'n') {
            return $this->renderTemplate('trackerinput/duration.tpl', $context, [
                'amounts' => $this->denormalize(),
                'units' => array_keys(self::getFactors())
            ]);
        }

        // vue.js integration
        $headerlib = TikiLib::lib('header');
        $value = $this->getValue();
        if (! $value) {
            $value = 0;
        }
        $composedFieldId = $this->getComposedId($params);

        if ($this->getComposedId($params)) {
            $applicationId = $this->getComposedId($params);
        } else {
            $applicationId = 'new';
        }

        // language=JavaScript
        $headerlib->add_jq_onready('
        console.log("registerApplication");
window.registerApplication({
    name: "@vue-mf/duration-picker-" + ' . json_encode($applicationId) . ',
    app: () => importShim("@vue-mf/duration-picker"),
    activeWhen: (location) => {
        let condition = true;
        return condition;
    },
    customProps: {
        durationData: {
            inputId: ' . json_encode($composedFieldId) . ',
            inputName: ' . json_encode($this->getInsertId()) . ',
            draft: ' . json_encode($_SESSION['duration_drafts'][$composedFieldId]) . ',
            value: ' . $value . ',
            units: ' . json_encode($this->enabledUnits()) . ',
            chronometer: ' . $this->getOption("chronometer") . '
        },
    },
});
onDOMElementRemoved("single-spa-application:@vue-mf/duration-picker-" + ' . json_encode($applicationId) . ', function () {
    window.unregisterApplication("@vue-mf/duration-picker-" + ' . json_encode($applicationId) . ');
});
');
        $appHtml = '<div id="single-spa-application:@vue-mf/duration-picker-' . $applicationId . '" class="wp-duration-picker"></div>';

        return $appHtml;
    }

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        $baseKey = $this->getBaseKey();

        $out = [
            $baseKey => $typeFactory->numeric($this->getValueInSeconds()),
            $baseKey . '_text' => $typeFactory->sortable($this->humanize()),
        ];

        return $out;
    }

    public function getProvidedFields(): array
    {
        $baseKey = $this->getBaseKey();
        return [$baseKey, "{$baseKey}_text"];
    }

    public function getProvidedFieldTypes(): array
    {
        $baseKey = $this->getBaseKey();
        return [
            $baseKey => 'numeric',
            "{$baseKey}_text" => 'sortable'
        ];
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

    public function getComposedId($params)
    {
        if (! $params['field']['fieldId'] || ! $params['field']['trackerId'] || ! $params['itemId']) {
            return null;
        } else {
            return "{$params['field']['fieldId']}{$params['field']['trackerId']}{$params['itemId']}";
        }
    }

    public function getTabularSchema()
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());
        $permName = $this->getConfiguration('permName');

        $parseInto = function (&$info, $value) use ($permName) {
            $info['fields'][$permName] = json_encode($this->parseDurationFormat($value));
        };

        $schema->addNew($permName, 'default')
            ->setLabel($this->getConfiguration('name'))
            ->setRenderTransform(function ($value) {
                $encoded = json_encode($this->parseDurationFormat($value));
                return $this->humanize($encoded);
            })
            ->setParseIntoTransform($parseInto)
            ;

        $schema->addNew($permName, 'raw-json')
            ->setLabel($this->getConfiguration('name'))
            ->setRenderTransform(function ($value) {
                $encoded = json_encode($this->parseDurationFormat($value));
                return $encoded;
            })
            ->setParseIntoTransform($parseInto)
            ;

        $schema->addNew($permName, 'number-seconds')
            ->setLabel($this->getConfiguration('name'))
            ->setRenderTransform(function ($value) {
                $encoded = json_encode($this->parseDurationFormat($value));
                return $this->getValueInSeconds($encoded);
            })
            ->setParseIntoTransform($parseInto)
            ;

        $schema->addNew($permName, 'number-minutes')
            ->setLabel($this->getConfiguration('name'))
            ->setRenderTransform(function ($value) {
                $encoded = json_encode($this->parseDurationFormat($value, 'seconds'));
                return intval($this->getValueInSeconds($encoded) / 60);
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = json_encode($this->parseDurationFormat($value, 'minutes'));
            })
            ;

        $schema->addNew($permName, 'number-hours')
            ->setLabel($this->getConfiguration('name'))
            ->setRenderTransform(function ($value) {
                $encoded = json_encode($this->parseDurationFormat($value, 'seconds'));
                return round($this->getValueInSeconds($encoded) / 3600, 2);
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = json_encode($this->parseDurationFormat($value, 'hours'));
            })
            ;

        $schema->addNew($permName, 'hh:mm:ss')
            ->setLabel($this->getConfiguration('name'))
            ->setRenderTransform(function ($value) {
                $struct = $this->parseDurationFormat($value);
                return str_pad($struct['days'] * 24 + $struct['hours'], 2, '0', STR_PAD_LEFT) . ':' .
                    str_pad($struct['minutes'], 2, '0', STR_PAD_LEFT) . ':' .
                    str_pad($struct['seconds'], 2, '0', STR_PAD_LEFT);
            })
            ->setParseIntoTransform($parseInto)
            ;

        $schema->addNew($permName, 'iso8601')
            ->setLabel($this->getConfiguration('name'))
            ->setRenderTransform(function ($value) {
                $encoded = json_encode($this->parseDurationFormat($value));
                return $this->iso8601($encoded);
            })
            ->setParseIntoTransform($parseInto)
            ;

        return $schema;
    }

    public function getFilterCollection()
    {
        // TODO
    }

    public static function getSortModeSql()
    {
        $parts = [];
        $factors = self::getFactors();
        foreach ($factors as $unit => $multiplier) {
            $parts[] = 'COALESCE(sttif.`value`->>"$.' . $unit . '", 0) * ' . $multiplier;
        }
        return 'IF(JSON_VALID(sttif.`value`), ' . implode('+', $parts) . ', 0)';
    }

    public static function getFactors()
    {
        return [
            'seconds' => 1,
            'minutes' => 60,
            'hours' => 3600,
            'days' => 86400,
            'weeks' => 604800,
            'months' => 2629746,
            'years' => 31556952,
        ];
    }

    public function getValueInSeconds($value = null)
    {
        $factors = self::getFactors();

        $result = 0;
        foreach ($this->denormalize($value) as $unit => $amount) {
            if (isset($factors[$unit])) {
                $result += floatval($amount) * $factors[$unit];
            } else {
                $result += floatval($amount);
            }
        }

        return $result;
    }

    private function denormalize($value = null)
    {
        if (is_null($value)) {
            $value = $this->getValue();
        }
        $value = json_decode($value, true);
        if (! is_array($value)) {
            $value = [];
        }
        return $value;
    }

    private function humanize($value = null)
    {
        $value = $this->denormalize($value);

        $output = '';
        foreach ($value as $unit => $amount) {
            // Remove s char if value is < 2
            if ($amount < 2) {
                $unit = substr($unit, 0, -1);
            }
            $output .= ($output ? ', ' : '') . "$amount " . tra($unit);
        }

        return $output;
    }

    private function iso8601($value = null)
    {
        $value = $this->denormalize($value);

        $output = 'P';
        foreach ($value as $unit => $amount) {
            if (in_array($unit, ['hours', 'minutes', 'seconds']) && ! strstr($output, 'T')) {
                $output .= 'T';
            }
            $output .= "$amount" . substr(ucfirst($unit), 0, 1);
        }

        return $output;
    }

    private function enabledUnits()
    {
        return array_reverse(
            array_values(
                array_filter(
                    array_keys(self::getFactors()),
                    function ($unit) {
                        return $this->getOption($unit);
                    }
                )
            )
        );
    }

    private function parseDurationFormat($data, $lowest = 'seconds')
    {
        $struct = [
            'years' => 0,
            'months' => 0,
            'weeks' => 0,
            'days' => 0,
            'hours' => 0,
            'minutes' => 0,
            'seconds' => 0,
        ];
        $data = trim($data);
        if (preg_match('/^[\d\.]+$/', $data)) {
            if ($lowest == 'seconds') {
                $seconds = intval($data);
            } else {
                $seconds = floatval($data);
            }
            $factors = self::getFactors();
            foreach ($factors as $unit => $factor) {
                if ($unit == $lowest) {
                    $seconds *= $factor;
                    break;
                }
            }
            foreach (array_reverse($factors) as $unit => $factor) {
                $struct[$unit] = floor($seconds / $factor);
                $seconds = $seconds % $factor;
            }
        } elseif ($parsed = @json_decode($data, true)) {
            $struct = $parsed;
        } else {
            $matches = [
                'years' => 'y|years?',
                'months' => 'mo|months?',
                'weeks' => 'w|weeks?',
                'days' => 'd|days?',
                'hours' => 'h|hours?',
                'minutes' => 'm|minutes?',
                'seconds' => 's|seconds?'
            ];
            foreach ($matches as $unit => $pattern) {
                if (preg_match('/(\d+)\s*(' . $pattern . ')/i', $data, $m)) {
                    $struct[$unit] = $m[1];
                }
            }
        }
        return array_filter($struct);
    }
}
