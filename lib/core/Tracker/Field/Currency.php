<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Handler class for currency field
 *
 * Letter key: ~b~
 *
 */
class Tracker_Field_Currency extends \Tracker\Field\AbstractField implements \Tracker\Field\SynchronizableInterface, \Tracker\Field\ExportableInterface, \Tracker\Field\FilterableInterface
{
    public static function getManagedTypesInfo(): array
    {
        return [
            'b' => [
                'name' => tr('Currency'),
                'description' => tr('Provide a single field to enter numeric amount and choose a currency. Prepended or appended values may be alphanumeric.'),
                'help' => 'Currency-Amount-Tracker-Field',
                'prefs' => ['trackerfield_currency'],
                'tags' => ['basic'],
                'default' => 'n',
                'supported_changes' => ['d', 'D', 'R', 'M', 't', 'a', 'n', 'q', 'b'],
                'params' => [
                    'samerow' => [
                        'name' => tr('Same Row'),
                        'description' => tr('Displays the next field on the same line.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'default' => 1,
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'legacy_index' => 0,
                    ],
                    'size' => [
                        'name' => tr('Display Size'),
                        'description' => tr('Visible size of the field, in characters. Does not affect the numeric length.'),
                        'filter' => 'int',
                        'default' => 15,
                        'legacy_index' => 1,
                    ],
                    'prepend' => [
                        'name' => tr('Prepend'),
                        'description' => tr('Text to be displayed in front of the currency amount.'),
                        'filter' => 'text',
                        'default' => '',
                        'legacy_index' => 2,
                    ],
                    'append' => [
                        'name' => tr('Append'),
                        'description' => tr('Text to be displayed after the numeric value.'),
                        'filter' => 'text',
                        'default' => '',
                        'legacy_index' => 3,
                    ],
                    'locale' => [
                        'name' => tr('Override Locale'),
                        'description' => tr('Set locale for currency formatting, for example en_US or en_US.UTF-8 or en_US.ISO-8559-1. Default is defined in admin tracker settings.'),
                        'filter' => 'text',
                        'default' => '',
                        'legacy_index' => 4,
                    ],
                    'currency' => [
                        'name' => tr('Currency'),
                        'description' => tr('A custom alphanumeric currency code. Not needed if locale is set and a standard code is desired. Default is USD.'),
                        'filter' => 'alpha',
                        'default' => 'USD',
                        'legacy_index' => 5,
                    ],
                    'symbol' => [
                        'name' => tr('Symbol'),
                        'description' => tr('Set whether the currency code (for example USD) or symbol (for example $) will display. Defaults to symbol.'),
                        'filter' => 'alpha',
                        'default' => 'n',
                        'options' => [
                            'i' => tr('Currency code'),
                            'n' => tr('Currency symbol'),
                        ],
                        'legacy_index' => 6,
                    ],
                    'all_symbol' => [
                        'name' => tr('First or all'),
                        'description' => tr('Set whether the currency code or symbol will be displayed against all amounts or only the first amount.'),
                        'filter' => 'int',
                        'default' => 1,
                        'options' => [
                            0 => tr('First item only'),
                            1 => tr('All'),
                        ],
                        'legacy_index' => 7,
                    ],
                    'dateFieldId' => [
                        'name' => tr('Date Field ID'),
                        'description' => tr('Currency conversions will be performed based on a date in another field in this tracker rather than the current date. This is usually the date of the transaction.'),
                        'filter' => 'int',
                        'legacy_index' => 8,
                        'profile_reference' => 'tracker_field',
                        'parent' => 'input[name=trackerId]',
                        'parentkey' => 'tracker_id',
                        'sort_order' => 'position_nasc',
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = [])
    {
        $ins_id = $this->getInsertId();
        if (isset($requestData[$ins_id])) {
            $amount = $requestData[$ins_id];
            $currency = $requestData[$ins_id . '_currency'] ?? '';
        } elseif (preg_match('/^([-\d\.]*)([A-Za-z]*)?$/', $this->getValue(), $m)) {
            $amount = $m[1];
            $currency = $m[2];
        } else {
            $amount = $this->getValue();
            $currency = '';
        }

        $dateFieldId = $this->getOption('dateFieldId');
        if ($dateFieldId) {
            $date = TikiLib::lib('trk')->get_item_value($this->getConfiguration('trackerId'), $this->getItemId(), $dateFieldId);
        } else {
            $date = null;
        }

        return [
            'value' => $amount . $currency,
            'amount' => $amount,
            'currency' => $currency,
            'date' => $date,
        ];
    }

    public function renderInnerOutput($context = [])
    {
        global $prefs;
        $data = $this->getFieldData();
        $locale = $this->getOption('locale');
        if (empty($locale)) {
            $locale = $prefs['tracker_currency_default_locale'];
        }
        $currency = $data['currency'] ?? $this->getOption('currency', 'USD');
        $symbol = $this->getOption('symbol');
        if (empty($symbol)) {
            $part1a = '%(!#10n';
            $part1b = '%(#10n';
        } else {
            $part1a = '%(!#10';
            $part1b = '%(#10';
        }
        $smarty = TikiLib::lib('smarty');
        if (! empty($context['reloff']) && $this->getOption('all_symbol') != 1) {
            $format = $part1a . $symbol;
            return smarty_modifier_money_format($data['amount'], $locale, $currency, $format, 0);
        } else {
            $format = $part1b . $symbol;
            return smarty_modifier_money_format($data['amount'], $locale, $currency, $format, 1);
        }
    }

    public function renderOutput($context = [])
    {
        $smarty = TikiLib::lib('smarty');

        $data = $this->getFieldData();

        return smarty_function_currency(
            [
                'amount' => $data['amount'],
                'sourceCurrency' => $data['currency'],
                'date' => $data['date'],
                'prepend' => $this->getOption('prepend'),
                'append' => $this->getOption('append'),
                'locale' => $this->getOption('locale'),
                'defaultCurrency' => $this->getOption('currency'),
                'symbol' => $this->getOption('symbol'),
                'allSymbol' => $this->getOption('all_symbol'),
                'reloff' => $context['reloff'] ?? null,
                'csv' => $context['list_mode'] === 'csv'
            ],
            $smarty->getEmptyInternalTemplate()
        );
    }

    public function renderInput($context = [])
    {
        $data = $this->getAvailableCurrencies();
        return $this->renderTemplate('trackerinput/currency.tpl', $context, $data);
    }

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        $value = $this->getValue();
        $data = $this->getFieldData();
        $defaultAmount = Services_Tracker_Utilities::convertToDefaultCurrency($data);
        $baseKey = $this->getBaseKey();

        $out = [
            $baseKey => $typeFactory->plaintext($value),
            "{$baseKey}_base" => $typeFactory->numeric($defaultAmount),
            "{$baseKey}_numeric" => $typeFactory->numeric($data['amount']),
        ];
        return $out;
    }

    public function getProvidedFields(): array
    {
        $baseKey = $this->getBaseKey();
        return [$baseKey, "{$baseKey}_base", "{$baseKey}_numeric"];
    }

    public function getProvidedFieldTypes(): array
    {
        $baseKey = $this->getBaseKey();
        return [
            $baseKey => 'plaintext',
            "{$baseKey}_base" => 'numeric',
            "{$baseKey}_numeric" => 'numeric',
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

    public function getTabularSchema()
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());

        $permName = $this->getConfiguration('permName');
        $schema->addNew($permName, 'default')
            ->setLabel($this->getConfiguration('name'))
            ->setRenderTransform(function ($value, $extra) {
                if (is_array($value)) {
                    list ($amount, $currency) = $value;
                } elseif (preg_match('/^([-\d\.]*)([A-Za-z]*)?$/', $value, $m)) {
                    $amount = $m[1];
                    $currency = $m[2];
                } else {
                    $amount = $value;
                    $currency = '';
                }
                if (! empty($extra['allow_multiple'])) {
                    return [$amount, $currency];
                } else {
                    return $amount . $currency;
                }
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                if (is_array($value)) {
                    $info['fields'][$permName] = implode('', $value);
                } else {
                    $info['fields'][$permName] = $value;
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
        $data = $this->getAvailableCurrencies();
        $data['size'] = $this->getOption('size');

        $filters->addNew($permName, 'range')
            ->setLabel($name)
            ->setControl(new Tracker\Filter\Control\CurrencyRange("tf_{$permName}_range", $data))
            ->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
                if ($control->hasValue()) {
                    $data = $this->getFieldData();
                    $data['amount'] = $control->getFrom();
                    $data['currency'] = $control->getFromCurrency();
                    $from = round(Services_Tracker_Utilities::convertToDefaultCurrency($data), 2);
                    $data['amount'] = $control->getTo();
                    $data['currency'] = $control->getToCurrency();
                    $to = round(Services_Tracker_Utilities::convertToDefaultCurrency($data), 2);
                    $query->filterNumericRange($from, $to, "{$baseKey}_base");
                }
            });

        return $filters;
    }

    private function getAvailableCurrencies()
    {
        global $prefs;
        $data = [];

        $trk = TikiLib::lib('trk');
        if ($prefs['tracker_system_currency'] != 'y') {
            $data['error'] = tr('Currency system tracker not enabled.');
        } elseif (! $prefs['tracker_system_currency_tracker']) {
            $data['error'] = tr('Currency system tracker not configured: missing tracker selection.');
        } elseif (! $prefs['tracker_system_currency_currency']) {
            $data['error'] = tr('Currency system tracker not configured: missing currency field.');
        } else {
            $data['currencies'] = $trk->list_tracker_field_values($prefs['tracker_system_currency_tracker'], $prefs['tracker_system_currency_currency']);
            sort($data['currencies']);
        }

        return $data;
    }
}
