<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Services_Calendar_Controller
{
    private CalendarLib $calendarLib;
    private ?\Tiki\Lib\Logs\LogsLib $logsLib;
    private $daynamesPlural;
    private $monthnames;
    private $daynames;

    public function setUp(): void
    {
        global $prefs;

        Services_Exception_Disabled::check('feature_calendar');

        $this->calendarLib = TikiLib::lib('calendar');
        if ($prefs['feature_actionlog'] == 'y') {
            $this->logsLib = TikiLib::lib('logs');
        } else {
            $this->logsLib = null;
        }

        $this->daynamesPlural = [
            'SU' => tra('Sundays'),
            'MO' => tra('Mondays'),
            'TU' => tra('Tuesdays'),
            'WE' => tra('Wednesdays'),
            'TH' => tra('Thursdays'),
            'FR' => tra('Fridays'),
            'SA' => tra('Saturdays'),
        ];
        $this->monthnames = [
            '',
            tra('January'),
            tra('February'),
            tra('March'),
            tra('April'),
            tra('May'),
            tra('June'),
            tra('July'),
            tra('August'),
            tra('September'),
            tra('October'),
            tra('November'),
            tra('December'),
        ];
        $this->daynames = [
            'SU' => tra('Sunday'),
            'MO' => tra('Monday'),
            'TU' => tra('Tuesday'),
            'WE' => tra('Wednesday'),
            'TH' => tra('Thursday'),
            'FR' => tra('Friday'),
            'SA' => tra('Saturday'),
        ];
    }

    /**
     * Returns the section for use with certain features like banning
     *
     * @return string
     */
    public function getSection()
    {
        return 'calendar';
    }

    public function action_move($input)
    {
        $itemId = $this->getItemId($input);
        $delta = $input->delta->int();
        $this->calendarLib->move_item($itemId, $delta);

        return [
            'calitemId' => $itemId,
        ];
    }

    public function action_resize($input)
    {
        $itemId = $this->getItemId($input);
        $delta = $input->delta->int();

        $this->calendarLib->resize_item($itemId, $delta);

        return [
            'calitemId' => $itemId,
        ];
    }

    /**
     * Retreives visible events for full calendar via ajax
     *
     * @param $input JitFilter
     *
     * @return array
     * @throws Services_Exception_Denied
     */
    public function action_list_items($input): array
    {
        global $user, $prefs;

        $rawcals = $this->calendarLib->list_calendars();
        $rawcals['data'] = Perms::filter(
            ['type' => 'calendar'],
            'object',
            $rawcals['data'],
            [ 'object' => 'calendarId' ],
            'view_calendar'
        );

        if (empty($rawcals['data'])) {
            throw new Services_Exception_Denied(tr('No calendars found'));
        }

        $calendars = [];

        foreach ($rawcals['data'] as $calendar) {
            $calendar['perms'] = Perms::get([ 'type' => 'calendar', 'object' => $calendar['calendarId']]);

            $calendars[$calendar['calendarId']] = $calendar;
        }

        $viewstart = $input->start->date();
        $viewend = $input->end->date();

        $viewstart = new DateTime($viewstart);
        $viewstart = $viewstart->getTimestamp();

        $viewend = new DateTime($viewend);
        $viewend = $viewend->getTimestamp();

        if (isset($_SESSION['CalendarViewGroups']) && $_SESSION['CalendarViewGroups']) {
            $listevents = $this->calendarLib->list_raw_items(
                $_SESSION['CalendarViewGroups'],
                $user,
                $viewstart,
                $viewend,
                0,
                -1
            );

            $listevents = Perms::filter(
                ['type' => 'calendaritem'],
                'object',
                $listevents,
                ['object' => 'calitemId'],
                ['view_events']
            );
        } else {
            $listevents = [];
        }

        $parserLib = TikiLib::lib('parser');
        $events = [];

        foreach ($listevents as $event) {
            $event['perms'] = Perms::get([ 'type' => 'calendaritem', 'object' => $event['calitemId']]);

            if ($prefs['calendar_event_click_action'] === 'edit_item') {
                $action = $event['perms']->change_events ? 'edit_item' : 'view_item';
            } else {
                $action = 'view_item';
            }

            $url = TikiLib::lib('service')->getUrl([
                'controller' => 'calendar',
                'action'     => $action,
                'calitemId'  => $event['calitemId'],
            ]);

            $timezone = new DateTimeZone($prefs['display_timezone']);
            $start = new DateTime('@' . $event['start']);
            $end   = new DateTime('@' . $event['end']);

            $allDay = $event['allday'] != 0;
            if (! $allDay) {
                $start->setTimezone($timezone);
                $end->setTimezone($timezone);
            } else {
                $start->setTime(0, 0);
                $end->setTime(0, 0);
            }

            $events[] = [
                'id'          => $event['calitemId'],
                'title'       => $event['name'],
                'extendedProps' => [
                    'description' => ! empty($event['description']) ? $parserLib->parse_data(
                        $event['description'],
                        ['is_html' => $prefs['calendar_description_is_html'] === 'y']
                    ) : '',
                ],
                'url'         => $url,
                'allDay'      => $allDay,
                'start'       => $start->format(DATE_ATOM),
                'end'         => $end->format(DATE_ATOM),
                'editable'    => $event['perms']->change_events,
                'color'       => '#' . $calendars[$event['calendarId']]['custombgcolor'],
                'textColor'   => '#' . $calendars[$event['calendarId']]['customfgcolor'],
            ];
        }
        return $events;
    }

    /**
     * Edits or creates a calendar item (event)
     *
     * @param JitFilter $input
     *
     * @return array
     * @throws Services_Exception_NotFound
     */

    public function action_edit_item(JitFilter $input): array
    {
        global $user, $prefs;
        $access = TikiLib::lib('access');
        $parserLib = TikiLib::lib('parser');
        $displayTimezone = TikiLib::lib('tiki')->get_display_timezone();
        $timezones = TikiDate::getTimeZoneList();
        $timezones = array_keys($timezones);

        $rawcals = $this->calendarLib->list_calendars();

        if ($rawcals['cant'] === 0) {
            throw new Services_Exception_NotFound(tr('No calendars found'));
        }

        $calendars = Perms::filter(
            ['type' => 'calendar'],
            'object',
            $rawcals['data'],
            ['object' => 'calendarId'],
            ['add_events', 'change_events']
        );

        $calitemId = $input->calitemId->int();
        $preview = false;

        $dateNow = new TikiDate();

        if ($access->requestIsPost() && $access->checkCsrf(false, true)) {
            if ($input->offsetExists('calitem')) {
                $calitem = $input->asArray('calitem');
                $calendarId = $calitem['calendarId'];
                $calitem['calitemId'] = $calitemId;
                $calitem['allday'] = empty($calitem['allday']) ? 0 : 1;
                $calitem['recurrenceId'] = $input->recurrenceId->int();
                $calendar = $this->calendarLib->get_calendar($calendarId);
                $calitem = $this->processParticipants($calitem);

                // save event
                if ($input->act->word() === 'saveitem' || $input->act->word() === 'saveas') {
                    if (! $input->calendarchanged->int()) {
                        $saved = $this->saveEvent($calitem, $calendar, $input);

                        if ($saved) { // then redirect?
                            if ($input->offsetExists('redirect')) {
                                return ['url' => $input->redirect->url()];
                            } else {
                                // reload the page?
                                return [];
                            }
                        } else {
                            Feedback::error(tr('Calendar edit error')); // TODO more
                        }
                    }
                }
            } else {
                Feedback::error(tr('No event data?'));
                return [];
            }
        } elseif ($calitemId) {
            // load from database
            $calitemId = $this->getItemId($input);  // also checks edit perms
            $calitem = $this->calendarLib->get_item($calitemId);
            $calendarId = $calitem['calendarId'];
            $calendar = $this->calendarLib->get_calendar($calendarId);
            $title = tr('Calendar event : %0', $calitem['name']);

            $start = new TikiDate();
            $start->setDate($calitem['start']);
            $start->setTZbyID($displayTimezone);
            $end = new TikiDate();
            $end->setDate($calitem['end']);
            $end->setTZbyID($displayTimezone);

            $allDay = $calitem['allday'] != 0;
            if (! $allDay) {
                $start->convertTimeToUTC();
                $end->convertTimeToUTC();
            } else {
                $start->convertTimeToUTC(0, 0, 0);
                $end->convertTimeToUTC(0, 0, 0);
            }

            $calitem['start'] = $start->getTime();
            $calitem['end']   = $end->getTime();
            $calitem['duration'] = 0;
        } else {
            // new event
            $title = tr('Calendar event : %0', tr('New'));
            $calitemId = 0;
            $calendar = $calendars[0];
            $calendarId = $input->defaultCalendarId->int() > 0 ? $input->defaultCalendarId->int() : $calendar['calendarId'];

            // set up default start and end
            $dateNow->setTZbyID($displayTimezone);
            $hour = $dateNow->date->format('H');
            if ($input->offsetExists('todate')) {
                // set the correct day clicked on
                $dateNow->setDate($input->todate->int());
            }
            $start = mktime(
                $hour,
                0,
                0,
                $dateNow->date->format('m'),
                $dateNow->date->format('d'),
                $dateNow->date->format('Y')
            );
            $duration = 60 * 60;
            $end = $start + $duration;

            $calitem = [
                'calitemId'             => $calitemId,
                'calendarId'            => $calendarId,
                'user'                  => $user,
                'name'                  => '',
                'url'                   => '',
                'description'           => '',
                'status'                => $calendar['defaulteventstatus'],
                'priority'              => 0,
                'locationId'            => 0,
                'categoryId'            => 0,
                'nlId'                  => 0,
                'start'                 => $start,
                'end'                   => $end,
                'duration'              => $duration,
                'recurrenceId'          => 0,
                'allday'                => $calendar['allday'] == 'y' ? 1 : 0,
                'organizers'            => [$user],
                'participants'          => [[
                                                'username' => $user,
                                                'role'     => '',
                                                'partstat' => '',
                                            ]],
            ];
        }

        if (isset($calitem['recurrenceId']) && $calitem['recurrenceId'] > 0) {
            $recurrence = new CalRecurrence($calitem['recurrenceId']);
            $recurrence = $recurrence->toArray();
            $recurranceNumChangedEvents = (int)TikiDb::get()->table('tiki_calendar_items')->fetchCount([
                'recurrenceId' => $calitem['recurrenceId'],
                'changed'      => 1,
            ]);
        } elseif ($input->recurrent->int()) {   // previewing or switching calendars
            $recurrence = $this->createRecurrenceFromInput($input);
            $recurrence = $recurrence->toArray();
            $recurranceNumChangedEvents = 0;
        } else {
            $recurrence = new CalRecurrence();

            $dayValue = strtoupper(substr($dateNow->date->format('D'), 0, 2));
            $recurrence->setWeekdays($dayValue);
            $recurrence->setDayOfMonth($dateNow->date->format('j'));
            $monthlyValue = "1{$dayValue}";
            $recurrence->setMonthlyWeekdayValue($monthlyValue);
            $recurrence->setDateOfYear(
                $dateNow->date->format('m') . $dateNow->date->format('d')
            );

            $recurrence = $recurrence->toArray();
            $recurranceNumChangedEvents = 0;
        }

        $showeachuser = '';
        $listusertoalert = [];
        $groupforalert = '';

        if ($prefs['feature_groupalert'] === 'y' && ! empty($calendarId)) {
            $groupalertlib = TikiLib::lib('groupalert');

            $groupforalert = $groupalertlib->GetGroup('calendar', $calendarId);
            if ($groupforalert != '') {
                $showeachuser = $groupalertlib->GetShowEachUser('calendar', $calendarId, $groupforalert);
                $listusertoalert = TikiLib::lib('user')->get_users(
                    0,
                    -1,
                    'login_asc',
                    '',
                    '',
                    false,
                    $groupforalert,
                    ''
                );
                $listusertoalert = $listusertoalert['data'];
            }
        }

        if (! $input->modal->int()) {
            TikiLib::lib('header')
                ->add_cssfile('themes/base_files/feature_css/calendar.css', 20)
                ->add_jsfile('lib/jquery_tiki/tiki-calendar_edit_item.js');
        }


        if ($calendar['customcategories'] == 'y') {
            $customCategories = $this->calendarLib->list_categories($calendarId);
        } else {
            $customCategories = [];
        }

        if ($calendar["customsubscription"] == 'y') {
            $customSubscritions = TikiLib::lib('newsletter')->list_avail_newsletters();
        } else {
            $customSubscritions = [];
        }

        if ($calendar["customlanguages"] == 'y') {
            $customLanguages = TikiLib::lib('language')->list_languages();
        } else {
            $customLanguages = [];
        }
        if ($calendar['customlocations'] == 'y') {
            $customLocations = $this->calendarLib->list_locations($calendarId);
        } else {
            $customLocations = [];
        }

        $customPriorities = ['0','1','2','3','4','5','6','7','8','9'];
        $customPriorityColors = ['fff','fdd','fcc','fbb','faa','f99','e88','d77','c66','b66','a66'];
        $customRoles = ['0' => '','1' => tra('required'),'2' => tra('optional'),'3' => tra('non-participant')];

        return [
            // collections
            'daynames'                   => $this->daynames,
            'monthnames'                 => $this->monthnames,
            'calendars'                  => $calendars,
            // event info
            'title'                      => $title,
            'calitem'                    => $calitem,
            'calitemId'                  => $calitemId,
            'calendar'                   => $calendar,
            'calendarId'                 => $calendarId,
            // modes
            'edit'                       => true,
            'preview'                    => $preview,
            // recurrences
            'recurrent'                  => $calitem['recurrenceId'] ?: $input->recurrent->int(),
            'recurrence'                 => $recurrence,
            'recurranceNumChangedEvents' => $recurranceNumChangedEvents,
            // "custom" calendar properties
            'selected_participants'      => [$user],
            'customCategories'           => $customCategories,
            'customSubscritions'         => $customSubscritions,
            'customLanguages'            => $customLanguages,
            'customPriorities'           => $customPriorities,
            'customPrioritycolors'       => $customPriorityColors,
            'customRoles'                => $customRoles,
            'customLocations'            => $customLocations,
            // group alert info
            'listusertoalert'            => $listusertoalert,
            'groupforalert'              => $groupforalert,
            'showeachuser'               => $showeachuser,
            'modal'                      => $input->modal->int(),
            'displayTimezone'            => $displayTimezone,
            'timezones'                  => $timezones,
        ];
    }

    public function action_copy_item(JitFilter $input): array
    {
        $input->offsetSet('calitemId', 0);

        return $this->action_edit_item($input);
    }

    /**
     * Shows a calendar item (event)
     *
     * @param JitFilter $input
     *
     * @return array
     */

    public function action_view_item(JitFilter $input): array
    {
        global $prefs;

        $calitemId = $input->calitemId->int();
        $recurrence = [];
        $preview = false;

        if ($input->act->word() === 'preview') {
            $preview = true;

            $calitem = $input->asArray('calitem');
            $calendar = $this->calendarLib->get_calendar($calitem['calendarId']);
            $calitem['allday'] = empty($calitem['allday']) ? 0 : 1;

            $parserLib = TikiLib::lib('parser');
            $calitem['parsed'] = $parserLib->parse_data(
                $calitem['description'],
                ['is_html' => $prefs['calendar_description_is_html'] === 'y']
            );
            $calitem['parsedName'] = $parserLib->parse_data($calitem['name']);

            $calitem = $this->processParticipants($calitem);

            if ($calendar['customcategories'] == 'y' && $calitem['categoryId']) {
                $customCategories = $this->calendarLib->list_categories($calitem['calendarId']);
                foreach ($customCategories as $customCategory) {
                    if ($calitem['categoryId'] == $customCategory['categoryId']) {
                        $calitem['categoryName'] = $customCategory['name'];
                        break;
                    }
                }
            }

            if ($calendar['customlocations'] == 'y') {
                $customLocations = $this->calendarLib->list_locations($calitem['calendarId']);
                foreach ($customLocations as $customLocation) {
                    if ($calitem['locationId'] == $customLocation['locationId']) {
                        $calitem['locationName'] = $customLocation['name'];
                        break;
                    }
                }
            }


            $recurrence = $this->createRecurrenceFromInput($input);
            $recurrence = $recurrence->toArray();
        } elseif ($calitemId) {
            $calitemId = $this->getItemId($input, 'view_events');  // also checks edit perms
            $calitem = $this->calendarLib->get_item($calitemId);
            $calendar = $this->calendarLib->get_calendar($calitem['calendarId']);
            if (isset($calitem['recurrenceId']) && $calitem['recurrenceId'] > 0) {
                $recurrence = new CalRecurrence($calitem['recurrenceId']);
                $recurrence = $recurrence->toArray();
            }
        } else {
            Feedback::error(tr('Not found'));
            $calitem = [];
        }

        if ($calitem) {
            // calculate event display date/time
            $tikilib = TikiLib::lib('tiki');
            $startday = $tikilib->get_short_date($calitem['start']);
            $endday = $tikilib->get_short_date($calitem['end']);
            if ($startday === $endday) {
                if ($calitem['allday']) {
                    $calitem['display_datetimes'] = $startday;
                } else {
                    $starttime = $tikilib->get_short_time($calitem['start']);
                    $endtime = $tikilib->get_short_time($calitem['end']);
                    $calitem['display_datetimes'] = tr('%0 %1 to %2', $startday, $starttime, $endtime);
                }
            } elseif ($calitem['allday']) {
                $calitem['display_datetimes'] = tr('%0 to %1', $startday, $endday);
            } else {
                $starttime = $tikilib->get_short_time($calitem['start']);
                $endtime = $tikilib->get_short_time($calitem['end']);
                $calitem['display_datetimes'] = tr('%0 %1 to %2 %3', $startday, $starttime, $endday, $endtime);
            }
        }

        return [
            'calitem'              => $calitem,
            'recurrent'            => $calitem['recurrenceId'] ?: $input->recurrent->int(),
            'recurrence'           => $recurrence,
            'calendar'             => $calendar,
            'daynames'             => $this->daynamesPlural,
            'monthnames'           => $this->monthnames,
            'preview'              => $preview,
        ];
    }

    public function action_delete_item(JitFilter $input): array
    {
        global $user;

        $calitemId = $this->getItemId($input); // also checks edit perms

        if ($calitemId) {
            $this->calendarLib->drop_item($user, $calitemId);
            if ($this->logsLib) {
                $this->logsLib->add_action('Removed', 'event ' . $_REQUEST['calitemId'], 'calendar event');
            }
        }

        return [];
    }

    public function action_delete_recurrent_items(JitFilter $input): array
    {
        $calitemId = $this->getItemId($input); // also checks edit perms

        if ($calitemId && $input->recurrenceId->int()) {
            $calRec = new CalRecurrence($input->recurrenceId->int());
            $calRec->delete();
            if ($this->logsLib) {
                $this->logsLib->add_action(
                    'Removed',
                    'recurrent event (recurrenceId = ' . $_REQUEST["recurrenceId"] . ')',
                    'calendar event'
                );
            }
        }

        return [];
    }

    /**
     * @param array     $calitem  new data for the event
     * @param array     $calendar calendar it belongs to
     * @param JitFilter $input    the whole input
     *
     * @return bool
     */
    private function saveEvent(array $calitem, array $calendar, JitFilter $input): bool
    {
        global $prefs;

        $eventDefaults = [
            'name'     => tra("Event without name"),
            'priority' => 1,
            'status'   => $calendar['defaulteventstatus'] ?? 1,
        ];

        $calitem = array_merge($eventDefaults, $calitem);

        if ($calitem['end'] < $calitem['start']) {
            $impossibleDates = true;
        } elseif ($calitem['start'] + (24 * 60 * 60) < $calitem['end']) { // more than a day?
            $impossibleDates = true;
        } else {
            $impossibleDates = false;
        }

        if (! $impossibleDates) {
            if ($input->recurrent->int() && $input->affect->word() !== 'event') {
                $calRecurrence = $this->createRecurrenceFromInput($input);
                $saved = $calRecurrence->save($input->affect->word() === 'all');

                if ($this->logsLib) {
                    $this->logsLib->add_action(
                        (empty($calitem['calitemId']) ? 'Created' : 'Updated') .
                            'event ' . $calitem['calitemId'] . ' in calendar ' . $calitem['calendarId'],
                        'calendar event'
                    );
                }
                return $saved;
            } else {
                if ($input->offsetExists('recurrenceId')) {
                    $calitem['recurrenceId'] = $input->recurrenceId->int();
                    $calitem['changed'] = 1;
                }

                global $user;

                // if local browser offset or timezone identifier is submitted, convert timestamp to server-based timezone
                $calitem['start'] = TikiDate::convertWithTimezone($input->asArray(), $calitem['start']);
                $server_offset = TikiDate::tzServerOffset(TikiLib::lib('tiki')->get_display_timezone(), $calitem['start']);
                $calitem['start'] -= $server_offset;
                $calitem['end'] = TikiDate::convertWithTimezone($input->asArray(), $calitem['end']);
                $server_offset = TikiDate::tzServerOffset(TikiLib::lib('tiki')->get_display_timezone(), $calitem['end']);
                $calitem['end'] -= $server_offset;

                $calitemId = $this->calendarLib->set_item($user, $calitem['calitemId'] ?? 0, $calitem);
                // Save the ip at the log for the addition of new calendar items
                if ($this->logsLib) {
                    $this->logsLib->add_action(
                        (empty($calitem['calitemId']) ? 'Created' : 'Updated') .
                            'event ' . $calitemId . ' in calendar ' . $calitem['calendarId'],
                        'calendar event'
                    );
                }
                if ($prefs['feature_groupalert'] == 'y') {
                    if ($input->offsetExists('listtoalert')) {
                        TikiLib::lib('groupalert')->Notify(
                            $input->listtoalert->int(),
                            "tiki-ajax_services.php?controller=calendar&action=view_item&calitemId=" . $calitemId
                        );
                    }
                }
                return $calitemId > 0;
            }
        }
        return false;
    }

    /**
     * @throws Services_Exception_Denied
     * @throws Services_Exception_NotFound
     */
    private function getItemId(JitFilter $input, string $perm = 'change_events'): int
    {
        $item = $input->calitemId->int();

        $cal_id = $this->calendarLib->get_calendarid($item);

        if (! $item || ! $cal_id) {
            throw new Services_Exception_NotFound();
        }

        $calperms = Perms::get(['type' => 'calendar', 'object' => $cal_id]);
        if (! $calperms->$perm) {
            throw new Services_Exception_Denied();
        }

        return $item;
    }

    /**
     * Takes the input from edit_item form and returns a CalRecurrence object
     *
     * @param JitFilter   $input
     *
     * @return CalRecurrence
     */
    private function createRecurrenceFromInput(JitFilter $input): CalRecurrence
    {
        $calitem = $input->asArray('calitem');
        $displayTimezone = TikiLib::lib('tiki')->get_display_timezone();

        $recurrence = new CalRecurrence(
            ! empty($input->recurrenceId->int()) ? $input->recurrenceId->int() : -1
        );
        $recurrence->setCalendarId($calitem['calendarId']);
        $calitem['start'] = TikiDate::convertWithTimezone($input->asArray(), $calitem['start']);
        $calitem['end'] = TikiDate::convertWithTimezone($input->asArray(), $calitem['end']);
        $tz = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $tikidateStart = new TikiDate();
        $tikidateStart->setDate($calitem['start']);
        $tikidateEnd = new TikiDate();
        $tikidateEnd->setDate($calitem['end']);
        $recurrence->setStart($tikidateStart->format("%H%M", true));
        $recurrence->setEnd($tikidateEnd->format("%H%M", true));
        date_default_timezone_set($tz);
        $recurrence->setAllday($calitem['allday']);
        $recurrence->setLocationId($calitem['locationId']);
        $recurrence->setCategoryId($calitem['categoryId']);
        $recurrence->setNlId(0); //TODO : What id nlId ?
        $recurrence->setPriority($calitem['priority']);
        $recurrence->setStatus($calitem['status']);
        $recurrence->setUrl($calitem['url']);
        $recurrence->setLang(strLen($calitem['lang']) > 0 ? $calitem['lang'] : 'en');
        $recurrence->setName($calitem['name']);
        $recurrence->setDescription($calitem['description']);
        $recurrence->setRecurenceDstTimezone($input->recurrenceDstTimezone->text());
        switch ($input->recurrenceType->word()) {
            case "daily":
                $recurrence->setDaily(true);
                $recurrence->setWeekly(false);
                $recurrence->setMonthly(false);
                $recurrence->setYearly(false);
                $recurrence->setDays($input->days->int());
                break;
            case "weekly":
                $recurrence->setDaily(false);
                $recurrence->setWeekly(true);
                $recurrence->setMonthly(false);
                $recurrence->setYearly(false);
                $recurrence->setWeeks($input->weeks->int());
                $recurrence->setWeekdays(implode(',', $input->asArray('weekdays')));
                break;
            case "monthly":
                $recurrence->setDaily(false);
                $recurrence->setWeekly(false);
                $recurrence->setMonthly(true);
                $recurrence->setYearly(false);
                $recurrence->setMonths($input->months->int());
                $recurrence->setMonthlyType($input->recurrenceTypeMonthy->word());
                if ($input->recurrenceTypeMonthy->word() === 'weekday') {
                    $monthlyWeekdayValue = $input->monthlyWeekNumber->word() . $input->monthlyWeekday->word();
                    $recurrence->setMonthlyWeekdayValue($monthlyWeekdayValue);
                } else {
                    $recurrence->setDayOfMonth(implode(',', $input->asArray('dayOfMonth')));
                }
                break;
            case "yearly":
                $recurrence->setDaily(false);
                $recurrence->setWeekly(false);
                $recurrence->setMonthly(false);
                $recurrence->setYearly(true);
                $recurrence->setYears($input->years->int());
                $recurrence->setYearlyType($input->recurrenceTypeYearly->word());
                if ($input->recurrenceTypeYearly->word() === 'weekday') {
                    $yearlyWeekdayValue = $input->yearlyWeekNumber->word() . $input->yearlyWeekday->word();
                    $recurrence->setYearlyWeekdayValue($yearlyWeekdayValue);
                    $recurrence->setYearlyWeekMonth($input->yearlyWeekMonth->int());
                } else {
                    $recurrence->setDateOfYear(
                        str_pad($input->yearlyMonth->word(), 2, '0', STR_PAD_LEFT) .
                        str_pad($input->yearlyDay->word(), 2, '0', STR_PAD_LEFT)
                    );
                }
                break;
        }
        // startPeriod does not exist when using the old non-jscalendar time selector with 3 dropdowns
        $startPeriod = $input->startPeriod->int();
        if (empty($startPeriod)) {
            $startPeriod = mktime(
                0,
                0,
                0,
                $input->startPeriod_Month->int(),
                $input->startPeriod_Day->int(),
                $input->startPeriod_Year->int()
            );
        }
        if ($recurrence->getId() > 0 && $calitem['calitemId'] == $recurrence->getFirstItemId()) {
            // modify start period when the first event is updated
            $recurrence->setStartPeriod(TikiDate::getStartDay($calitem['start'], $displayTimezone));
        } else {
            $recurrence->setStartPeriod($startPeriod);
        }
        if ($input->endType->word() === "dt") {
            $recurrence->setEndPeriod($input->endPeriod->word());
        } else {
            $nbRecurrences = $input->nbRecurrences->int() ?? 1;
            if ($input->recurrenceType->word() === 'weekly') {
                $nbRecurrences = $nbRecurrences * count($input->asArray('weekdays'));
            }
            $recurrence->setNbRecurrences($nbRecurrences);
        }
        $recurrence->setUser($calitem['user']);
        if (! empty($calitem['calitemId'])) {
            // store the initial event if it was already created
            $recurrence->setInitialItem($calitem);
        }
        return $recurrence;
    }

    /**
     * @param array $calitem
     *
     * @return array
     */
    private function processParticipants(array $calitem): array
    {
        if (is_string($calitem['organizers'])) {
            $calitem['organizers'] = preg_split('/\s*,\s*/', $calitem['organizers']);
        }
        if ($calitem['organizers']) {
            $calitem['organizers'] = array_filter($calitem['organizers']);
        }

        // process participants
        if (! empty($calitem['participant_roles'])) {
            $participants = [];
            foreach ($calitem['participant_roles'] as $username => $role) {
                $participants[] = [
                    'username' => $username,
                    'role'     => $role,
                    'partstat' => $calitem['participant_partstat'][$username] ?? '',
                ];
            }
            $calitem['participants'] = $participants;
            unset($calitem['participant_roles'], $calitem['participant_partstat']);
        } else {
            $calitem['participants'] = [];
        }

        return $calitem;
    }
}
