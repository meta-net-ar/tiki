<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_calendar_info()
{
    return [
        'name' => tra('Calendar'),
        'documentation' => 'PluginCalendar',
        'description' => tra('Display a calendar and its events'),
        'prefs' => [ 'feature_calendar', 'wikiplugin_calendar' ],
        'iconname' => 'calendar',
        'format' => 'html',
        'introduced' => 4,
        'params' => [
            'calIds' => [
                'required' => false,
                'name' => tra('Calendar IDs'),
                'description' => tra('Comma-separated list of calendar Ids to restrict the events to specified calendars.')
                    . " " . tra('Example values:') . '<code>13</code>, <code>4,7</code> ' . tra('Not set by default.'),
                'since' => '4.0',
                'filter' => 'digits',
                'separator' => ',',
                'profile_reference' => 'calendar',
            ],
            'viewlist' => [
                'required' => false,
                'name' => tra('View Type'),
                'description' => tra('Determines how events.') . ' ' . tr(
                    '%0 (default) shows events in a calendar.',
                    '<code>table</code>'
                ),
                'since' => '4.0',
                'filter' => 'word',
                'default' => 'table',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('List'), 'value' => 'list'],
                    ['text' => tra('Table'), 'value' => 'table'],
                    ['text' => tra('Both'), 'value' => 'both'],
                ],
            ],
            'viewmode' => [
                'required' => false,
                'name' => tra('View Time Span'),
                'description' => tr('If in calendar (%0) View Type, determines the time span displayed by the
                    calendar.', '<code>table</code>') . tra('Default is month'),
                'since' => '4.0',
                'filter' => 'word',
                'default' => 'month',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Day'), 'value' => 'day'],
                    ['text' => tra('Week'), 'value' => 'week'],
                    ['text' => tra('Month'), 'value' => 'month'],
                    ['text' => tra('Quarter'), 'value' => 'quarter'],
                    ['text' => tra('Semester'), 'value' => 'semester'],
                    ['text' => tra('Year'), 'value' => 'year'],
                ],
            ],
            'viewnavbar' => [
                'required' => false,
                'name' => tra('Navigation Bar'),
                'description' => tra('Show or hide the navigation bar (not shown by default)'),
                'since' => '4.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
            ],
        ],
    ];
}

function wikiplugin_calendar($data, $params)
{
    global $prefs, $tiki_p_admin, $tiki_p_view_calendar, $base_uri;

    $smarty = TikiLib::lib('smarty');
    $tikilib = TikiLib::lib('tiki');
    $calendarlib = TikiLib::lib('calendar');

    $params = WikiPlugin_Helper::applySeparators($params, wikiplugin_calendar_info());

    if (is_null($params['calIds'])) {
        $params['calIds'] = [1];
    }
    if (empty($params['viewlist'])) {
        $params['viewlist'] = 'table';
    }
    if (empty($params['viewmode'])) {
        $params['viewmode'] = 'month';
    }
    if (empty($params['viewnavbar'])) {
        $params['viewnavbar'] = ($params['viewlist'] === 'list' || $params['viewlist'] === 'both') ? 'y' : 'n';
    }
    $pluginCalendarIds = implode(',', $params['calIds']);

    $rawcals = $calendarlib->list_calendars();

    if (empty($rawcals['data'])) {
        Feedback::error(tra("No calendars found"));
        return;
    }

    // Remove calendars for which the user does not have permission
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

    $focusdate = date("Y-m-d");
    switch ($params['viewmode']) {
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

    $calendarInitialParams = $calendarlib->generalParamsOfCalendar($rawcals['data']);
    $calendarInitialParams['initialView'] = $initialView;

    if (! empty($params['calIds'])) {
        $calendarInitialParams['calendars'] = array_intersect_key(
            $calendarInitialParams['calendars'],
            array_flip($params['calIds'])
        );
    }

    $smarty->assign('eventCalendarParams', $calendarInitialParams);
    $calendars = $calendarInitialParams['calendars'];
    // set up list of groups
    if (isset($_REQUEST["calIds"]) and is_array($_REQUEST["calIds"]) and count($_REQUEST["calIds"])) {
        $_SESSION['PluginCalendarViewGroups'] = array_intersect($_REQUEST["calIds"], array_keys($calendars));
    } elseif (isset($_REQUEST["calIds"]) and ! is_array($_REQUEST["calIds"])) {
        $_SESSION['PluginCalendarViewGroups'] = array_intersect([$_REQUEST["calIds"]], array_keys($calendars));
    } elseif (! empty($_REQUEST['allCals'])) {
        $_SESSION['PluginCalendarViewGroups'] = array_keys($calendars);
    } elseif (isset($_REQUEST["refresh"]) and ! isset($_REQUEST["calIds"])) {
        $_SESSION['PluginCalendarViewGroups'] = [];
    }

    $current_url = $base_uri;
    $smarty->assign('returnURL', $current_url);
    $smarty->assign(
        'eventCalendarParams',
        $calendarInitialParams
    );
    $smarty->assign('uniqueId', rand());
    if ($calendarInitialParams['canEditAnything']) {
        TikiLib::lib('header')
            ->add_jsfile('lib/jquery_tiki/tiki-calendar_edit_item.js');
    }

    $checkedCalIds = [];
    $pluginCalendarViewGroups = $_SESSION['PluginCalendarViewGroups'] ?? null;
    if (is_array($pluginCalendarViewGroups)) {
        foreach ($calendars as $calendarId => $calendar) {
            if (in_array($calendarId, $pluginCalendarViewGroups)) {
                $checkedCalIds[] = $calendarId;
            }
        }
        $displayedcals = $pluginCalendarViewGroups;
        $pluginCalendarIds = implode(',', $pluginCalendarViewGroups);
    } else {
        $displayedcals = $params['calIds'];
        $checkedCalIds = $params['calIds'];
    }

    $smarty->assign('displayedcals', $displayedcals);
    $smarty->assign_by_ref('checkedCalIds', $checkedCalIds);
    $smarty->assign('pluginCalendarIds', $pluginCalendarIds);
    $smarty->assign('calendars', $calendars);
    $smarty->assign('focusdate', $focusdate);
    $smarty->assign('viewlist', $params['viewlist']);

    $out = '';
    if ($params['viewlist'] == 'list' || $params['viewlist'] == 'both') {
        $module_reference = [
            'moduleId' => null,
            'name' => 'calendar_new',
            'params' => [ 'calIds' => $params['calIds'],
                                'viewnavbar' => $params['viewnavbar'],
                                'viewlist' => $params['viewlist'],
                                'viewmode' => $params['viewmode'],
                                'nobox' => 'y' ],
            'position' => null,
            'ord' => null,
        ];

        $modlib = TikiLib::lib('mod');
        $out = '';
        if ($params['viewlist'] == 'list' || $params['viewlist'] == 'both') {
            $module_reference['params']['viewlist'] = 'list';
            $out .= "<div>" . $modlib->execute_module($module_reference) . "</div>";
        }
    }
    $smarty->assign('out', $out);
    $smarty->assign('viewnavbar', $params['viewnavbar']);

    return $smarty->fetch('wiki-plugins/wikiplugin_calendar.tpl');
}
