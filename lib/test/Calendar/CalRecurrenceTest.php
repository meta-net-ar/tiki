<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Calendar_CalRecurrenceTest extends TikiTestCase
{
    protected $calendarId;
    protected $recIds = [];

    protected function setUp(): void
    {
        global $user;
        $user = 'admin';
        $this->calendarId = TikiLib::lib('calendar')->set_calendar(0, $user, 'Test cal', '', ['customlanguages' => 'y', 'custompriorities' => 'y']);
        $table = TikiLib::lib('tiki')->table('tiki_calendar_recurrence');
        $start = strtotime('12am');
        $this->recIds['daily'] = $table->insert([
            'calendarId' => $this->calendarId,
            'start' => '1200',
            'end' => '1300',
            'name' => 'Daily event',
            'daily' => 1,
            'days' => 1,
            'startPeriod' => $start,
            'endPeriod' => strtotime('12am', strtotime('+5 days')),
        ]);
        $this->recIds['weekly'] = $table->insert([
            'calendarId' => $this->calendarId,
            'start' => '1200',
            'end' => '1300',
            'name' => 'Bi-weekly wednesday event',
            'weekly' => 1,
            'weeks' => 2,
            'weekdays' => 'WE',
            'startPeriod' => $start,
            'endPeriod' => strtotime('12am', strtotime('+2 months')),
        ]);
        $this->recIds['monthly-date'] = $table->insert([
            'calendarId' => $this->calendarId,
            'start' => '1200',
            'end' => '1300',
            'name' => 'Monthly 10th event',
            'monthly' => 1,
            'months' => 1,
            'monthlyType' => 'date',
            'dayOfMonth' => 10,
            'startPeriod' => $start,
            'endPeriod' => strtotime('12am', strtotime('+5 months')),
        ]);
        $this->recIds['monthly-weekday'] = $table->insert([
            'calendarId' => $this->calendarId,
            'start' => '1200',
            'end' => '1300',
            'name' => 'Monthly every second tuesday event',
            'monthly' => 1,
            'months' => 1,
            'monthlyType' => 'weekday',
            'monthlyWeekdayValue' => '2TU',
            'startPeriod' => $start,
            'endPeriod' => strtotime('12am', strtotime('+5 months')),
        ]);
        $this->recIds['yearly-date'] = $table->insert([
            'calendarId' => $this->calendarId,
            'start' => '1200',
            'end' => '1300',
            'name' => 'Yearly Sep 15th ',
            'yearly' => 1,
            'years' => 1,
            'yearlyType' => 'date',
            'dateOfYear' => 915,
            'startPeriod' => $start,
            'endPeriod' => strtotime('12am', strtotime('+5 years')),
        ]);
        $this->recIds['yearly-weekday'] = $table->insert([
            'calendarId' => $this->calendarId,
            'start' => '1200',
            'end' => '1300',
            'name' => 'Yearly every last friday of June',
            'yearly' => 1,
            'years' => 1,
            'yearlyType' => 'weekday',
            'yearlyWeekdayValue' => '-1FR',
            'yearlyWeekMonth' => 6,
            'startPeriod' => $start,
            'endPeriod' => strtotime('12am', strtotime('+5 years')),
        ]);
    }

    public function tearDown(): void
    {
        TikiLib::lib('tiki')->query("DELETE FROM tiki_calendar_recurrence");
        TikiLib::lib('tiki')->query("DELETE FROM tiki_calendar_items");
        TikiLib::lib('tiki')->query("DELETE FROM tiki_calendars WHERE calendarId = ?", $this->calendarId);
    }

    public function testLoad(): void
    {
        $rec = new CalRecurrence($this->recIds['yearly-date']);
        $this->assertEquals([
            'id' => $this->recIds['yearly-date'],
            'daily' => false,
            'days' => null,
            'weekly' => false,
            'weeks' => null,
            'weekdays' => [],
            'monthly' => false,
            'months' => null,
            'dayOfMonth' => [],
            'monthlyType' => null,
            'monthlyWeekdayValue' => null,
            'yearly' => true,
            'years' => 1,
            'yearlyType' => 'date',
            'dateOfYear' => 915,
            'yearlyMonth' => 9,
            'yearlyDay' => 15,
            'yearlyWeekdayValue' => null,
            'yearlyWeekMonth' => null,
            'nbRecurrences' => null,
            'startPeriod' => $rec->getStartPeriod(),
            'endPeriod' => $rec->getEndPeriod(),
            'user' => '',
            'created' => 0,
            'lastModif' => 0,
            'recurrenceDstTimezone' => null,
        ], $rec->toArray());
    }

    public function testDailyValidity(): void
    {
        $rec = new CalRecurrence($this->recIds['daily']);
        $this->assertTrue($rec->isValid());
        $rec->setDaily(0);
        $this->assertFalse($rec->isValid());
    }

    public function testWeeklyValidity(): void
    {
        $rec = new CalRecurrence($this->recIds['weekly']);
        $this->assertTrue($rec->isValid());
        $rec->setWeeks(0);
        $this->assertFalse($rec->isValid());
        $rec->setWeeks(2);
        $rec->setWeekdays('');
        $this->assertFalse($rec->isValid());
    }

    public function testMonthlyDateValidity(): void
    {
        $rec = new CalRecurrence($this->recIds['monthly-date']);
        $this->assertTrue($rec->isValid());
        $rec->setDayOfMonth('1516');
        $this->assertFalse($rec->isValid());
    }

    public function testMonthlyWeekdayValidity(): void
    {
        $rec = new CalRecurrence($this->recIds['monthly-weekday']);
        $this->assertTrue($rec->isValid());
        $rec->setMonthlyWeekdayValue('12SU');
        $this->assertFalse($rec->isValid());
    }

    public function testYearlyDateValidity(): void
    {
        $rec = new CalRecurrence($this->recIds['yearly-date']);
        $this->assertTrue($rec->isValid());
        $rec->setDateOfYear('');
        $this->assertFalse($rec->isValid());
    }

    public function testYearlyWeekdayValidity(): void
    {
        $rec = new CalRecurrence($this->recIds['yearly-weekday']);
        $this->assertTrue($rec->isValid());
        $rec->setYearlyWeekMonth('');
        $this->assertFalse($rec->isValid());
    }

    public function testFieldComparison(): void
    {
        $rec = new CalRecurrence($this->recIds['yearly-date']);
        $oldRec = new CalRecurrence($this->recIds['yearly-weekday']);
        $result = $rec->compareFields($oldRec);
        $this->assertEquals([
            'name', '_yearlyType', '_dateOfYear'
        ], $result);
        $result = $oldRec->compareFields($rec);
        $this->assertEquals([
            'name', '_yearlyType', '_yearlyWeekdayValue', '_yearlyWeekMonth'
        ], $result);
    }

    public function testEventCreationDaily(): void
    {
        $rec = new CalRecurrence($this->recIds['daily']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $this->assertEquals(5, count($events));
        $last = array_pop($events);
        $this->assertEquals(date('Y-m-d', strtotime('+4 days')), date('Y-m-d', $last['start']));
    }

    public function testEventCreationWeekly(): void
    {
        $rec = new CalRecurrence($this->recIds['weekly']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $last = array_pop($events);
        $this->assertEquals(3, date('w', $last['start']));
        $this->assertEquals(date('Y-m-d', strtotime('previous Wednesday', strtotime('+2 months'))), date('Y-m-d', $last['start']));
    }

    public function testEventCreationMonthlyDate(): void
    {
        $rec = new CalRecurrence($this->recIds['monthly-date']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $last = array_pop($events);
        $this->assertEquals(10, date('j', $last['start']));
    }

    public function testEventCreationMonthlyWeekday(): void
    {
        $rec = new CalRecurrence($this->recIds['monthly-weekday']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $last = array_pop($events);
        $this->assertEquals(date('Y-m-d', strtotime('next Tuesday', strtotime('next Tuesday', strtotime(date('Y-m-01', $last['start']))))), date('Y-m-d', $last['start']));
    }

    public function testEventCreationYearlyDate(): void
    {
        $rec = new CalRecurrence($this->recIds['yearly-date']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $last = array_pop($events);
        $this->assertEquals('09-15', date('m-d', $last['start']));
    }

    public function testEventCreationYearlyWeekday(): void
    {
        $rec = new CalRecurrence($this->recIds['yearly-weekday']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $last = array_pop($events);
        $this->assertEquals(date('m-d', strtotime('last Friday of June')), date('m-d', $last['start']));
    }

    public function testEventComparison(): void
    {
        self::markTestSkipped("Skip this test until upstream issue https://github.com/sabre-io/vobject/issues/626 is fixed.  See https://gitlab.com/tikiwiki/tiki/-/commit/ec7414275405a8dbfcd798775c18689ff2dbb3b4#note_1539871797 benoitg - 2023-09-01");
        foreach ($this->recIds as $type => $id) {
            $rec = new CalRecurrence($id);
            $rec->createEvents();
            $events = $this->getEventsByRecurrence($rec->getId());
            foreach ($events as $i => $evt) {
                $result = $rec->compareFieldsOfEvent($evt, $rec);
                if ($i == 0) {
                    foreach ($result as $key => $field) {
                        if (substr($field, 0, 1) == '_') {
                            unset($result[$key]);
                        }
                    }
                }
                $this->assertEquals([], $result);
            }
        }
    }

    public function testEventDiffComparisonWeekly(): void
    {
        $rec = new CalRecurrence($this->recIds['weekly']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $rec->setWeekdays('MO');
        $result = $rec->compareFieldsOfEvent(array_pop($events), $rec);
        $this->assertEquals(['_weekdays'], $result);
    }

    public function testEventDiffComparisonMonthly(): void
    {
        $rec = new CalRecurrence($this->recIds['monthly-date']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $rec->setDayOfMonth(20);
        $result = $rec->compareFieldsOfEvent(array_pop($events), $rec);
        $this->assertEquals(['_dayOfMonth'], $result);
    }

    public function testEventDiffComparisonYearly(): void
    {
        $rec = new CalRecurrence($this->recIds['yearly-weekday']);
        $rec->createEvents();
        $events = $this->getEventsByRecurrence($rec->getId());
        $rec->setYearlyWeekdayValue('3WE');
        $rec->setYearlyWeekMonth(1);
        $result = $rec->compareFieldsOfEvent(array_pop($events), $rec);
        $this->assertEquals(['_yearlyWeekdayValue', '_yearlyWeekMonth'], $result);
    }

    private function getEventsByRecurrence($id)
    {
        $query = "SELECT calitemId,calendarId, start, end, allday, locationId, categoryId, nlId, priority, status, url, lang, name, description, "
                 . "user, created, lastModif, changed, recurrenceStart "
                 . "FROM tiki_calendar_items WHERE recurrenceId = ? ORDER BY start";
        $bindvars = [(int)$id];
        return TikiLib::lib('calendar')->fetchAll($query, $bindvars);
    }
}
