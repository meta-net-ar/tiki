<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/**
 * @param $params
 *               - fieldname: name attribute for the input element
 *               - date: date to display in the input field; default is now
 *               - enddate: second date to display in the input field (for date ranges)
 *               - endfieldname: name attribute for the second input element (for date ranges)
 *               - showtime: show timepicker in addition to date
 *               - goto:
 *               - notAfter:
 *               - notBefore:
 *               - timezone
 *               - timezoneFieldname
 *
 * @param $smarty
 *
 * @return string
 * @throws Exception
 */
class JsCalendar extends Base
{
    public function handle($params, Template $template)
    {
        global $prefs;
        $tikilib = \TikiLib::lib('tiki');
        $headerlib = \TikiLib::lib('header');

        $headerlib->add_js_module("import '@vue-widgets/el-date-picker';");

        if (! isset($params['showtime'])) {
            $params['showtime'] = 'y';
        }

        $fieldName = $params['fieldname'] ?? '';
        $enableTimezonePicker = ($params['showtimezone'] ?? '') === 'y' ? "true" : "false";
        $enableTimePicker = $params['showtime'] === 'y' ? 1 : 0;
        $goto = $params['goto'] ?? '';
        $id = $params['id'] ?? "date-picker-" . uniqid();
        $endfieldname = $params['endfieldname'] ?? '';
        $enddate = $params['enddate'] ?? '';
        $timezoneFieldname = $params['timezoneFieldname'] ?? '';
        $minuteStep = isset($params['minutestep']) ? (int)$params['minutestep'] : 1;
        $enforceStep = isset($params['enforcestep']) ? (int)$params['enforcestep'] : 0;

        if (! isset($params['timezone'])) {
            $params['timezone'] = $tikilib->get_display_timezone();
        }

        $type = "date";
        if ($endfieldname && $enableTimePicker) {
            $type = "datetimerange";
        } elseif ($endfieldname) {
            $type = "daterange";
        } elseif ($enableTimePicker) {
            $type = "datetime";
        }

        $headerlib->add_js_module(<<<JS
            if (typeof handleDatePicker === 'undefined') {
                import('@tiki/ui-utils').then(({ handleDatePicker }) => {
                    handleDatePicker('#{$id}', {
                        fieldName: '{$fieldName}',
                        endFieldName: '{$endfieldname}',
                        date: '{$params['date']}',
                        endDate: '{$enddate}',
                        timezoneFieldName: '{$timezoneFieldname}',
                        goto: '{$goto}',
                    })
                });
            } else {
                handleDatePicker('#{$id}', {
                    fieldName: '{$fieldName}',
                    endFieldName: '{$endfieldname}',
                    date: '{$params['date']}',
                    endDate: '{$enddate}',
                    timezoneFieldName: '{$timezoneFieldname}',
                    goto: '{$goto}',
                })
            }
        JS);

        $language = $tikilib->get_language();
        $format = strpos($type, 'time') !== false ? $prefs['short_date_format_js'] . ' ' . $prefs['short_time_format_js'] : $prefs['short_date_format_js'];

        return <<<HTML
            <el-date-picker type="{$type}" custom-timezone="{$enableTimezonePicker}" id="{$id}" timezone="{$params['timezone']}" language="{$language}" format="{$format}" minutestep="{$minuteStep}" enforcestep="{$enforceStep}" />
        HTML;
    }
}
