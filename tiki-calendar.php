<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\TikiDate;

$inputConfiguration = [
    [
        'staticKeyFilters'                => [
            'allCals'               => 'text',              //post
            'refresh'               => 'bool',              //post
            'find'                  => 'word',              //post
            'day'                   => 'digits',            //post
            'year'                  => 'digits',            //post
            'mon'                   => 'digits',            //post
            'generate_availability' => 'digits',            //post
            'sort_mode'             => 'string',            //get
            'watch_event'           => 'string',            //post
            'watch_action'          => 'string',            //post
            'todate'                => 'string',            //post

        ],'staticKeyFiltersForArrays' => [
            'calIds'                    => 'int',          //post
        ],
    ]
];
require_once('tiki-setup.php');
use Tiki\Sections;
$section = Sections::SECTION_CALENDAR;
Sections::setCurrentSection($section);
$calendarlib = TikiLib::lib('calendar');
$categlib = TikiLib::lib('categ');
include_once('lib/newsletters/nllib.php');

$headerlib->add_cssfile('themes/base_files/feature_css/calendar.css', 20);
# perms are
#   $tiki_p_view_calendar
#   $tiki_p_admin_calendar
#   $tiki_p_change_events
#   $tiki_p_add_events
$access->check_feature('feature_calendar');

$auto_query_args = [
    'viewmode',
    'calIds',
    'find',
    'mode',
    'sort_mode',
];

$maxSimultaneousWeekViewEvents = 3;

$myurl = $prefs['feature_sefurl'] === 'y' ? 'calendar' : 'tiki-calendar.php';
$iCalAdvParamsUrl = 'tiki-calendar_params_ical.php';
$smarty->assign('iCalAdvParamsUrl', $iCalAdvParamsUrl);

if (! isset($cookietab)) {
    $cookietab = '1';
}

$rawcals = $calendarlib->list_calendars();

if (empty($rawcals['data'])) {
    if (Perms::get(['type' => 'calendar'])->admin_calendar) {
        $message = tr('You need to %0create a calendar%1', '<a href="tiki-admin_calendars.php?cookietab=2">', '</a>');
    } else {
        $message = tra('No calendars found');
    }
    Feedback::errorAndDie($message, \Laminas\Http\Response::STATUS_CODE_404);
}

$rawcals['data'] = Perms::filter(
    ['type' => 'calendar'],
    'object',
    $rawcals['data'],
    [ 'object' => 'calendarId' ],
    'view_calendar'
);

if (empty($rawcals['data'])) {
    Feedback::errorAndDie(tra("You do not have permission to view the calendar"), \Laminas\Http\Response::STATUS_CODE_401);
}

$minHourOfDay = 12;
$maxHourOfDay = 12;
$calendars = [];
$canEditAnything = false;

foreach ($rawcals['data'] as $calendar) {
    $calendar['perms'] = Perms::get([ 'type' => 'calendar', 'object' => $calendar['calendarId']]);

    $calendars[$calendar['calendarId']] = $calendar;

    // for week and day views
    $startOfDayUnix = (int)($calendar['startday'] ?? $prefs['calendar_startday'] ?? 0);
    $startOfDayHour = $startOfDayUnix / 3600;
    $startOfDayMinute = ($startOfDayUnix % 3600) / 60;
    $minHourOfDay = date('H:i:s', mktime($startOfDayHour, $startOfDayMinute, 0));
    $endOfDayUnix = (int)($calendar['endday'] ?? 0);
    $endOfDayHour = $endOfDayUnix / 3600;
    $endOfDayMinute = ($endOfDayUnix % 3600) / 60;
    $maxHourOfDay = date('H:i:s', mktime($endOfDayHour, $endOfDayMinute, 0));

    $canEditAnything = $canEditAnything || $calendar['perms']->add_events;
}

$rawsubs = $calendarlib->get_subscriptions($user);
foreach ($rawsubs['data'] as $sub) {
    $calendars['s' . $sub['subscriptionId']] = $sub;
}

$smarty->assign('calendars', $calendars);
$smarty->assign('now', $tikilib->now);

// set up list of groups
$use_default_calendars = false;
$defaultCalendarId = 0;
if (isset($_REQUEST["calIds"]) and is_array($_REQUEST["calIds"]) and count($_REQUEST["calIds"])) {
    $defaultCalendarId = $_REQUEST["calIds"][0];
    $_SESSION['CalendarViewGroups'] = array_intersect($_REQUEST["calIds"], array_keys($calendars));
    if (! empty($user)) {
        $tikilib->set_user_preference($user, 'default_calendars', serialize($_SESSION['CalendarViewGroups']));
    }
} elseif (isset($_REQUEST["calIds"]) and ! is_array($_REQUEST["calIds"])) {
    $_SESSION['CalendarViewGroups'] = array_intersect([$_REQUEST["calIds"]], array_keys($calendars));
    if (! empty($user)) {
        $tikilib->set_user_preference($user, 'default_calendars', serialize($_SESSION['CalendarViewGroups']));
    }
} elseif (! empty($_REQUEST['allCals'])) {
    $_SESSION['CalendarViewGroups'] = array_keys($calendars);
    if (! empty($user)) {
        $tikilib->set_user_preference($user, 'default_calendars', serialize($_SESSION['CalendarViewGroups']));
    }
} elseif (! isset($_SESSION['CalendarViewGroups'])) {
    $use_default_calendars = true;
} elseif (isset($_REQUEST["refresh"]) and ! isset($_REQUEST["calIds"])) {
    $_SESSION['CalendarViewGroups'] = [];
} elseif (! empty($user)) {
    $use_default_calendars = true;
}

if ($use_default_calendars) {
    if ($prefs['feature_default_calendars'] == 'y') {
        $_SESSION['CalendarViewGroups'] = array_intersect(is_array($prefs['default_calendars']) ? $prefs['default_calendars'] : unserialize($prefs['default_calendars']), array_keys($calendars));
    } elseif (! empty($user)) {
        $user_default_calendars = $tikilib->get_user_preference($user, 'default_calendars', array_keys($calendars));
        if (is_string($user_default_calendars)) {
            $user_default_calendars = unserialize($user_default_calendars);
        }
        $_SESSION['CalendarViewGroups'] = $user_default_calendars;
    } else {
        $_SESSION['CalendarViewGroups'] = array_keys($calendars);
    }
}

if (! empty($_REQUEST["calitemId"])) {
    $calitemId = $_REQUEST["calitemId"];
    $currentItemCalendarId = $calendarlib->get_calendarid($calitemId);
    if (! empty($currentItemCalendarId) && ! in_array($currentItemCalendarId, $_SESSION['CalendarViewGroups']) && isset($calendars[$currentItemCalendarId])) {
        $_SESSION['CalendarViewGroups'][] = $currentItemCalendarId;
        if (! empty($user)) {
            $tikilib->set_user_preference($user, 'default_calendars', serialize($_SESSION['CalendarViewGroups']));
        }
    }
} else {
    $calitemId = '';
}
global $base_uri;
$current_url = $base_uri;
$smarty->assign('returnURL', $current_url);
$smarty->assign('currentcalitemId', $calitemId);

$smarty->assign('displayedcals', $_SESSION['CalendarViewGroups']);
$thiscal = [];
$checkedCalIds = [];

if (is_array($_SESSION['CalendarViewGroups'])) {
    foreach ($calendars as $calendarId => $calendar) {
        if (in_array($calendarId, $_SESSION['CalendarViewGroups'])) {
            $checkedCalIds[] = $calendarId;
        }
    }
}
$smarty->assign_by_ref('checkedCalIds', $checkedCalIds);
$find = $_REQUEST["find"] ?? '';
$smarty->assign('find', $find);

if (! empty($_REQUEST['mon'])) {
    $request_month = $_REQUEST['mon'];
}
if (! empty($_REQUEST['day'])) {
    $request_day = $_REQUEST['day'];
}
if (! empty($_REQUEST['year'])) {
    $request_year = $_REQUEST['year'];
}

include_once("tiki-calendar_setup.php");

// Calculate all the displayed days for the selected calendars
$viewdays = [];
foreach ($_SESSION['CalendarViewGroups'] as $calendar) {
    $defaultCalendarId = $_SESSION['CalendarViewGroups'][0];
    $info = $calendarlib->get_calendar($calendar);
    if (isset($info['viewdays']) && is_array($info['viewdays'])) {
        $viewdays = array_merge($info['viewdays'], $viewdays);
    }
}
$smarty->assign('defaultCalendarId', $defaultCalendarId);

if (empty($viewdays)) {
        $viewdays = [0,1,2,3,4,5,6];
}
sort($viewdays, SORT_NUMERIC);
$viewdays = array_map("correct_start_day", array_unique($viewdays));
$viewdays2 = array_values($viewdays);

if (! empty($_REQUEST['generate_availability'])) {
    try {
        if (empty($viewdays) || ! is_array($viewdays)) {
            throw new Exception(tr('Invalid view days parameter'));
        }
        if (empty($focusdate) || ! is_numeric($focusdate)) {
            throw new Exception(tr('Invalid focus date'));
        }
        $ranges = [];
        foreach ($viewdays as $day) {
            if (! is_numeric($day)) {
                continue;
            }
            $ranges[$day] = [
                [(int)$minHourOfDay, 0], [(int)$maxHourOfDay, 0]
            ];
        }
        $busy_list = [];
        $weekday = TikiLib::date_format('%w', $focusdate);
        $week_start = $focusdate;
        if ($weekday > 0) {
            $week_start -= $weekday * 86400;
        }
        $week_end = $week_start + (7 * 86400) - 1;
        $week_events = $calendarlib->list_raw_items(
            $_SESSION['CalendarViewGroups'],
            $user,
            $week_start,
            $week_end,
            0,
            -1
        );
        foreach ($week_events as $week_event) {
            if (empty($week_event['start']) || empty($week_event['end'])) {
                continue;
            }
            $dow = TikiLib::date_format("%w", $week_event['start']);
            $start_time = TikiLib::date_format("%H %i", $week_event['start']);
            $end_time = TikiLib::date_format("%H %i", $week_event['end']);
            if (empty($start_time) || empty($end_time)) {
                continue;
            }
            list($sh, $sm) = explode(' ', $start_time);
            list($eh, $em) = explode(' ', $end_time);
            $busy_list[] = [$dow, [$sh, $sm], [$eh, $em]];
        }
        $nlgen = new NLGen\Grammars\Availability\AvailabilityGenerator();
        $nlg_availability = $nlgen->generateAvailability($busy_list, $ranges, NLGen\Grammars\Availability\AvailabilityGrammar::SPECIFIC, null);
        $smarty->assign('nlg_availability', $nlg_availability);
    } catch (Exception $e) {
        error_log("Availability generation error: " . $e->getMessage());
        Feedback::error($e->getMessage());
        $smarty->assign('nlg_availability', '');
    }
}

if (isset($_REQUEST['sort_mode'])) {
    $sort_mode = $_REQUEST['sort_mode'];
}

$viewstart = $_REQUEST['todate'] ?? $tikilib->now;
$viewend = $viewstart + 90 * 86400 - 1; // 1 month approx

$listevents = [];
if (! empty($_SESSION['CalendarViewGroups']) && ! empty($_SESSION['CalendarViewList']) && $_SESSION['CalendarViewList'] === 'list') {
    // This logic is only for the 'list' view. The 'table' view is handled by AJAX.

    if (! isset($sort_mode)) {
        $sort_mode = "start_asc";
    }
    $smarty->assign_by_ref('sort_mode', $sort_mode);

    $listevents = $calendarlib->list_raw_items(
        $_SESSION['CalendarViewGroups'],
        $user,
        $viewstart,
        $viewend,
        0,
        $prefs['maxRecords'],
        $sort_mode
    );

    $listevents = Perms::filter(
        ['type' => 'calendaritem'],
        'object',
        $listevents,
        ['object' => 'calitemId'],
        ['view_events']
    );

    foreach ($listevents as & $event) {
        $event['perms'] = Perms::get([ 'type' => 'calendaritem', 'object' => $event['calitemId']]);
    }
    unset($event);
}
$smarty->assign_by_ref('listevents', $listevents);

$mloop = TikiLib::date_format("%m", $viewstart);
$dloop = TikiLib::date_format("%d", $viewstart);
$yloop = TikiLib::date_format("%Y", $viewstart);

$curtikidate = new TikiDate();
$display_tz = $tikilib->get_display_timezone();
if ($display_tz == '') {
    $display_tz = 'UTC';
}
$curtikidate->setTZbyID($display_tz);
$curtikidate->setLocalTime($dloop, $mloop, $yloop, 0, 0, 0, 0);

$smarty->assign('display_tz', $display_tz);

if ($prefs['feature_user_watches'] == 'y' && $user && count($_SESSION['CalendarViewGroups']) == 1) {
    $calId = $_SESSION['CalendarViewGroups'][0];
    if (isset($_REQUEST['watch_event']) && isset($_REQUEST['watch_action'])) {
        $access->checkCsrf();
        if ($_REQUEST['watch_action'] == 'add') {
            $tikilib->add_user_watch($user, $_REQUEST['watch_event'], $calId, 'calendar', $infocals['data'][$calId]['name'], "tiki-calendar.php?calIds[]=$calId");
        } else {
            $tikilib->remove_user_watch($user, $_REQUEST['watch_event'], $calId, 'calendar');
        }
    }
    if ($tikilib->user_watches($user, 'calendar_changed', $calId, 'calendar')) {
        $smarty->assign('user_watching', 'y');
    } else {
        $smarty->assign('user_watching', 'n');
    }

    // Check, if a user is watching this calendar.
    if ($prefs['feature_categories'] == 'y') {
        $watching_categories_temp = $categlib->get_watching_categories($calId, 'calendar', $user);
        $smarty->assign('category_watched', 'n');
        if (count($watching_categories_temp) > 0) {
            $smarty->assign('category_watched', 'y');
            $watching_categories = [];
            foreach ($watching_categories_temp as $wct) {
                $watching_categories[] = ["categId" => $wct, "name" => $categlib->get_category_name($wct)];
            }
            $smarty->assign('watching_categories', $watching_categories);
        }
    }
}

if ($prefs['feature_theme_control'] == 'y'  and isset($_REQUEST['calIds'])) {
    $cat_type = "calendar";
    $cat_objid = $_REQUEST['calIds'][0];
}
include_once('tiki-section_options.php');

    $headerlib->add_cssfile(BOOTSTRAP_ICONS_FONT_PATH . '/bootstrap-icons.css');
// Disable eventcalendar's force events to be one-line tall
$headerlib->add_css('.ec-event > .ec-content { white-space: normal; }');
if ($canEditAnything) {
    $smarty->assign('minHourOfDay', $minHourOfDay . ':00:00');
    $smarty->assign('maxHourOfDay', $maxHourOfDay . ':00:00');

    TikiLib::lib('header')
        ->add_cssfile('themes/base_files/feature_css/calendar.css', 20)
        ->add_jsfile('lib/jquery_tiki/tiki-calendar_edit_item.js');
}

// Detect if we have a PDF export mod installed
$smarty->assign('pdf_export', ($prefs['print_pdf_from_url'] != 'none') ? 'y' : 'n');
$smarty->assign('pdf_warning', 'n');
//checking if mPDF package is available

if ($prefs['print_pdf_from_url'] == "mpdf" && ! class_exists('\\Mpdf\\Mpdf')) {
    $smarty->assign('pdf_warning', 'y');
}
$smarty->assign('mid', 'tiki-calendar.tpl');


// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->display("tiki.tpl");
