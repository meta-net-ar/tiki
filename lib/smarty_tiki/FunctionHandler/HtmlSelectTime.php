<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/**
 * Smarty {html_select_time} function handler
 *
 * Type:     function<br>
 * Name:     html_select_time<br>
 * Purpose:  Prints the dropdowns for time selection
 * @link http://smarty.php.net/manual/en/language.function.html.select.time.php {html_select_time}
 *          (Smarty online manual)
 * @param array
 * @param \Smarty\Template
 * @return string
 * @uses smarty_make_timestamp()
 */
class HtmlSelectTime extends Base
{
    public function handle($params, Template $template)
    {
        global $tikilib;

        /* Default values. */
        $prefix             = "Time_";
        $time               = time();
        $display_hours      = true;
        $display_minutes    = true;
        $display_seconds    = true;
        $display_meridian   = true;
        $use_24_hours       = true;
        $minute_interval    = 1;
        $second_interval    = 1;
        $hour_minmax        = '0-23';
        $tikidate = new \TikiDate();
        /* Should the select boxes be part of an array when returned from PHP?
       e.g. setting it to "birthday", would create "birthday[Hour]",
       "birthday[Minute]", "birthday[Seconds]" & "birthday[Meridian]".
       Can be combined with prefix. */
        $field_array        = null;
        $all_extra          = null;
        $hour_extra         = null;
        $minute_extra       = null;
        $second_extra       = null;
        $meridian_extra     = null;
        $hour_empty = null;
        $minute_empty = null;
        $second_empty = null;
        $all_empty = null;
        $class = 'form-control date';

        extract($params);
        if (! empty($all_empty)) {
            $hour_empty = $minute_empty = $second_empty = $all_empty;
        }

        if (! isset($time) or ! $time) {
            $time = $tikilib->now;
        } elseif (is_string($time) && strpos($time, ':') !== false) {
            $e = explode(':', $time, 3);
            $time = $tikilib->make_time(
                isset($e[0]) ? $e[0] : 0,
                isset($e[1]) ? $e[1] : 0,
                isset($e[2]) ? $e[2] : 0,
                $tikilib->date_format('%m'),
                $tikilib->date_format('%d'),
                $tikilib->date_format('%Y')
            );
        }
        if (empty($hour_minmax) || ! preg_match('/^[0-2]?[0-9]-[0-2]?[0-9]$/', $hour_minmax)) {
            $hour_minmax = '0-23';
        }
        //only needed for end_ and the static variable in the date_format functions seem to cause problems without the if
        if ($prefix == 'end_') {
            $time_hr24 = \TikiLib::date_format('%H%M%s', $time);
        }

        $html_result = '';

        if ($display_hours) {
            if ($use_24_hours) {
                list($hour_min, $hour_max) = explode('-', $hour_minmax);
                $hours = range(($hour_min == 24 ? 0 : $hour_min), ($hour_max == 0 || $hour_max == 24 ? 23 : $hour_max));
                $hour_fmt = '%H';
                $latest = 23;
                //12-hour clock
            } else {
                $hours = range(1, 12);
                $hour_fmt = '%I';
                $latest = 11;
            }
            for ($i = 0, $for_max = count($hours); $i < $for_max; $i++) {
                $hours[$i] = sprintf('%02d', $hours[$i]);
            }

            if ($prefix == 'end_' && ($time_hr24 == '000000')) {
                $selected = $latest;
            } elseif ($prefix == 'duration_' || $prefix == 'startday_' || $prefix == 'endday_') {
                if ($use_24_hours) {
                    $selected = floor($time / (60 * 60));
                } else {
                    $selected = date('h', strtotime(floor($time / (60 * 60)) . ':00 '));
                }
            } else {
                $selected = $time == '--' ? $hour_empty : \TikiLib::date_format($hour_fmt, $time);
            }

            $html_result .= '<div class="col-sm-3 ps-0"><select class="' . $class . '" name=';

            if (null !== $field_array) {
                $html_result .= '"' . $field_array . '[' . $prefix . 'Hour]"';
            } else {
                $html_result .= '"' . $prefix . 'Hour"';
            }

            if (null !== $hour_extra) {
                $html_result .= ' ' . $hour_extra;
            }

            if (null !== $all_extra) {
                $html_result .= ' ' . $all_extra;
            }

            $html_result .= '>' . "\n";

            if (! empty($hour_empty)) {
                $hours = array_merge([$hour_empty == ' ' ? '' : $hour_empty], $hours);
            }

            $html_result .= smarty_function_html_options(
                [
                    'output'        => $hours,
                    'values'        => $hours,
                    'selected'      => $selected,
                    'print_result'  => false
                ],
                $template
            );

            $html_result .= "</select></div>\n";
        }

        if ($display_minutes) {
            $all_minutes = range(0, 59);
            for ($i = 0, $for_max = count($all_minutes); $i < $for_max; $i += $minute_interval) {
                $minutes[] = sprintf('%02d', $all_minutes[$i]);
            }

            if ($minute_interval > 1) {
                $minutes[] = 59;
            }

            if ($time !== '--') {
                $tikidate->setDate($time);
                $minute = $tikidate->format('%M', true);
            } else {
                $minute = '00';
            }
            if (in_array($minute, $minutes) == false) {
                for ($i = 0, $for_max = count($minutes); $i < $for_max; $i++) {
                    if (
                        (int) $minute > (int) $minutes[$i] &&
                        (
                            (int) $minute < (int) $minutes[$i + 1] ||
                            empty($minutes[$i + 1])
                        )
                    ) {
                        array_splice($minutes, $i + 1, 0, $minute);
                        $i = $for_max;
                    }
                }
            }

            if ($prefix == 'end_' && ($time_hr24 == '000000' || $minute == 59)) {
                $selected = 59;
            } else {
                if ($time == '--') {
                    $selected = $minute_empty;
                } elseif (in_array($minute, $minutes)) {
                    $selected = $minute;
                } else {
                    $tikidate->setDate($time);
                    $selected = (int)(floor($tikidate->format('%M', true) / $minute_interval) * $minute_interval);
                }
            }

            //minute intervals less than 10 are followed by a '0', here we ensure that they are selectable
            if (strlen($selected) == 1) {
                $selected = '0' . $selected;
            }

            $html_result .= '<div class="col-sm-3 pe-0"><select class="' . $class . '" name=';
            if (null !== $field_array) {
                $html_result .= '"' . $field_array . '[' . $prefix . 'Minute]"';
            } else {
                $html_result .= '"' . $prefix . 'Minute"';
            }
            if (null !== $minute_extra) {
                $html_result .= ' ' . $minute_extra;
            }
            if (null !== $all_extra) {
                $html_result .= ' ' . $all_extra;
            }
            $html_result .= '>' . "\n";

            if (! empty($minute_empty)) {
                $minutes = array_merge([$minute_empty == ' ' ? '' : $minute_empty], $minutes);
            }

            $html_result .= smarty_function_html_options(
                [
                    'output'        => $minutes,
                    'values'        => $minutes,
                    'selected'      => $selected,
                    'print_result'  => false
                ],
                $template
            );
            $html_result .= "</select></div>\n";
        }

        if ($display_seconds) {
            $all_seconds = range(0, 59);
            for ($i = 0, $for_max = count($all_seconds); $i < $for_max; $i += $second_interval) {
                $seconds[] = sprintf('%02d', $all_seconds[$i]);
            }

            if ($second_interval > 1) {
                $seconds[] = 59;
            }

            $tikidate->setDate($time);

            if ($prefix == 'end_' && ($time_hr24 == '000000' || $tikidate->format('%M', true) == 59)) {
                $selected = 59;
            } else {
                $selected = $time == '--' ? $second_empty : (int)(floor($tikidate->format('%S', true) / $second_interval) * $second_interval);
            }

            $html_result .= '<div class="col"><select class="' . $class . '" name=';

            if (null !== $field_array) {
                $html_result .= '"' . $field_array . '[' . $prefix . 'Second]"';
            } else {
                $html_result .= '"' . $prefix . 'Second"';
            }

            if (null !== $second_extra) {
                $html_result .= ' ' . $second_extra;
            }

            if (null !== $all_extra) {
                $html_result .= ' ' . $all_extra;
            }

            $html_result .= '>' . "\n";

            if (! empty($seconde_empty)) {
                $secondes = array_merge([$seconde_empty == ' ' ? '' : $seconde_empty], $secondes);
            }

            $html_result .= smarty_function_html_options(
                [
                    'output'        => $seconds,
                    'values'        => $seconds,
                    'selected'      => $selected,
                    'print_result'  => false
                ],
                $template
            );
            $html_result .= "</select></div>\n";
        }

        if (! $use_24_hours) {
            $html_result .= '<div class="col-sm-3 ps-0"><select class="' . $class . '" name=';
            if (null !== $field_array) {
                $html_result .= '"' . $field_array . '[' . $prefix . 'Meridian]"';
            } else {
                $html_result .= '"' . $prefix . 'Meridian"';
            }

            if (null !== $meridian_extra) {
                $html_result .= ' ' . $meridian_extra;
            }
            if (null !== $all_extra) {
                $html_result .= ' ' . $all_extra;
            }
            $html_result .= '>' . "\n";

            $html_result .= smarty_function_html_options(
                [
                    'output'        => ['AM', 'PM'],
                    'values'        => ['am', 'pm'],
                    'selected'      => \TikiLib::date_format('%p', $time),
                    'print_result'  => false
                ],
                $template
            );
            $html_result .= "</select></div>\n";
        }
        $html_result = "<div class='row html-select-time'>$html_result</div>";

        $html_result = '<span dir="ltr">' . $html_result . '</span>';
        return $html_result;
    }
}
