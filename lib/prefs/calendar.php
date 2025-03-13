<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_calendar_list()
{
    return [
        'calendar_view_days' => [
            'name' => tra('Days to display in the calendar'),
            'type' => 'multicheckbox',
            'options' => [
                0 => tra('Sunday'),
                1 => tra('Monday'),
                2 => tra('Tuesday'),
                3 => tra('Wednesday'),
                4 => tra('Thursday'),
                5 => tra('Friday'),
                6 => tra('Saturday'),
            ],
            'default' => [0,1,2,3,4,5,6],
        ],
        'calendar_view_mode' => [
            'name' => tra('Default view mode'),
            'description' => '',
            'type' => 'list',
            'options' => [
                'day' => tra('Day'),
                'week' => tra('Week'),
                'month' => tra('Month'),
                'quarter' => tra('Quarter'),
                'semester' => tra('Semester'),
                'year' => tra('Year'),
            ],
            'default' => 'month',
            'tags' => ['basic'],
        ],
        'calendar_list_begins_focus' => [
            'name' => tra('View list begins'),
            'description' => '',
            'type' => 'list',
            'options' => [
                'y' => tra('Focus date'),
                'n' => tra('Period beginning'),
            ],
            'default' => 'n',
        ],
        'calendar_firstDayofWeek' => [
            'name' => tra('First day of the week'),
            'description' => '',
            'type' => 'list',
            'options' => [
                '0' => tra('Sunday'),
                '1' => tra('Monday'),
                'user' => tra('Depends user language'),
            ],
            'default' => 'user',
        ],
        'calendar_timespan' => [
            'name' => tra('Split hours in periods of'),
            'description' => tra('Times shown in week and day view.'),
            'type' => 'list',
            'units' => tra('minutes'),
            'options' => [
                '1' => '1',
                '5' => '5',
                '10' => '10',
                '15' => '15',
                '30' => '30',
            ],
            'default' => '30',
        ],
        'calendar_minute_interval' => [
            'name' => tra('Minute Interval'),
            'description' => tra('Interval to show between minutes on time selectors'),
            'type' => 'list',
            'units' => tra('minutes'),
            'options' => [
                '1' => '1',
                '5' => '5',
                '10' => '10',
                '15' => '15',
                '30' => '30',
            ],
            'default' => '5',
        ],
        'calendar_start_year' => [
            'name' => tra('First year in the dropdown'),
            'units' => tra('years'),
            'description' => '',
            'type' => 'text',
            'size' => '5',
            'hint' => tra('Enter a year or use +/- N to specify a year relative to the current year. Year selection is valid when the JS Calendar __is not__ enabled'),
            'default' => '-3',
        ],
        'calendar_end_year' => [
            'name' => tra('Last year in the dropdown'),
            'description' => '',
            'units' => tra('years'),
            'type' => 'text',
            'size' => '5',
            'hint' => tra('Enter a year or use +/- N to specify a year relative to the current year'),
            'default' => '+5',
        ],
        'calendar_sticky_popup' => [
            'name' => tra('Sticky popup'),
            'description' => '',
            'type' => 'flag',
            'default' => 'n',
        ],
        'calendar_view_tab' => [
            'name' => tra('Item view tab'),
            'description' => '',
            'type' => 'flag',
            'default' => 'n',
        ],
        'calendar_addtogooglecal' => [
            'name' => tra('Show "Add to Google Calendar" icon'),
            'description' => '',
            'type' => 'flag',
            'dependencies' => [
                'wikiplugin_addtogooglecal'
            ],
            'default' => 'n',
        ],
        'calendar_export' => [
            'name' => tra('Show "Export Calendars" button'),
            'description' => '',
            'type' => 'flag',
            'default' => 'n',
        ],
        'calendar_export_item' => [
            'name' => tra('Show "Export Calendar Item" Button'),
            'description' => tra('Allow exporting a single calendar event as an iCal file'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'calendar_description_is_html' => [
            'name' => tra('Treat calendar item descriptions as HTML'),
            'description' => tra('Use this if you use the WYSIWYG editor for calendars. This is to handle legacy data from Tiki pre 7.0.'),
            'type' => 'flag',
            'default' => 'y',
        ],
        'calendar_watch_editor' => [
            'name' => tra('Enable watch events when you are the editor'),
            'description' => tra('Check this to receive email notifications of events you changed yourself.'),
            'type' => 'flag',
            'default' => 'y',
        ],
        'calendar_fc_premium_license' => [
            'name' => tra('License for FullCalendar premium plugins'),
            'description' => tra('FullCalendar premium license.'),
            'type' => 'text',
            'hint' => tr('For details on the premium license please check [%0]', 'https://fullcalendar.io/license'),
            'filter' => 'text',
            'default' => '',
        ],
        'calendar_event_click_action' => [
            'name' => tra('Event click action'),
            'description' => tra('View or edit item on click'),
            'type' => 'list',
            'options' => [
                'edit_item' => tr('Edit'),
                'view_item' => tr('View'),
            ],
            'default' => 'view_item',
        ],
        'calendar_start_day' => [
            'name' => tra('Calendar start of day'),
            'description' => '',
            'type' => 'text',
            'filter' => 'int',
            'size' => 5,
            'units' => tra('seconds'),
            'default' => 25200,
        ],
        'calendar_end_day' => [
            'name' => tra('Calendar end of day'),
            'description' => '',
            'type' => 'text',
            'filter' => 'int',
            'size' => 5,
            'units' => tra('seconds'),
            'default' => 72000,
        ],
        'calendar_holidays' => [
            'name' => tra('Holidays calendar'),
            'description' => tra('Choose a calendar to store non-working days which are used in date calculations involving working days.'),
            'type' => 'text',
            'default' => '',
            'profile_reference' => 'calendar',
        ],
    ];
}
