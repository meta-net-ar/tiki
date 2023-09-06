<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'calendar';
require_once('tiki-setup.php');

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
    $smarty->assign('errortype', 404);
    $smarty->assign('msg', $message);
    $smarty->display("error.tpl");
    die;
}

$rawcals['data'] = Perms::filter(
    ['type' => 'calendar'],
    'object',
    $rawcals['data'],
    [ 'object' => 'calendarId' ],
    'view_calendar'
);

if (empty($rawcals['data'])) {
    $smarty->assign('errortype', 401);
    $smarty->assign('msg', tra("You do not have permission to view the calendar"));
    $smarty->display("error.tpl");
    die;
}

$minHourOfDay = 12;
$maxHourOfDay = 12;
$calendars = [];
$canEditAnything = false;

foreach ($rawcals['data'] as $calendar) {
    $calendar['perms'] = Perms::get([ 'type' => 'calendar', 'object' => $calendar['calendarId']]);

    $calendars[$calendar['calendarId']] = $calendar;

    // for week and day views
    $minHourOfDay = min($minHourOfDay, (int)(($calendar['startday'] ?? 0) / 3600));
    $maxHourOfDay = max($maxHourOfDay, (int)((($calendar['endday'] ?? 0) + 1) / 3600));

    $canEditAnything = $calendar['perms']->add_events || $calendar['perms']->add_events;
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
} elseif (! isset($_SESSION['CalendarViewGroups']) || ! empty($_REQUEST['allCals'])) {
    $use_default_calendars = true;
} elseif (isset($_REQUEST["refresh"]) and ! isset($_REQUEST["calIds"])) {
    $_SESSION['CalendarViewGroups'] = [];
} elseif (! empty($user) || ! isset($_SESSION['CalendarViewGroups'])) {
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

if (isset($_REQUEST["find"])) {
    $find = $_REQUEST["find"];
} else {
    $find = '';
}
$smarty->assign('find', $find);

if (isset($_REQUEST['mon']) && ! empty($_REQUEST['mon'])) {
    $request_month = $_REQUEST['mon'];
}
if (isset($_REQUEST['day']) && ! empty($_REQUEST['day'])) {
    $request_day = $_REQUEST['day'];
}
if (isset($_REQUEST['year']) && ! empty($_REQUEST['year'])) {
    $request_year = $_REQUEST['year'];
}

include_once("tiki-calendar_setup.php");

// Calculate all the displayed days for the selected calendars
$viewdays = [];
foreach ($_SESSION['CalendarViewGroups'] as $calendar) {
    $defaultCalendarId = $_SESSION['CalendarViewGroups'][0];
    $info = $calendarlib->get_calendar($calendar);
    if (is_array($info['viewdays'])) {
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
    $ranges = [];
    foreach ($viewdays as $day) {
        $ranges[$day] = [[$minHourOfDay, 0], [$maxHourOfDay, 0]];
    }
    $busy_list = [];
    $weekday = TikiLib::date_format('%w', $focusdate);
    $week_start = $focusdate;
    if ($weekday > 0) {
        $week_start -= $weekday * 86400;
    }
    $week_end = $week_start + 7 * 86400 - 1;
    $week_events = $calendarlib->list_raw_items($_SESSION['CalendarViewGroups'], $user, $week_start, $week_end, 0, -1);
    foreach ($week_events as $week_event) {
        $dow = TikiLib::date_format("%w", $week_event['start']);
        [$sh, $sm] = explode(' ', TikiLib::date_format("%H %i", $week_event['start']));
        [$eh, $em] = explode(' ', TikiLib::date_format("%H %i", $week_event['end']));
        $busy_list[] = [$dow, [$sh, $sm], [$eh, $em]];
    }
    $nlgen = new NLGen\Grammars\Availability\AvailabilityGenerator();
    $nlg_availability = $nlgen->generateAvailability($busy_list, $ranges, NLGen\Grammars\Availability\AvailabilityGrammar::SPECIFIC, null);
    $smarty->assign('nlg_availability', $nlg_availability);
}

if (isset($_REQUEST['sort_mode'])) {
    $sort_mode = $_REQUEST['sort_mode'];
}

$viewstart = $_REQUEST['todate'] ?? $tikilib->now;
$viewend = $viewstart + 90 * 86400 - 1; // 1 month approx

if ($_SESSION['CalendarViewGroups']) {
    if (array_key_exists('CalendarViewList', $_SESSION) && $_SESSION['CalendarViewList'] == "list") {
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
    } else {
        $listevents = $calendarlib->list_items($_SESSION['CalendarViewGroups'], $user, $viewstart, $viewend, 0, -1);
    }
    $smarty->assign_by_ref('listevents', $listevents);
} else {
    $listevents = [];
}

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
        check_ticket('calendar');
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

$headerlib->add_cssfile('vendor_bundled/vendor/npm-asset/fullcalendar/main.css');
$headerlib->add_cssfile('vendor_bundled/vendor/twbs/bootstrap-icons/font/bootstrap-icons.css');
// Disable fullcalendar's force events to be one-line tall
$headerlib->add_css('.fc-day-grid-event > .fc-content { white-space: normal; }');
$headerlib->add_jsfile('vendor_bundled/vendor/moment/moment/min/moment.min.js', true);
$headerlib->add_jsfile('vendor_bundled/vendor/npm-asset/fullcalendar/main.js', true);

$headerlib->add_jsfile('lib/jquery_tiki/tiki-calendar.js');

if ($canEditAnything) {
    $smarty->assign('minHourOfDay', $minHourOfDay . ':00:00');
    $smarty->assign('maxHourOfDay', $maxHourOfDay . ':00:00');
    if ($prefs['feature_wysiwyg'] == 'y' && $prefs['wysiwyg_default'] == 'y') {
        TikiLib::lib('wysiwyg')->setUpEditor(false, 'editwiki');        // init ckeditor if default editor
    }

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
