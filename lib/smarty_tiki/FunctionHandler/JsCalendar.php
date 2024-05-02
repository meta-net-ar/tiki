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
 *               - id: id attribute for the input element
 *               - fieldname: name attribute for the input element
 *               - date: date to display in the input field; default is now
 *               - showtime: show timepicker in addition to date
 *               - goto:
 *               - notAfter:
 *               - notBefore:
 *               - isutc:
 *               - timezone
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
        $headerlib = \TikiLib::lib('header');
        $tikilib = \TikiLib::lib('tiki');
        $smarty = \TikiLib::lib('smarty');

        $uiCalendarInstance = uniqid();

        if (! isset($params['id'])) {
            $params['id'] = 'uiCal_' . $uiCalendarInstance;
        }
        $id = '';
        $selector = "#$id";
        if (isset($params['fieldname'])) {
            $name = ' name="' . $params['fieldname'] . '"';
        } else {
            $name = '';
        }
        if (! isset($params['date'])) {
            // if date is provided empty then show a blank date (for filters)
            $params['date'] = $tikilib->now;
        }
        $datepicker_options = '{ altField: "#' . $params['id'] . '", onClose: function(dateText, inst) {' .
            '$(inst.settings.altField).removeClass("isDatepicker");' .
            '$.datepickerAdjustAltField("' . ((! isset($params['showtime']) || $params['showtime'] === 'n') ? 'datepicker' : 'datetimepicker') . '", inst);' .
            '}';
        if (! empty($params['goto'])) {
            $datepicker_options .= ', onSelect: function(dateText, inst) {
            var url = "' . $params['goto'] . '".replace("%s",$("#' . $params['id'] . '").val()/1000);
            if (url.indexOf("?") > -1) {
                url += "&";
            } else {
                url += "?";
            }
            url += "tzname=" + $(this).closest("form").find("input[name=tzname]").val();
            url += "&tzoffset=" + $(this).closest("form").find("input[name=tzoffset]").val();
            window.location = url;
        }';
        }

        if (! empty($params['notAfter'])) {
            $datepicker_options .= ', onSelect: function (selectedDateTime) {
        $("' . $params['notAfter'] . '").find(".isDatepicker").datetimepicker("option", "minDate", selectedDateTime); }';
        }

        if (! empty($params['notBefore'])) {
            $datepicker_options .= ', onSelect: function (selectedDateTime) {
        $("' . $params['notBefore'] . '").find(".isDatepicker").datetimepicker("option", "maxDate", selectedDateTime); }';
        }

        static $datepicker_options_common;

        if (! $datepicker_options_common) {
            $calendarlib = \TikiLib::lib('calendar');
            $first = $calendarlib->firstDayofWeek();

            if (! is_numeric($first) || ! in_array($first, [0, 1, 2, 3, 4, 5, 6])) {
                $first = 0;
            }

            $datepicker_options_common .= ', firstDay: ' . $first;
            $datepicker_options_common .= ", closeText: '" . smarty_function_jscalendar_tra('Done') . "'";
            $datepicker_options_common .= ", prevText: '" . smarty_function_jscalendar_tra('Prev') . "'";
            $datepicker_options_common .= ", nextText: '" . smarty_function_jscalendar_tra('Next') . "'";
            $datepicker_options_common .= ", currentText: '" . smarty_function_jscalendar_tra('Today') . "'";
            $datepicker_options_common .= ", weekHeader: '" . smarty_function_jscalendar_tra('Wk') . "'";

            $datepicker_options_common .= ", dayNames: ['" .
                smarty_function_jscalendar_tra('Sunday') . "','" .
                smarty_function_jscalendar_tra('Monday') . "','" .
                smarty_function_jscalendar_tra('Tuesday') . "','" .
                smarty_function_jscalendar_tra('Wednesday') . "','" .
                smarty_function_jscalendar_tra('Thursday') . "','" .
                smarty_function_jscalendar_tra('Friday') . "','" .
                smarty_function_jscalendar_tra('Saturday') . "']";

            $datepicker_options_common .= ", dayNamesMin: ['" .
                smarty_function_jscalendar_tra('Su') . "','" .
                smarty_function_jscalendar_tra('Mo') . "','" .
                smarty_function_jscalendar_tra('Tu') . "','" .
                smarty_function_jscalendar_tra('We') . "','" .
                smarty_function_jscalendar_tra('Th') . "','" .
                smarty_function_jscalendar_tra('Fr') . "','" .
                smarty_function_jscalendar_tra('Sa') . "']";

            $datepicker_options_common .= ", dayNamesShort: ['" .
                smarty_function_jscalendar_tra('Sun') . "','" .
                smarty_function_jscalendar_tra('Mon') . "','" .
                smarty_function_jscalendar_tra('Tue') . "','" .
                smarty_function_jscalendar_tra('Wed') . "','" .
                smarty_function_jscalendar_tra('Thu') . "','" .
                smarty_function_jscalendar_tra('Fri') . "','" .
                smarty_function_jscalendar_tra('Sat') . "']";

            $datepicker_options_common .= ", monthNames: ['" .
                smarty_function_jscalendar_tra('January') . "','" .
                smarty_function_jscalendar_tra('February') . "','" .
                smarty_function_jscalendar_tra('March') . "','" .
                smarty_function_jscalendar_tra('April') . "','" .
                smarty_function_jscalendar_tra('May') . "','" .
                smarty_function_jscalendar_tra('June') . "','" .
                smarty_function_jscalendar_tra('July') . "','" .
                smarty_function_jscalendar_tra('August') . "','" .
                smarty_function_jscalendar_tra('September') . "','" .
                smarty_function_jscalendar_tra('October') . "','" .
                smarty_function_jscalendar_tra('November') . "','" .
                smarty_function_jscalendar_tra('December') . "']";

            $datepicker_options_common .= ", monthNamesShort: ['" .
                smarty_function_jscalendar_tra('Jan') . "','" .
                smarty_function_jscalendar_tra('Feb') . "','" .
                smarty_function_jscalendar_tra('Mar') . "','" .
                smarty_function_jscalendar_tra('Apr') . "','" .
                smarty_function_jscalendar_tra('May') . "','" .
                smarty_function_jscalendar_tra('Jun') . "','" . //get_strings tra('Jun')
                smarty_function_jscalendar_tra('Jul') . "','" . //get_strings tra('Jul')
                smarty_function_jscalendar_tra('Aug') . "','" .
                smarty_function_jscalendar_tra('Sep') . "','" .
                smarty_function_jscalendar_tra('Oct') . "','" .
                smarty_function_jscalendar_tra('Nov') . "','" .
                smarty_function_jscalendar_tra('Dec') . "']";
            $datepicker_options_common .= '}';
        }

        $datepicker_options .= $datepicker_options_common;

        $html = '<input type="hidden" id="' . $params['id'] . '"' . $name . ' value="' . $params['date'] . '" class="isDatepicker" . aria-label="date picker">';
        $html .= '<input type="hidden" id="' . $params['id'] . '_tzoffset" name="tzoffset" value="">';
        $html .= '<input type="hidden" id="' . $params['id'] . '_tzname" name="tzname" value="">';
        $headerlib->add_jq_onready('$("input#' . $params['id'] . '_tzoffset").val((new Date(' . (int)$params['date'] . '*1000)).getTimezoneOffset());');
        $headerlib->add_jq_onready('try { $("input#' . $params['id'] . '_tzname").val(Intl.DateTimeFormat().resolvedOptions().timeZone); } catch(e) {}');
        if (isset($params['isutc']) && $params['isutc'] && intval($params['date']) > 0) {
            $headerlib->add_jq_onready('$("#' . $params['id'] . '").val(' . (int)$params['date'] . ' + parseInt($("input#' . $params['id'] . '_tzoffset").val())*60);');
        }
        $html .= '<input type="text" class="form-control isDatepicker" id="' . $params['id'] . '_dptxt" value="" . aria-label="date picker">'; // text version of datepicker date
        $headerlib->add_jq_onready('$("#' . $params['id'] . '_dptxt").on("change", function(e){' .
            'var inst = $.datepicker._getInst(this);' .
            '$.datepickerAdjustAltField("' . (! isset($params['showtime']) || $params['showtime'] === 'n' ? 'datepicker' : 'datetimepicker') . '", inst);' .
            '})');
        $headerlib->add_jq_onready('$("#' . $params['id'] . '").on("change", function(e){' .
            '$("input#' . $params['id'] . '_tzoffset").val((new Date(parseInt($(this).val())*1000)).getTimezoneOffset());' .
            '})');

        $display_tz = ! empty($params['timezone']) ? $params['timezone'] : $tikilib->get_display_timezone();
        if ($display_tz == '') {
            $display_tz = 'UTC';
        }
        if (strpos($display_tz, 'Etc/GMT+') !== false) {
            $display_tz = str_replace('Etc/GMT+', 'GMT-', $display_tz);
        } elseif (strpos($display_tz, 'Etc/GMT-') !== false) {
            $display_tz = str_replace('Etc/GMT-', 'GMT+', $display_tz);
        }

        // TODO use a parsed version of $prefs['short_date_format']
        // Note: JS timestamp is in milliseconds - php is seconds
        // Note: adding local browser offset if php date is in UTC, see JsCalendar tracker field
        if (! isset($params['showtime']) || $params['showtime'] === 'n') {
            $command = 'datepicker';
            $js_val = empty($params['date']) ? '""' : '$.datepicker.formatDate( "' .
                $prefs['short_date_format_js'] . '", new Date(' . $params['date'] . '* 1000 + ' . (isset($params['isutc']) && $params['isutc'] && intval($params['date']) > 0 ? 'parseInt($("input#' . $params['id'] . '_tzoffset").val())*60*1000' : '0') . '))';
            $headerlib->add_jq_onready(
                '$("#' . $params['id'] . '_dptxt").val(' . $js_val . ').tiki("' .
                    $command . '", "jscalendar", ' . $datepicker_options . ');'
            );
            $timeclass = '';
        } else {
            // add timezone info if showing the time
            $html .= '<div class="description">' . tra('Time zone') . ': ' . $display_tz . '</div>';
            // datetime picker

            $command = 'datetimepicker';

            $js_val1 = empty($params['date']) ? '' : '
var dt = new Date(' . $params['date'] . '* 1000 + ' . (isset($params['isutc']) && $params['isutc'] ? 'parseInt($("input#' . $params['id'] . '_tzoffset").val())*60*1000' : '0') . ');
var tm = { hour: dt.getHours(), minute: dt.getMinutes(), second: dt.getSeconds() };
';
            $js_val2 = empty($params['date']) ? '""' : '$.datepicker.formatDate( "' .
                $prefs['short_date_format_js'] . '", dt) + " " + $.datepicker.formatTime("' .
                $prefs['short_time_format_js'] . '", tm)';

            $headerlib->add_jq_onready(
                $js_val1 . '$("#' . $params['id'] . '_dptxt").val(' . $js_val2 . ').tiki("' .
                    $command . '", "jscalendar", ' . $datepicker_options . ');'
            );

            $timeclass = ' datetime';
        }

        $icon = smarty_function_icon(['name' => 'calendar'], $smarty->getEmptyInternalTemplate());
        $headerlib->add_jq_onready("$('#" . $params['id'] . "').closest('div.jscal').find(' button.ui-datepicker-trigger').empty().append('$icon').addClass('btn btn-sm btn-link').css({'padding' : '0px', 'font-size': '16px'});");

        return '<div class="jscal' . $timeclass . '" style="margin-bottom:10px">' . $html . '</div>';
    }
}

function smarty_function_jscalendar_tra($str)
{
    return str_replace("'", "\\'", tra($str));
}
