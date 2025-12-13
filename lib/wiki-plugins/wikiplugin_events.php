<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\TikiDate;

function wikiplugin_events_info()
{
    return [
        'name' => tra('Events'),
        'documentation' => 'PluginEvents',
        'description' => tra('Display events from calendars'),
        'prefs' => [ 'feature_calendar', 'wikiplugin_events' ],
        'iconname' => 'calendar',
        'tags' => [ 'basic' ],
        'introduced' => 2,
        'params' => [
            'calendarid' => [
                'required' => true,
                'name' => tra('Calendar IDs'),
                'description' => tr('ID numbers for the site calendars whose events are to be displayed, separated by
                    vertical bars (%0)', '<code>|</code>'),
                'since' => '2.0',
                'filter' => 'text',
                'profile_reference' => 'calendar',
            ],
            'maxdays' => [
                'required' => false,
                'name' => tra('Maximum Days'),
                'description' => tr('Events occurring within this number of days in the future from today will be
                    included in the list (unless limited by other parameter settings). Default is %0.', '<code>365</code>'),
                'since' => '2.0',
                'filter' => 'digits',
                'default' => 365,
            ],
            'max' => [
                'required' => false,
                'name' => tra('Maximum Events'),
                'description' => tr('Maximum number of events to display. Default is %0. Set to %1 to display all
                    (unless limited by other parameter settings)', '<code>10</code>', '<code>0</code>'),
                'since' => '2.0',
                'default' => 10,
                'filter' => 'digits',
            ],
            'datetime' => [
                'required' => false,
                'name' => tra('Show Time'),
                'description' => tra('Show the time along with the date (shown by default)'),
                'since' => '2.0',
                'default' => 1,
                'filter' => 'digits',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ],
            ],
            'desc' => [
                'required' => false,
                'name' => tra('Show Description'),
                'description' => tra('Show the description of the event (shown by default)'),
                'since' => '2.0',
                'default' => 1,
                'filter' => 'digits',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ],
            ],
            // Pagination
            'timespan' => [
                'required' => false,
                'name' => tra('Time Span'),
                'description' => tra('Specify the time span.'),
                'since' => '10.0',
                'default' => 'future',
                'filter' => 'word',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('All'), 'value' => 'all'],
                    ['text' => tra('Past'), 'value' => 'past'],
                    ['text' => tra('Future'), 'value' => 'future']
                ],
            ],
            'usePagination' => [
                'required' => false,
                'name' => tra('Use Pagination'),
                'description' => tr('Activate pagination when Events listing are long. Default is %0.', '<code>n</code>'),
                'since' => '10.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
            ],
            'attendees' => [
                'required' => false,
                'name' => tra('attendees'),
                'description' => tr('This parameter allows listing the participants of the event.'),
                'since' => '28.0',
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

/**
 * @param $data
 * @param $params
 * @return string
 */
function wikiplugin_events($data, $params)
{
    global $tiki_p_admin, $tiki_p_view_calendar, $user;
    $userlib = TikiLib::lib('user');
    $smarty = TikiLib::lib('smarty');
    $calendarlib = TikiLib::lib('calendar');
    $invalideid = false;

    extract($params, EXTR_SKIP);

    if ($usePagination == 'y') {
        $start = $_REQUEST["offset"] ?? 0;
    }



    $rawcals = $calendarlib->list_calendars();
    $calIds = [];
    $viewable = [];

    foreach ($rawcals["data"] as $cal_id => $cal_data) {
        $calIds[] = $cal_id;
        if ($tiki_p_admin == 'y') {
            $canView = 'y';
        } elseif ($cal_data["personal"] == "y") {
            if ($user) {
                $canView = 'y';
            } else {
                $canView = 'n';
            }
        } else {
            if ($userlib->object_has_one_permission($cal_id, 'calendar')) {
                if ($userlib->object_has_permission($user, $cal_id, 'calendar', 'tiki_p_view_calendar')) {
                    $canView = 'y';
                } else {
                    $canView = 'n';
                }
                if ($userlib->object_has_permission($user, $cal_id, 'calendar', 'tiki_p_admin_calendar')) {
                    $canView = 'y';
                }
            } else {
                $canView = $tiki_p_view_calendar;
            }
        }
        if ($canView == 'y') {
            $viewable[] = $cal_id;
        }
    }

    // Pagination
    if ($timespan == 'future') {
        $calendarlib->upcoming_events(
            $max,
            array_intersect($calIds, $viewable) ?: [],
            $maxdays,
            'start_asc',
            1,
            0,
            $start ?? 0
        );
    }

    if ($timespan == 'all') {
        $calendarlib->all_events(
            $max,
            array_intersect($calIds, $viewable),
            $maxdays,
            'start_asc',
            1,
            0,
            $start ?? 0
        );
    }

    if ($timespan == 'past') {
        $calendarlib->past_events(
            $max,
            array_intersect($calIds, $viewable),
            $maxdays,
            'start_desc',
            0,
            -1,
            $start ?? 0
        );
    }


    if (! is_null($calendarid)) {
        $calParamIds = explode('|', $calendarid);
        if (array_diff($calParamIds, $calIds)) {
            $invalideid = true;
            $calParamIds = [];
        }
    }
    $events = $calendarlib->upcoming_events($max, array_intersect($calParamIds, $viewable) ?: [], $maxdays);

    $smarty->assign_by_ref('datetime', $datetime);
    $smarty->assign_by_ref('desc', $desc);
    $smarty->assign_by_ref('events', $events);
    $smarty->assign_by_ref('invalideid', $invalideid);

    // Pagination
    if ($usePagination == 'y') {
        $start = $start ?? 0;
        $smarty->assign('maxEvents', $max);
        $smarty->assign_by_ref('offset', $start);
        $smarty->assign_by_ref('count', $events['count']);
    }

    // attendees
    if ($attendees == 'y') {
        $attendees = 'y';
    }

    $listAttendees = $calendarlib->getAttendees();

    $smarty->assign('usePagination', $usePagination ?? 'n');
    $smarty->assign_by_ref('events', $events['data']);
    $smarty->assign_by_ref('actions', $actions);
    $smarty->assign('attendees', $attendees);
    $smarty->assign('listAttendees', $listAttendees);


    return '~np~' . $smarty->fetch('wiki-plugins/wikiplugin_events.tpl') . '~/np~';

    $repl = "";
    if (count($events) < $max) {
        $max = count($events);
    }

    $repl .= '<table class="table-bordered">';
    $repl .= '<tr class="heading"><td colspan="2">' . tra("Upcoming Events") . '</td></tr>';
    $tikidateStart = new TikiDate();
    $tikidateEnd = new TikiDate();
    for ($j = 0; $j < $max; $j++) {
        $tikidateStart->setDate($events[$j]["start"]);
        $tikidateEnd->setDate($events[$j]["end"]);
        if ($datetime != 1) {
            $eventStart = str_replace(" ", "&nbsp;", $tikidateStart->format($tikilib->get_short_date_format(), true));
            $eventEnd = str_replace(" ", "&nbsp;", $tikidateEnd->format($tikilib->get_short_date_format(), true));
        } else {
            $eventStart = str_replace(" ", "&nbsp;", $tikidateStart->format($tikilib->get_short_datetime_format(), true));
            $eventEnd = str_replace(" ", "&nbsp;", $tikidateEnd->format($tikilib->get_short_datetime_format(), true));
        }
        if ($j % 2) {
            $style = "odd";
        } else {
            $style = "even";
        }
        $repl .= '<tr class="' . $style . '"><td width="5%">~np~' . $eventStart . '<br/>' . $eventEnd . '~/np~</td>';
        $repl .= '<td><a class="linkmodule" href="tiki-calendar.php?editmode=details&calitemId=' . $events[$j]["calitemId"] . '"><b>' . $events[$j]["name"] . '</b></a>';
        if ($desc == 1) {
            $repl .= '<br/>' . nl2br($events[$j]["description"]);
        }
        $repl .= '</td></tr>';
    }
    $repl .= '</table>';
    return $repl;
}
