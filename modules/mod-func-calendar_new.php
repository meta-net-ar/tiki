<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}

/**
 * @return array
 */
function module_calendar_new_info()
{
    return [
        'name' => tra('Calendar'),
        'description' => tra('Includes a calendar or a list of calendar events.'),
        'prefs' => ['feature_calendar'],
        'documentation' => 'Module calendar_new',
        'params' => [
            'calIds' => [
                'name' => tra('Calendars filter'),
                'description' => tra('If set to a list of calendar identifiers, restricts the events to those in the identified calendars. Identifiers are separated by vertical bars ("|"), commas (",") or colons (":"). Example values: "13", "4,7", "31:49". Not set by default.'),
                'profile_reference' => 'calendar',
            ],
            'month_delta' => [
                'name' => tra('Displayed month (relative)'),
                'description' => tra('Distance in month to the month to display. A distance of -1 would display the previous month. Setting this option implies a calendar view type with a month time span. Example values: 2, 0, -2, -12.'),
                'filter' => 'int',
            ],
            'viewlist' => [
                'name' => tra('View type'),
                'description' => tr('Determines how to show events. Possible values: %0, %1.', 'table', 'list'),
                'filter' => 'word',
                'default' => 'table',
            ],
            'viewmode' => [
                'name' => tra('Calendar view type time span'),
                'description' => tr(
                    'If in calendar (or "table") view type, determines the time span displayed by the calendar. Possible values: %0, %1, %2, %3 ,%4, %5. A user changing this time span in the calendar can change the time span the module displays for him.',
                    'year',
                    'semester',
                    'quarter',
                    'month',
                    'week',
                    'day'
                ),
                'filter' => 'word',
                'default' => 'month',
            ],
            'showaction' => [
                'name' => tra('Show action'),
                'description' => 'y|n',
                'filter' => 'word',
                'default' => 'y',
            ],
            'viewnavbar' => [
                'name' => tra('View navigation bar'),
                'description' => 'y|n|partial',
                'filter' => 'word',
                'default' => 'y',
            ]
        ]
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_calendar_new($mod_reference, $module_params)
{
    global $prefs, $user, $tiki_p_admin_calendars, $base_uri;
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');
    $calendarlib = TikiLib::lib('calendar');
    $userlib = TikiLib::lib('user');
    global $calendarViewMode, $focusdate;
    $default = ['viewnavbar' => 'y', 'viewmode' => 'month', 'showaction' => 'y'];
    $module_params = array_merge($default, $module_params);
    $defaultCalendarId = 0;
    $current_url = $base_uri;
    if (isset($_REQUEST['viewmode'])) {
        $save_viewmode = $_REQUEST['viewmode'];
    }
    if (! empty($module_params['viewmode'])) {
        $calendarViewMode['casedefault'] = $module_params['viewmode'];
    }

    if (isset($_REQUEST['todate'])) {
        $save_todate = $_REQUEST['todate'];
    }

    if (isset($module_params['month_delta'])) {
        $calendarViewMode['casedefault'] = 'month';
        list($focus_day, $focus_month, $focus_year) = [
            TikiLib::date_format("%d", $focusdate),
            TikiLib::date_format("%m", $focusdate),
            TikiLib::date_format("%Y", $focusdate)
        ];
        $_REQUEST['todate'] = $tikilib->make_time(0, 0, 0, $focus_month + $module_params['month_delta'], 1, $focus_year);
    }

    switch ($calendarViewMode['casedefault']) {
        case 'week':
            $initialView = 'timeGridWeek';
            break;
        case 'day':
            $initialView = 'timeGridDay';
            break;
        case 'month':
            $initialView = 'dayGridMonth';
            break;
        default:
            $initialView = 'dayGridMonth';
    }

    if (! empty($module_params['calIds'])) {
        $calIds = $module_params['calIds'];
        if (! is_array($module_params['calIds'])) {
            $calIds = preg_split('/[\|:\&,]/', $calIds);
        }
    } elseif (! empty($_SESSION['ModuleCalendarViewGroups'])) {
        $calIds = $_SESSION['ModuleCalendarViewGroups'];
    } elseif ($prefs['feature_default_calendars'] == 'n') {
        $calendars = $calendarlib->list_calendars();
        $calIds = array_keys($calendars['data']);
    } elseif (! empty($prefs['default_calendars'])) {
        $calIds = $_SESSION['ModuleCalendarViewGroups'] = is_array($prefs['default_calendars']) ? $prefs['default_calendars'] : unserialize($prefs['default_calendars']);
    } else {
        $calIds = [];
    }
    $defaultCalendarId = $calIds[0] ?? 0;
    $moduleCalendarIds = implode(',', $calIds);

    $_REQUEST['gbi'] = 'y';
    if (! empty($module_params['viewlist'])) {
        $_REQUEST['viewlistmodule'] = $module_params['viewlist'];
    } else {
        $_REQUEST['viewlistmodule'] = 'table';
    }

    $rawcals = $calendarlib->list_calendars();
    if (empty($rawcals['data'])) {
        Feedback::error(tra("No calendars found"));
        return;
    }

    $rawcals['data'] = Perms::filter(
        ['type' => 'calendar'],
        'object',
        $rawcals['data'],
        [ 'object' => 'calendarId' ],
        'view_calendar'
    );

    if (empty($rawcals['data'])) {
        Feedback::error(tra("You do not have permission to view the calendar"));
        return;
    }

    $moduleCalendarFocusdate = date("Y-m-d");
    $calendarInitialParams = $calendarlib->generalParamsOfCalendar($rawcals['data']);
    $calendarInitialParams['initialView'] = $initialView;

    if (! empty($calIds)) {
        $calendarInitialParams['calendars'] = array_intersect_key(
            $calendarInitialParams['calendars'],
            array_flip($calIds)
        );
    }

    $smarty->assign('eventCalendarParams', $calendarInitialParams);
    $calendars = $calendarInitialParams['calendars'];

    // set up list of groups
    if (isset($_REQUEST["calIds"]) and is_array($_REQUEST["calIds"]) and count($_REQUEST["calIds"])) {
        $_SESSION['ModuleCalendarViewGroups'] = array_intersect($_REQUEST["calIds"], array_keys($calendars));
    } elseif (isset($_REQUEST["calIds"]) and ! is_array($_REQUEST["calIds"])) {
        $_SESSION['ModuleCalendarViewGroups'] = array_intersect([$_REQUEST["calIds"]], array_keys($calendars));
    } elseif (! empty($_REQUEST['allCals'])) {
        $_SESSION['ModuleCalendarViewGroups'] = array_keys($calendars);
    } elseif (isset($_REQUEST["refresh"]) and ! isset($_REQUEST["calIds"])) {
        $_SESSION['ModuleCalendarViewGroups'] = [];
    }

    if ($calendarInitialParams['canEditAnything']) {
        TikiLib::lib('header')->add_jsfile('lib/jquery_tiki/tiki-calendar_edit_item.js');
    }

    foreach ($calIds as $i => $cal_id) {
        if ($tiki_p_admin_calendars != 'y' && ! $userlib->user_has_perm_on_object($user, $cal_id, 'calendar', 'tiki_p_view_calendar')) {
            unset($calIds[$i]);
        }
    }

    if (! empty($calIds)) {
        $tc_infos = $calendarlib->getCalendar($calIds, $viewstart, $viewend, 'day', 'events', true);
        if ($_REQUEST['viewlistmodule'] == 'list') {
            foreach ($tc_infos['listevents'] as $i => $e) {
                $tc_infos['listevents'][$i]['head'] = '';
                $tc_infos['listevents'][$i]['group_description'] = '';
            }
        }

        foreach ($tc_infos as $tc_key => $tc_val) {
            $smarty->assign($tc_key, $tc_val);
        }

        $checkedCalIds = [];
        $moduleCalendarViewGroups = $_SESSION['ModuleCalendarViewGroups'] ?? null;
        if (is_array($moduleCalendarViewGroups)) {
            foreach ($calendars as $calendarId => $calendar) {
                if (in_array($calendarId, $moduleCalendarViewGroups)) {
                    $checkedCalIds[] = $calendarId;
                }
            }
            $displayedcals = $moduleCalendarViewGroups;
            $moduleCalendarIds = implode(',', $moduleCalendarViewGroups);
        } else {
            $displayedcals = $calIds;
            $checkedCalIds = $calIds;
        }

        $smarty->assign('viewnavbar', $module_params['viewnavbar']);
        $smarty->assign('name', 'calendar_new');
        $smarty->assign('calendars', $calendars);
        $smarty->assign_by_ref('checkedCalIds', $checkedCalIds);
        $smarty->assign('displayedcals', $displayedcals);
        $smarty->assign('moduleCalendarIds', $moduleCalendarIds);
        $smarty->assign('returnURL', $current_url);
        $smarty->assign('moduleCalendarFocusdate', $moduleCalendarFocusdate);
        $smarty->assign('defaultCalendarId', $defaultCalendarId);
        $smarty->assign('uniqueId', rand());
        $smarty->assign('show_calendar_module', 'y');
        if (isset($save_todate)) {
            $_REQUEST['todate'] = $save_todate;
        } else {
            unset($_REQUEST['todate']);
        }
    }
}
