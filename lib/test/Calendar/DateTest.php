<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Calendar_DateTest extends TikiTestCase
{
    public function testMakeTime(): void
    {
        global $tikilib;
        $date = $tikilib->make_time(0, 0, 0, 8, 1, 2010);
        $this->assertEquals('2010-08-01 00:00', TikiLib::date_format('%Y-%m-%d %H:%M', $date));
    }

    public function testInfoDate(): void
    {
        global $tikilib;
        $calendarlib = TikiLib::lib('calendar');
        $date = $tikilib->make_time(0, 0, 0, 8, 1, 2010);
        $focus = $calendarlib->infoDate($date);
        $this->assertEquals('2010-08-01', TikiLib::date_format('%Y-%m-%d', $focus['date']));
    }

    public function testFocusToStartEnd(): void
    {
        global $tikilib;
        $calendarlib = TikiLib::lib('calendar');
        $date = $tikilib->make_time(0, 0, 0, 8, 1, 2010);
        $start = $startNext = [];
        $calendarlib->focusStartEnd($calendarlib->infoDate($date), 'month', 'y', $start, $startNext);
        $this->assertEquals('2010-08-01', TikiLib::date_format('%Y-%m-%d', $start['date']));
        $this->assertEquals('2010-09-01', TikiLib::date_format('%Y-%m-%d', $startNext['date']));
        $date = $tikilib->make_time(0, 0, 0, 2, 8, 2010);
        $calendarlib->focusStartEnd($calendarlib->infoDate($date), 'month', 'y', $start, $startNext);
        $this->assertEquals('2010-02-01', TikiLib::date_format('%Y-%m-%d', $start['date']));
        $this->assertEquals('2010-03-01', TikiLib::date_format('%Y-%m-%d', $startNext['date']));
        $date = $tikilib->make_time(0, 0, 0, 4, 30, 2010);
        $calendarlib->focusStartEnd($calendarlib->infoDate($date), 'month', 'y', $start, $startNext);
        $this->assertEquals('2010-04-01', TikiLib::date_format('%Y-%m-%d', $start['date']));
        $this->assertEquals('2010-05-01', TikiLib::date_format('%Y-%m-%d', $startNext['date']));
    }

    public function testFocusToCell(): void
    {
        global $tikilib;
        $calendarlib = TikiLib::lib('calendar');
        $start = $startNext = [];
        $date = $tikilib->make_time(0, 0, 0, 8, 1, 2010);
        $calendarlib->focusStartEnd($calendarlib->infoDate($date), 'month', 'y', $start, $startNext);
        $view = 'month';
        $firstWeekDay = 0; // sunday
        $cell = $calendarlib->getTableViewCells($start, $startNext, $view, $firstWeekDay);
        $this->assertEquals('2010-08-01', TikiLib::date_format('%Y-%m-%d', $cell[0][0]['date']));
        $this->assertEquals('2010-09-04', TikiLib::date_format('%Y-%m-%d', $cell[4][6]['date']));
        $view = 'month';
        $firstWeekDay = 1; // monday
        $cell = $calendarlib->getTableViewCells($start, $startNext, $view, $firstWeekDay);
        $this->assertEquals('2010-07-26', TikiLib::date_format('%Y-%m-%d', $cell[0][0]['date']));
        $this->assertEquals('2010-09-05', TikiLib::date_format('%Y-%m-%d', $cell[5][6]['date']));
    }

    public function testPrevious(): void
    {
        global $tikilib;
        $calendarlib = TikiLib::lib('calendar');
        $date = $tikilib->make_time(0, 0, 0, 7, 20, 2010);
        $previous = $calendarlib->focusPrevious($calendarlib->infoDate($date), 'month');
        $this->assertEquals('2010-06-20', TikiLib::date_format('%Y-%m-%d', $previous['date']));
        $date = $tikilib->make_time(0, 0, 0, 7, 31, 2010);
        $previous = $calendarlib->focusPrevious($calendarlib->infoDate($date), 'month');
        $this->assertEquals('2010-06-30', TikiLib::date_format('%Y-%m-%d', $previous['date']));
        $date = $tikilib->make_time(0, 0, 0, 3, 31, 2010);
        $previous = $calendarlib->focusPrevious($calendarlib->infoDate($date), 'quarter');
        $this->assertEquals('2009-11-30', TikiLib::date_format('%Y-%m-%d', $previous['date']));
    }

    public function testNext(): void
    {
        global $tikilib;
        $calendarlib = TikiLib::lib('calendar');
        $date = $tikilib->make_time(0, 0, 0, 8, 31, 2010);
        $previous = $calendarlib->focusNext($calendarlib->infoDate($date), 'month');
        $this->assertEquals('2010-09-30', TikiLib::date_format('%Y-%m-%d', $previous['date']));
    }

    public function testNextDayAndWeekRollOverDateBoundaries(): void
    {
        global $tikilib;
        $calendarlib = TikiLib::lib('calendar');
        $cases = [
            ['2026-06-30', 'day', '2026-07-01'],
            ['2026-06-30', 'week', '2026-07-07'],
            ['2026-12-31', 'day', '2027-01-01'],
            ['2026-12-31', 'week', '2027-01-07'],
            ['2024-02-29', 'day', '2024-03-01'],
            ['2024-02-29', 'week', '2024-03-07'],
        ];

        foreach ($cases as [$input, $view, $expected]) {
            [$year, $month, $day] = array_map('intval', explode('-', $input));
            $date = $tikilib->make_time(0, 0, 0, $month, $day, $year);
            $next = $calendarlib->focusNext($calendarlib->infoDate($date), $view);

            $this->assertSame($expected, TikiLib::date_format('%Y-%m-%d', $next['date']), "$view navigation from $input");
        }
    }

    public function testCalendarListRanges(): void
    {
        global $calendarViewMode, $prefs, $tikilib;
        $originalRequest = $_REQUEST;
        $originalSession = $_SESSION;
        $originalPrefs = $prefs;
        $hadCalendarViewMode = isset($calendarViewMode);
        $originalCalendarViewMode = $calendarViewMode ?? null;
        $cases = [
            ['day', 'y', '0', '2026-08-24 00:00:00', '2026-08-24 23:59:59'],
            ['week', 'y', '0', '2026-08-24 00:00:00', '2026-08-30 23:59:59'],
            ['month', 'y', '0', '2026-08-24 00:00:00', '2026-09-23 23:59:59'],
            ['quarter', 'y', '0', '2026-08-24 00:00:00', '2026-11-23 23:59:59'],
            ['semester', 'y', '0', '2026-08-24 00:00:00', '2027-02-23 23:59:59'],
            ['year', 'y', '0', '2026-08-24 00:00:00', '2027-08-23 23:59:59'],
            ['week', 'n', '0', '2026-08-23 00:00:00', '2026-08-29 23:59:59'],
            ['week', 'n', '1', '2026-08-24 00:00:00', '2026-08-30 23:59:59'],
            ['month', 'n', '0', '2026-08-01 00:00:00', '2026-08-31 23:59:59'],
            ['quarter', 'n', '0', '2026-08-01 00:00:00', '2026-10-31 23:59:59'],
            ['semester', 'n', '0', '2026-08-01 00:00:00', '2027-01-31 23:59:59'],
            ['year', 'n', '0', '2026-01-01 00:00:00', '2026-12-31 23:59:59'],
        ];

        try {
            foreach ($cases as [$viewmode, $beginsFocus, $firstDay, $expectedStart, $expectedEnd]) {
                $_SESSION = [];
                $_REQUEST = [
                    'todate' => $tikilib->make_time(0, 0, 0, 8, 24, 2026),
                    'viewmode' => $viewmode,
                    'viewlist' => 'list',
                ];
                $prefs['feature_calendar'] = 'y';
                $prefs['feature_action_calendar'] = 'n';
                $prefs['calendar_list_begins_focus'] = $beginsFocus;
                $prefs['calendar_firstDayofWeek'] = $firstDay;
                $prefs['calendar_view_mode'] = 'month';
                $prefs['calendar_timespan'] = '30';
                unset($calendarViewMode);

                include TIKI_PATH . '/tiki-calendar_setup.php';

                $message = "view=$viewmode, begins-focus=$beginsFocus, first-day=$firstDay";
                $actualStart = TikiLib::date_format('%Y-%m-%d %H:%M:%S', $daystart);
                $actualEnd = TikiLib::date_format('%Y-%m-%d %H:%M:%S', $dayend);
                $this->assertSame($expectedStart, $actualStart, "$message start");
                $this->assertSame($expectedEnd, $actualEnd, "$message end");
            }
        } finally {
            $_REQUEST = $originalRequest;
            $_SESSION = $originalSession;
            $prefs = $originalPrefs;
            if ($hadCalendarViewMode) {
                $calendarViewMode = $originalCalendarViewMode;
            } else {
                unset($calendarViewMode);
            }
        }
    }
}
