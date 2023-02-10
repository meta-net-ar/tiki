<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_Calendar_Controller
{
    private CalendarLib $lib;

    public function setUp()
    {
        Services_Exception_Disabled::check('feature_calendar');

        $this->lib = TikiLib::lib('calendar');
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

        $calendarlib = TikiLib::lib('calendar');
        $calendarlib->move_item($itemId, $delta);

        return [
            'calitemId' => $itemId,
        ];
    }

    public function action_resize($input)
    {
        $itemId = $this->getItemId($input);
        $delta = $input->delta->int();

        $calendarlib = TikiLib::lib('calendar');
        $calendarlib->resize_item($itemId, $delta);

        return [
            'calitemId' => $itemId,
        ];
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
        global $user;

        $rawcals = $this->lib->list_calendars();

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

        $itemId = $input->calitemId->int();

        if ($itemId) {
            $itemId = $this->getItemId($input);  // also checks edit perms
            $calitem = $this->lib->get_item($itemId);
            $title = tr('Calendar event : %0', $calitem['name']);
        } else {
            $title = tr('Calendar event : %0', tr('New'));
            $calitem = [
                'calitemId'             => $calendars[0]['calendarId'],
                'user'                  => $user,
                'name'                  => '',
                'url'                   => '',
                'description'           => '',
                'status'                => $calendars[0]['defaulteventstatus'],
                'priority'              => 0,
                'locationId'            => 0,
                'categoryId'            => 0,
                'nlId'                  => 0,
                'start'                 => $start,
                'end'                   => $end,
                'duration'              => (60 * 60),
                'recurrenceId'          => 0,
                'allday'                => $calendars['allday'] == 'y' ? 1 : 0,
                'organizers'            => [$user],
                'participants'          => [[
                                                'username' => $user,
                                                'role'     => '',
                                                'partstat' => '',
                                            ]],
                'selected_participants' => [$user],
            ];
        }

        $calendarId = $calitem['calendarId'];
        $calendar = $this->lib->get_calendar($calendarId);


        if (isset($calitem['recurrenceId']) && $calitem['recurrenceId'] > 0) {
            $recurrence = new CalRecurrence($calitem['recurrenceId']);
            $recurrence = $recurrence->toArray();
            $recurranceNumChangedEvents = (int) TikiDb::get()->table('tiki_calendar_items')->fetchCount([
                'recurrenceId' => $calitem['recurrenceId'],
                'changed'      => 1,
            ]);
        } else {
            $recurrence = [];
            $recurranceNumChangedEvents = 0;
        }

        $daysnames = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
        $daysnames_abr = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
        $monthnames = ["", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

        TikiLib::lib('header')
            ->add_cssfile('themes/base_files/feature_css/calendar.css', 20)
            ->add_jsfile('lib/jquery_tiki/calendar_edit_item.js');

        return [
            'title'         => $title,
            'daysnames'     => $daysnames,
            'daysnames_abr' => $daysnames_abr,
            'monthnames'    => $monthnames,
            'edit'          => true,
            'calitem'       => $calitem,
            'calendar'      => $calendar,
            'calendars'     => $calendars,
            'calendarId'    => $calendarId,
            'recurrence'    => $recurrence,
            'recurranceNumChangedEvents' => $recurranceNumChangedEvents,
        ];
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


        return [
            'edit' => false,
        ];
    }

    /**
     * @throws Services_Exception_Denied
     * @throws Services_Exception_NotFound
     */
    private function getItemId(JitFilter $input, string $perm = 'change_events'): int
    {
        $item = $input->calitemId->int();

        $cal_id = $this->lib->get_calendarid($item);

        if (! $item || ! $cal_id) {
            throw new Services_Exception_NotFound();
        }

        $calperms = Perms::get(['type' => 'calendar', 'object' => $cal_id]);
        if (! $calperms->$perm) {
            throw new Services_Exception_Denied();
        }

        return $item;
    }
}
