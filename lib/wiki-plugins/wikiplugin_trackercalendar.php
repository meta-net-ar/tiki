<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Package\VendorHelper;

function wikiplugin_trackercalendar_info()
{
    global $prefs;

    return [
        'name' => tr('Tracker Calendar'),
        'description' => tr('Create and display a calendar using tracker data'),
        'prefs' => ['wikiplugin_trackercalendar'],
        'packages_required' => [
            'npm-asset/fullcalendar-scheduler' =>
                VendorHelper::getAvailableVendorPath(
                    'fullcalendarscheduler',
                    'npm-asset/fullcalendar-scheduler/main.min.js'
                )
        ],
        'format' => 'html',
        'iconname' => 'calendar',
        'introduced' => 10,
        'body' => tra('Add list plugin style {filter} and {format} blocks'),
        'params' => [
            'trackerId' => [
                'name' => tr('Tracker ID'),
                'description' => tr('Tracker to search from'),
                'since' => '10.0',
                'required' => false,
                'default' => 0,
                'filter' => 'int',
                'profile_reference' => 'tracker',
            ],
            'begin' => [
                'name' => tr('Begin Date Field'),
                'description' => tr('Permanent name of the field to use for event beginning'),
                'since' => '10.0',
                'required' => true,
                'filter' => 'word',
            ],
            'end' => [
                'name' => tr('End Date Field'),
                'description' => tr('Permanent name of the field to use for event ending'),
                'since' => '10.0',
                'required' => true,
                'filter' => 'word',
            ],
            'title' => [
                'name' => tr('Title Field'),
                'description' => tr('Permanent name of the field to use for event title'),
                'since' => '29.0',
                'required' => false,
                'filter' => 'word',
            ],
            'description' => [
                'name' => tr('Description Field'),
                'description' => tr('Permanent name of the field to use for event description'),
                'since' => '29.0',
                'required' => false,
                'filter' => 'word',
            ],
            'resource' => [
                'name' => tr('Resource Descriptor Field'),
                'description' => tr('Permanent name of the field to use as the resource indicator'),
                'since' => '10.0',
                'required' => false,
                'filter' => 'word',
            ],
            'coloring' => [
                'name' => tr('Coloring Discriminator Field'),
                'description' => tr('Permanent name of the field to use to segment the information into color schemes.'),
                'since' => '10.0',
                'required' => false,
                'filter' => 'word',
            ],
            'external' => [
                'required' => false,
                'name' => tra('External Link'),
                'description' => tra('Follow external link when event item is clicked. Useful for supporting links to
                    pretty tracker supported pages.'),
                'since' => '12.4',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'url' => [
                'required' => false,
                'name' => tra('URL'),
                'description' => tra('Complete URL, internal or external.'),
                'since' => '12.4',
                'filter' => 'url',
                'default' => '',
                'parentparam' => ['name' => 'external', 'value' => 'y'],
            ],
            'trkitemid' => [
                'required' => false,
                'name' => tra('Tracker Item Id'),
                'description' => tr('If Yes (%0y%1) the item id will be passed as %0itemId%1, which can be used
                    by Tracker plugins. Will be passed as %0itemid%1 if No (%0n%1)', '<code>', '</code>'),
                'since' => '12.4',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
                'parentparam' => ['name' => 'external', 'value' => 'y'],
            ],
            'addAllFields' => [
                'required' => false,
                'name' => tra('Add All Fields'),
                'description' => tr('If Yes (%0y%1)  all fields in the tracker will be added to the URL, not just the
                    itemId', '<code>', '</code>'),
                'since' => '12.4',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
                'parentparam' => ['name' => 'external', 'value' => 'y'],
            ],
            'useSessionStorage' => [
                'required' => false,
                'name' => tra('Use Session Storage'),
                'description' => tr('If Yes (%0y%1) copy all the field values into window.sessionStorage so it can be
                    accessed via JavaScript.', '<code>', '</code>'),
                'since' => '12.4',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ],
                'parentparam' => ['name' => 'addAllFields', 'value' => 'y'],
            ],
            'amonth' => [
                'required' => false,
                'name' => tra('Agenda by Months'),
                'description' => tra('Display the option to change the view to agenda by months'),
                'since' => '12.1',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'aweek' => [
                'required' => false,
                'name' => tra('Agenda by Weeks'),
                'description' => tra('Display the option to change the view to agenda by weeks'),
                'since' => '12.1',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'aday' => [
                'required' => false,
                'name' => tra('Agenda by Days'),
                'description' => tra('Display the option to change the view to agenda by days'),
                'since' => '12.1',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'lyear' => [
                'required' => false,
                'name' => tra('List by Years'),
                'description' => tra('Display the option to change the view to list by years'),
                'since' => '20.1',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'lmonth' => [
                'required' => false,
                'name' => tra('List by Months'),
                'description' => tra('Display the option to change the view to list by months'),
                'since' => '20.1',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'lweek' => [
                'required' => false,
                'name' => tra('List by Weeks'),
                'description' => tra('Display the option to change the view to list by weeks'),
                'since' => '20.1',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'lday' => [
                'required' => false,
                'name' => tra('List by Days'),
                'description' => tra('Display the option to change the view to list by days'),
                'since' => '20.1',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'ryear' => [
                'required' => false,
                'name' => tra('Resources by Years'),
                'description' => tra('Display the option to change the view to resources by years'),
                'since' => '20.1',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'rmonth' => [
                'required' => false,
                'name' => tra('Resources by Months'),
                'description' => tra('Display the option to change the view to resources by months'),
                'since' => '12.1',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'rweek' => [
                'required' => false,
                'name' => tra('Resources by Weeks'),
                'description' => tra('Display the option to change the view to resources by weeks'),
                'since' => '12.1',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'rday' => [
                'required' => false,
                'name' => tra('Resources by Days'),
                'description' => tra('Display the option to change the view to resources by days'),
                'since' => '12.1',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'dView' => [
                'required' => false,
                'name' => tra('Default View'),
                'description' => tra('Choose the default view for the Tracker Calendar'),
                'since' => '12.1',
                'filter' => 'alpha',
                'default' => 'month',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Agenda by Months'), 'value' => 'month'],
                    ['text' => tra('Agenda by Weeks'), 'value' => 'agendaWeek'],
                    ['text' => tra('Agenda by Days'), 'value' => 'agendaDay'],
                    ['text' => tra('List'), 'value' => 'list'],
                    ['text' => tra('List by Months'), 'value' => 'listMonth'],
                    ['text' => tra('List by Weeks'), 'value' => 'listWeek'],
                    ['text' => tra('List by Days'), 'value' => 'listDay'],
                    ['text' => tra('Resources by Years'), 'value' => 'timelineYear'],
                    ['text' => tra('Resources by Months'), 'value' => 'timelineMonth'],
                    ['text' => tra('Resources by Weeks'), 'value' => 'timelineWeek'],
                    ['text' => tra('Resources by Days'), 'value' => 'timelineDay']
                ]
            ],
            'dYear' => [
                'required' => false,
                'name' => tra('Default Year'),
                'description' => tra('Choose the default year (yyyy) to use for the display'),
                'since' => '12.1',
                'default' => 0,
                'filter' => 'int',
            ],
            'dMonth' => [
                'required' => false,
                'name' => tra('Default Month'),
                'description' => tra('Choose the default month (mm, as numeric value) to use for the display. Numeric
                    values here are 1-based, meaning January=1, February=2, etc'),
                'since' => '12.1',
                'default' => 0,
                'filter' => 'int',
            ],
            'dDay' => [
                'required' => false,
                'name' => tra('Default Day'),
                'description' => tra('Choose the default day (dd) to use for the display'),
                'since' => '12.1',
                'default' => 0,
                'filter' => 'int',
            ],
            'colormap' => [
                'required' => false,
                'name' => tra('Colormap for coloring'),
                'description' => tr('Colormap to be used when segmenting the information using the coloring field.
                    Each map is composed of value and color separated with a comma, use pipes to separate multiple colormaps: %0', '<code>1,#6cf|2,#6fc</code>'),
                'since' => '18.0',
                'filter' => 'text',
            ],
            'fDayofWeek' => [
                'required' => false,
                'name' => tra('First day of the Week'),
                'description' => tr('Choose the day that each week begins with, for the tracker calendar display.
                    The value must be a number that represents the day of the week: Sunday=0, Monday=1, Tuesday=2,
                    etc. Default: %0 (Sunday)', '<code>0</code>'),
                'since' => '12.1',
                'default' => 0,
                'filter' => 'int',
            ],
            'weekends' => [
                'required' => false,
                'name' => tra('Show Weekends'),
                'description' => tra('Display Saturdays and Sundays (shown by default)'),
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'minHourOfDay' => [
                'required' => false,
                'name' => tra('Day Start'),
                'description' => tr('First time slot that will be displayed for each day, e.g. %0', '07:00:00'),
                'since' => '19.1',
                'filter' => 'text',
                'default' => '07:00:00',
            ],
            'maxHourOfDay' => [
                'required' => false,
                'name' => tra('Day End'),
                'description' => tr('Last time slot that will be displayed for each day, e.g. %0', '24:00:00'),
                'since' => '19.1',
                'filter' => 'text',
                'default' => '24:00:00',
            ],
            'slotDuration' => [
                'required' => false,
                'name' => tra('Slot Duration'),
                'description' => tr('Frequency for displayting time slots, e.g. %0 (defaults to the calendar_timespan preference)', "00:{$prefs['calendar_timespan']}:00"),
                'since' => '19.1',
                'filter' => 'text',
                'default' => "00:{$prefs['calendar_timespan']}:00",
            ],
            'eventOverlap' => [
                'required' => false,
                'name' => tra('Overlapping allowed'),
                'description' => tra('Allow resources to overlap in time.'),
                'since' => '20.1',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'maxEvents' => [
                'required' => false,
                'name' => tra('The maximum of events to retrieve'),
                'description' => tra('Adjust the maximum number of events to display in calendar views'),
                'since' => '22',
                'filter' => 'int',
                'default' => 200,
            ],
        ],
    ];
}

function wikiplugin_trackercalendar($data, $params)
{
    global $prefs;

    static $id = 0;
    $headerlib = TikiLib::lib('header');

    // Disable fullcalendar's force events to be one-line tall
    $headerlib->add_css('.ec-day-grid > .ec-content, .ec-timeline > .ec-content { white-space: normal; }');

    //$headerlib->add_cssfile($vendorPath . '/npm-asset/fullcalendar-scheduler/main.css');
    // Disable fullcalendar's force events to be one-line tall
    $headerlib->add_css('.ec-day-grid > .ec-content { white-space: normal; }');
    $headerlib->add_js_module('import "@jquery-tiki/wikiplugin-trackercalendar";');

    $jit = new JitFilter($params);
    $definition = Tracker_Definition::get($jit->trackerId->int());
    $itemObject = Tracker_Item::newItem($jit->trackerId->int());

    if (! $definition) {
        return WikiParser_PluginOutput::userError(tr('Tracker not found.'));
    }

    $beginField = $definition->getFieldFromPermName($jit->begin->word());
    $endField = $definition->getFieldFromPermName($jit->end->word());

    if (! $beginField || ! $endField) {
        return WikiParser_PluginOutput::userError(tr('Fields not found.'));
    }

    //set defaults
    $plugininfo = wikiplugin_trackercalendar_info();
    $defaults = [];
    foreach ($plugininfo['params'] as $key => $param) {
        $defaults[$key] = $param['default'] ?? null;
    }
    $params = array_merge($defaults, $params);

    $views = [];
    if ($params['amonth'] === 'y') {
        $views[] = 'dayGridMonth';
    }
    if ($params['aweek'] === 'y') {
        $views[] = 'resourceTimeGridWeek';
    }
    if ($params['aday'] === 'y') {
        $views[] = 'resourceTimeGridDay';
    }
    if ($params['lyear'] === 'y') {
        $views[] = 'listYear';
    }
    if ($params['lmonth'] === 'y') {
        $views[] = 'listMonth';
    }
    if ($params['lweek'] === 'y') {
        $views[] = 'listWeek';
    }
    if ($params['lday'] === 'y') {
        $views[] = 'listDay';
    }

    $resources = [];
    if ($resourceField = $jit->resource->word()) {
        $field = $definition->getFieldFromPermName($resourceField);
        $resources = wikiplugin_trackercalendar_get_resources($field);

        if ($params['ryear'] === 'y') {
            $views[] = 'resourceTimelineYear';
        }
        if ($params['rmonth'] === 'y') {
            $views[] = 'resourceTimelineMonth';
        }
        if ($params['rweek'] === 'y') {
            $views[] = 'resourceTimelineWeek';
        }
        if ($params['rday'] === 'y') {
            $views[] = 'resourceTimelineDay';
        }
    }

    $dViewMap = [
        'month' => 'dayGridMonth',
        'agendaWeek' => 'resourceTimeGridWeek',
        'agendaDay' => 'resourceTimeGridDay',
        'list' => 'list',
        'listMonth' => 'listMonth',
        'listWeek' => 'listWeek',
        'listDay' => 'listDay',
        'timelineYear' => 'resourceTimelineYear',
        'timelineMonth' => 'resourceTimelineMonth',
        'timelineWeek' => 'resourceTimelineWeek',
        'resourceWeek' => 'resourceTimelineWeek', // Old value on fullcalendar resources
        'timelineDay' => 'resourceTimelineDay'
    ];

    $dView = $dViewMap[$params['dView']];

    // Define the default date (dYear, dMonth, dDay)
    if (! empty($params['dYear'])) {
        $dYear = $params['dYear'];
    } else {
        $dYear = (int) date('Y');
    }
    if ($params['dMonth'] > 0 and $params['dMonth'] < 13) {
        $dMonth = $params['dMonth'];
    } else {
        $dMonth = (int) date('n');
    }
    if ($params['dDay'] > 0 and $params['dDay'] < 32) {
        $dDay = $params['dDay'];
    } else {
        $dDay = (int) date('j');
    }
    // day duration
    $minHourOfDay = $params['minHourOfDay'];
    $maxHourOfDay = $params['maxHourOfDay'];
    $slotDuration = $params['slotDuration'];

    $eventOverlap = $params['eventOverlap'] === 'y';

    $maxEvents = $params['maxEvents'];

    // Format the default date as Y-m-d instead of Y-n-d, required by MomentJs
    $dDate = (new DateTime($dYear . '-' . $dMonth . '-' . $dDay))->format('Y-m-d');

    if ($params['fDayofWeek'] > -1 and $params['fDayofWeek'] < 7) {
        $firstDayofWeek = $params['fDayofWeek'];
    } elseif ($prefs['calendar_firstDayofWeek'] !== 'user') {
        $firstDayofWeek = $prefs['calendar_firstDayofWeek'];
    } else {
        $firstDayofWeek = 0;
    }

    $matches = WikiParser_PluginMatcher::match($data);
    $builder = new Search_Formatter_Builder();
    $builder->apply($matches);
    $formatter = $builder->getFormatter();
    $filters = str_replace(['~np~', '~/np~'], '', $formatter->renderFilters());

    $smarty = TikiLib::lib('smarty');
    $smarty->assign(
        'trackercalendarData',
        [
            'id' => 'trackercalendar' . ++$id,
            'trackerId' => $jit->trackerId->int(),
            'begin' => $jit->begin->word(),
            'end' => $jit->end->word(),
            'resource' => $resourceField,
            'resourceList' => $resources,
            'coloring' => $jit->coloring->word(),
            'beginFieldName' => 'ins_' . $beginField['fieldId'],
            'endFieldName' => 'ins_' . $endField['fieldId'],
            'firstDayofWeek' => $firstDayofWeek,
            'views' => implode(',', $views),
            'initialDate' => $dDate,
            'minHourOfDay' => $minHourOfDay,
            'maxHourOfDay' => $maxHourOfDay,
            'slotDuration' => $slotDuration,
            'addTitle' => tr('Insert'),
            'title' => $jit->title->word(),
            'description' => $jit->description->word(),
            'canInsert' => $itemObject->canModify(),
            'dView' => $dView,
            'eventOverlap' => $eventOverlap,
            'body' => $data,
            'filterValues' => $_REQUEST,
            'url' => $params['external'] === 'y' ? $params['url'] : '',
            'trkitemid' => $params['external'] === 'y' ? $params['trkitemid'] : '',
            'addAllFields' => $params['external'] === 'y' ? $params['addAllFields'] : '',
            'useSessionStorage' => $params['external'] === 'y' ? $params['useSessionStorage'] : '',
            'timeFormat' => $prefs['display_12hr_clock'] === 'y',
            'weekends' => $params['weekends'] === 'y' ? 1 : 0,
            'utcOffset' => TikiDate::tzServerOffset(TikiLib::lib('tiki')->get_display_timezone()) / 60, // In minutes
            'maxEvents' => $maxEvents,
            'display_timezone' => $prefs['display_timezone'],
            'premiumLicense' => $prefs['calendar_fc_premium_license'],
            'labelResDay' => tr('resource day'),
            'labelResWeek' => tr('resource week'),
            'labelResMonth' => tr('resource month'),
            'labelResYear' => tr('resource year'),
            'labelListDay' => tr('list day'),
            'labelListWeek' => tr('list week'),
            'labelListMonth' => tr('list month'),
            'labelListYear' => tr('list year'),
            'labelAgendaWeek' => tr('week'),
            'labelAgendaDay' => tr('day'),
            'labelToday' => tr('today'),
            'labelAllDay' => tr('all-day'),
        ]
    );
    $smarty->assign('filters', $filters);
    return $smarty->fetch('wiki-plugins/trackercalendar.tpl');
}

function wikiplugin_trackercalendar_get_resources($field)
{
    $db = TikiDb::get();

    return $db->fetchAll('SELECT DISTINCT LOWER(value) as id, value as title FROM tiki_tracker_item_fields WHERE fieldId = ? ORDER BY  value', $field['fieldId']);
}
