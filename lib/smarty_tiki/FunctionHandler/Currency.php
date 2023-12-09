<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

class Currency extends Base
{
    public function handle($params, Template $template)
    {
        global $prefs;

        extract($params);

        if (! isset($amount)) {
            return tra('Parameter amount is not specified.');
        }

        if ($amount === '') {
            return '';
        }

        if (! isset($sourceCurrency)) {
            return tra('Parameter sourceCurrency is not specified.');
        }

        $trk = \TikiLib::lib('trk');

        if (is_numeric($params['date'])) {
            $date = date('Y-m-d', $params['date']);
        } elseif (! empty($params['date'])) {
            $date = date('Y-m-d', strtotime($params['date']));
        } else {
            $date = date('Y-m-d');
        }

        $conversions = [];
        $rates = $trk->exchange_rates($date);

        $defaultCurrency = array_search(1, $rates);
        if (empty($defaultCurrency)) {
            $defaultCurrency = 'USD';
        }

        if (empty($sourceCurrency)) {
            $sourceCurrency = $defaultCurrency;
        }

        // convert amount to default currency before converting to other currencies
        $defaultAmount = $amount;
        if ($sourceCurrency != $defaultCurrency && ! empty($rates[$sourceCurrency])) {
            $defaultAmount = (float)$defaultAmount / (float)$rates[$sourceCurrency];
            $conversions[$defaultCurrency] = $defaultAmount;
        }
        foreach ($rates as $currency => $rate) {
            if ($currency != $sourceCurrency) {
                $conversions[$currency] = (float)$rate * (float)$defaultAmount;
            }
        }

        if (empty($locale)) {
            $locale = $prefs['tracker_currency_default_locale'];
        }

        // NOTE: php 7.4+ (including 8.0) has a serious memory leak issue (https://bugs.php.net/bug.php?id=79519 and https://bugs.php.net/bug.php?id=76982)
        // which makes $smarty->fetch here leak a lot - e.g. indexing 50K tracker items requiring 5GB+ of RAM
        // build the output inline here for now instead of fetching currency_output.tpl and switch to the tpl once the issue is resolved

        $id = uniqid();

        $export = isset($csv) && $csv;

        $out = '';
        if (! $export) {
            $out = '<div style="display:inline" id="currency_output_' . $id . '" class="currency_output">';
        }
        if ($prepend) {
            if ($export) {
                $out .= ' ' . smarty_modifier_escape($prepend);
            } else {
                $out .= '<span class="formunit">' . smarty_modifier_escape($prepend) . '</span>';
            }
        }
        if ($sourceCurrency) {
            $currency = $sourceCurrency;
        } elseif (empty($defaultCurrency)) {
            $currency = 'USD';
        } else {
            $currency = $defaultCurrency;
        }
        if (empty($symbol)) {
            $part1a = '%(!#10n';
            $part1b = '%(#10n';
        } else {
            $part1a = '%(!#10';
            $part1b = '%(#10';
        }
        if ((isset($reloff) and $reloff > 0) and ($allSymbol != 1)) {
            $format = $part1a . $symbol;
            $out .= smarty_modifier_money_format($amount, $locale, $currency, $format, 0);
        } else {
            $format = $part1b . $symbol;
            $out .= smarty_modifier_money_format($amount, $locale, $currency, $format, 1);
        }
        if ($append) {
            if ($export) {
                $out .= ' ' . smarty_modifier_escape($append);
            } else {
                $out .= '<span class="formunit">' . smarty_modifier_escape($append) . '</span>';
            }
        }
        if ($export) {
            return $out;
        }

        $out .= '</div>';
        if ($conversions) {
            $out .= '
        <div class="d-none currency_output_' . $id . '" style="position:absolute; z-index: 1000;">
            <div class="modal-content">
                <div class="modal-body">';
            foreach ($conversions as $currency => $amount) {
                if ((isset($reloff) and $reloff > 0) and ($allSymbol != 1)) {
                    $format = $part1a . $symbol;
                    $out .= smarty_modifier_money_format($amount, $locale, $currency, $format, 0);
                } else {
                    $format = $part1b . $symbol;
                    $out .= smarty_modifier_money_format($amount, $locale, $currency, $format, 1);
                }
                $out .= '<br>';
            }
            $out .= '
                </div>
            </div>
        </div>';
        }

        return $out;
    }
}
