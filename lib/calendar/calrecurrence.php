<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 *
 */
class CalRecurrence extends TikiLib
{
    private $id;
    private $calendarId;
    private $start;
    private $end;
    private $allday;
    private $locationId;
    private $categoryId;
    private $nlId;
    private $priority;
    private $status;
    private $url;
    private $lang;
    private $name;
    private $description;
    private $daily;
    private $days;
    private $weekly;
    private $weeks;
    private $weekdays;
    private $monthly;
    private $months;
    private $dayOfMonth;
    private $monthlyType; //enum('date', 'weekday', 'firstlastweekday')
    private $monthlyWeekdayValue; //Format => (-) + 1digit + 2 letters for weekday (1MO for every 1st Monday or -1TH for last Thursday of each month )
    private $monthlyFirstlastWeekdayValue;
    private $yearly;
    private $years;
    private $yearlyType; //enum('date', 'weekday', 'firstlastweekday')
    private $dateOfYear; // format is mmdd
    private $yearlyWeekdayValue; //Format => (-) + 1digit + 2 letters for weekday (1MO for every 1st Monday or -1TH for last Thursday of each month )
    private $yearlyFirstlastWeekdayValue;
    private $yearlyWeekMonth;
    private $nbRecurrences;
    private $startPeriod;
    private $endPeriod;
    private $user;
    private $created;
    private $lastModif;
    private $initialItem;
    private $uid;
    private $uri;
    private $recurrenceDstTimezone;

    /**
     * @param $param
     */
    public function __construct($param = -1)
    {
        parent::__construct();
        if ($param > 0) {
            $this->setId($param);
        }
        $this->setInitialItem([]);
        $this->load();
    }

    public function load()
    {
        $dataExists = false;
        if ($this->getId() > 0) {
            $query = "SELECT calendarId, start, end, allday, locationId, categoryId, nlId, priority, status, url, lang, name, description, daily, days,"
                     . "weekly, weeks, weekdays, monthly, months, dayOfMonth, monthlyType, monthlyWeekdayValue, monthlyFirstlastWeekdayValue, yearly, years, yearlyType, dateOfYear,"
                     . "yearlyWeekdayValue, yearlyFirstlastWeekdayValue, yearlyWeekMonth, nbRecurrences, startPeriod, endPeriod, user, created, lastModif, uri, uid, recurrenceDstTimezone "
                     . "FROM tiki_calendar_recurrence WHERE recurrenceId = ?";
            $result = $this->query($query, [(int)$this->getId()]);
            if ($row = $result->fetchRow()) {
                $dataExists = true;
                $this->setCalendarId($row['calendarId']);
                $this->setStart($row['start']);
                $this->setEnd($row['end']);
                $this->setAllday($row['allday']);
                $this->setLocationId($row['locationId']);
                $this->setCategoryId($row['categoryId']);
                $this->setNlId($row['nlId']);
                $this->setPriority($row['priority']);
                $this->setStatus($row['status']);
                $this->setUrl($row['url']);
                $this->setLang($row['lang']);
                $this->setName($row['name']);
                $this->setDescription($row['description']);
                $this->setDaily($row['daily'] == 1);
                $this->setDays($row['days']);
                $this->setWeekly($row['weekly'] == 1);
                $this->setWeeks($row['weeks']);
                $this->setWeekdays(is_null($row['weekdays']) ? '' : $row['weekdays']);
                $this->setMonthly($row['monthly'] == 1);
                $this->setMonths($row['months']);
                $this->setDayOfMonth($row['dayOfMonth']);
                $this->setMonthlyType($row['monthlyType']);
                $this->setMonthlyWeekdayValue($row['monthlyWeekdayValue']);
                $this->setmonthlyFirstlastWeekdayValue($row['monthlyFirstlastWeekdayValue']);
                $this->setYearly($row['yearly'] == 1);
                $this->setYears($row['years']);
                $this->setYearlyType($row['yearlyType']);
                $this->setDateOfYear($row['dateOfYear']);
                $this->setYearlyWeekdayValue($row['yearlyWeekdayValue']);
                $this->setyearlyFirstlastWeekdayValue($row['yearlyFirstlastWeekdayValue']);
                $this->setYearlyWeekMonth($row['yearlyWeekMonth']);
                $this->setNbRecurrences($row['nbRecurrences']);
                $this->setStartPeriod($row['startPeriod']);
                $this->setEndPeriod($row['endPeriod']);
                $this->setUser($row['user']);
                $this->setCreated($row['created']);
                $this->setLastModif($row['lastModif']);
                $this->setUri($row['uri']);
                $this->setUid($row['uid']);
                $this->setRecurenceDstTimezone($row['recurrenceDstTimezone']);
            }
        }
        if (! $this->getId() || ! $dataExists) {
            $this->setCalendarId(0);
            $this->setStart(0);
            $this->setEnd(0);
            $this->setAllday(0);
            $this->setLocationId(0);
            $this->setCategoryId(0);
            $this->setNlId(0);
            $this->setPriority(0);
            $this->setStatus(0);
            $this->setUrl('');
            $this->setLang('');
            $this->setName('');
            $this->setDescription('');
            $this->setDaily(0);
            $this->setDays(1);
            $this->setWeekly(0);
            $this->setWeeks(1);
            $this->setWeekdays('');
            $this->setMonthly(0);
            $this->setMonths(1);
            $this->setDayOfMonth('');
            $this->setMonthlyType(null);
            $this->setMonthlyWeekdayValue(0);
            $this->setMonthlyFirstlastWeekdayValue(0);
            $this->setYearly(0);
            $this->setYears(1);
            $this->setYearlyType(null);
            $this->setDateOfYear(0);
            $this->setYearlyWeekdayValue(" ");
            $this->setYearlyFirstlastWeekdayValue(0);
            $this->setYearlyWeekMonth(0);
            $this->setNbRecurrences(0);
            $this->setStartPeriod(0);
            $this->setEndPeriod(0);
            $this->setUser('');
            $this->setCreated(0);
            $this->setLastModif(0);
            $this->setUri('');
            $this->setUid('');
            $this->setRecurenceDstTimezone('');
        }
    }

    public function updateDetails($data)
    {
        $this->setCalendarId($data['calendarId']);
        $this->setStart(\DateTime::createFromFormat('U', $data['start'])->setTimezone(new \DateTimeZone('UTC'))->format('Hi'));
        $this->setEnd(\DateTime::createFromFormat('U', $data['end'])->setTimezone(new \DateTimeZone('UTC'))->format('Hi'));
        if (isset($data['newloc'])) {
            $this->setLocationId($data['newloc']);
        }
        if (isset($data['newcat'])) {
            $this->setCategoryId($data['newcat']);
        }
        if (isset($data['priority'])) {
            $this->setPriority($data['priority']);
        }
        if (isset($data['status'])) {
            $this->setStatus($data['status']);
        }
        if (isset($data['lang'])) {
            $this->setLang($data['lang']);
        }
        if (isset($data['nlId'])) {
            $this->setNlId($data['nlId']);
        }
        if (isset($data['url'])) {
            $this->setUrl($data['url']);
        }
        if (isset($data['name'])) {
            $this->setName($data['name']);
        }
        if (isset($data['description'])) {
            $this->setDescription($data['description']);
        }
        if (isset($data['user'])) {
            $this->setUser($data['user']);
        }
        if (isset($data['uri'])) {
            $this->setUri($data['uri']);
        }
        if (isset($data['uid'])) {
            $this->setUid($data['uid']);
        }
    }

    /**
     * When updating the recurrence rule,
     * we are offered the the option to update all the recurrent events already created
     * (i.e. $updateManuallyChanged = true), or only the events for which the changes on the rules
     * have no incidence on the changes done manually (i.e. fields changed in the rule are not the fields changed
     * in the event)
     */
    public function save($updateManuallyChangedEvents = false)
    {
        if (! $this->isValid()) {
            return false;
        }
        if ($this->getId() > 0) {
            return $this->update($updateManuallyChangedEvents);
        }
        return $this->create();
    }

    /**
     * Validation before storing (or updating) to the database.
     * returns true if succeeds, false otherwise
     */
    public function isValid()
    {
        // should be related to a calendar
        if (! ($this->getCalendarId() > 0)) {
            return false;
        }
        // should have valid start and end date
        if (
            ! ($this->isAllday())
             && (! ($this->getStart() >= 0) || ! ($this->getEnd() >= 0) || ($this->getStart() > 2359) || ($this->getEnd() > 2359))
        ) {
            return false;
        }
        // should be recurrent on "some" basis
        if (! $this->isDaily() && ! $this->isWeekly() && ! $this->isMonthly() && ! $this->isYearly()) {
            return false;
        }
        if ($this->isDaily() && ! $this->getDays()) {
            return false;
        }
        if ($this->isWeekly() && ! $this->getWeeks()) {
            return false;
        }
        if ($this->isMonthly() && ! $this->getMonths()) {
            return false;
        }
        if ($this->isYearly() && ! $this->getYears()) {
            return false;
        }
        if ($this->isWeekly()) {
            // recurrence should be correctly defined
            $weekdays = $this->getWeekdays();
            $weekdays = array_filter($weekdays, function ($day) {
                return in_array($day, ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA']);
            });
        }
        //Set monthly weekday possible value
        $possibleWeekdayValues = [];
        foreach (['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'] as $day) {
            $possibleWeekdayValues[] = '-1' . $day;
            for ($i = 1; $i < 6; $i++) {
                $possibleWeekdayValues[] = $i . $day;
            }
        }
        $invalidDaysOfMonth = array_filter(explode(',', $this->getDayOfMonth()), function ($d) {
            return $d > 31 || $d < 1;
        });
        if (
            ($this->isWeekly() && empty($weekdays))
            || ($this->isMonthly() && $this->getMonthlyType() == 'date' && (is_null($this->getDayOfMonth()) || $invalidDaysOfMonth || $this->getDayOfMonth() == ''))
            || ($this->isMonthly() && $this->getMonthlyType() == 'weekday' && (is_null($this->getMonthlyWeekdayValue()) || ! in_array($this->getMonthlyWeekdayValue(), $possibleWeekdayValues)))
            || ($this->isMonthly() && $this->getMonthlyType() == 'firstlastweekday' && (is_null($this->getMonthlyFirstlastWeekdayValue())))
            || ($this->isYearly() && $this->getYearlyType() == 'date' && (is_null($this->getDateOfYear()) || $this->getDateOfYear() > 1231 || $this->getDateOfYear() < 0101 || $this->getDateOfYear() == ''))
            || ($this->isYearly() && $this->getYearlyType() == 'weekday' && (is_null($this->getYearlyWeekdayValue()) || ! in_array($this->getYearlyWeekdayValue(), $possibleWeekdayValues) || empty($this->getYearlyWeekMonth())))
            || ($this->isYearly() && $this->getYearlyType() == 'firstlastweekday' && (is_null($this->getYearlyFirstlastWeekdayValue()) || empty($this->getYearlyWeekMonth())))
        ) {
            return false;
        }
        // recurrence period should be defined
        if (
            (is_null($this->getNbRecurrences()) || ($this->getNbRecurrences() == '') || ($this->getNbRecurrences() == 0))
            && (is_null($this->getEndPeriod()) || ($this->getEndPeriod() == '') || ($this->getEndPeriod() < $this->getStartPeriod()))
        ) {
            return false;
        }
        //
        if (is_null($this->getNlId())) {
            return false;
        }
        // should inform the language
        if (is_null($this->getLang()) || $this->getLang() == "") {
            return false;
        }
        // should have a name
        if (is_null($this->getName()) || $this->getName() == "") {
            return false;
        }
        return true;
    }

    /**
     * @param null $fromTime
     * @return mixed
     */
    public function delete($fromTime = null)
    {
        global $user;
        $tx = TikiDb::get()->begin();

        if (is_null($fromTime)) {
            $fromTime = time();
        }

        $calendarlib = TikiLib::lib('calendar');
        $tiki_calendar_items = TikiDb::get()->table('tiki_calendar_items');

        $calItemIds = $tiki_calendar_items->fetchColumn('calItemId', [
            'recurrenceId' => $this->getId(),
            'start' => $tiki_calendar_items->greaterThan($fromTime),
        ]);

        foreach ($calItemIds as $calItemId) {
            $calendarlib->drop_item($user, $calItemId, true);
        }

        // this seems to leave ones in the past alone by default but detatches them from the recurrence rule (odd)
        $query = "UPDATE tiki_calendar_items SET recurrenceId = NULL WHERE recurrenceId = ?";
        $bindvars = [(int)$this->getId()];
        $this->query($query, $bindvars);
        $query = "DELETE FROM tiki_calendar_recurrence WHERE recurrenceId = ?";
        $bindvars = [(int)$this->getId()];
        $ret = $this->query($query, $bindvars);

        $tx->commit();

        return $ret;
    }

    /**
     * @return bool
     */
    private function create()
    {
        $query = "INSERT INTO tiki_calendar_recurrence (calendarId, start, end, allday, locationId, categoryId, nlId, priority, status, url, lang, name, description, "
                 . "daily, days, weekly, weeks, weekdays, monthly, months, dayOfMonth, monthlyType, monthlyWeekdayValue, monthlyFirstlastWeekdayValue, yearly, years, yearlyType, dateOfYear, "
                 . "yearlyWeekdayValue, yearlyFirstlastWeekdayValue, yearlyWeekMonth, nbRecurrences, startPeriod, endPeriod, user, created, lastModif, uri, uid, recurrenceDstTimezone) "
                 . "VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $now = $this->now;
        $bindvars = [
                        $this->getCalendarId(),
                        $this->getStart(),
                        $this->getEnd(),
                        $this->isAllday() ? 1 : 0,
                        $this->getLocationId() ?: 0,
                        $this->getCategoryId() ?: null,
                        $this->getNlId(),
                        $this->getPriority(),
                        $this->getStatus(),
                        $this->getUrl(),
                        $this->getLang(),
                        $this->getName(),
                        $this->getDescription(),
                        $this->isDaily() ? 1 : 0,
                        $this->getDays(),
                        $this->isWeekly() ? 1 : 0,
                        $this->getWeeks(),
                        implode(',', $this->getWeekdays()),
                        $this->isMonthly() ? 1 : 0,
                        $this->getMonths(),
                        $this->getDayOfMonth(),
                        $this->getMonthlyType(),
                        $this->getMonthlyWeekdayValue(),
                        $this->getMonthlyFirstlastWeekdayValue(),
                        $this->isYearly() ? 1 : 0,
                        $this->getYears(),
                        $this->getYearlyType(),
                        $this->getDateOfYear(),
                        $this->getYearlyWeekdayValue(),
                        $this->getYearlyFirstlastWeekdayValue(),
                        $this->getYearlyWeekMonth(),
                        $this->getNbRecurrences(),
                        $this->getStartPeriod(),
                        $this->getEndPeriod(),
                        $this->getUser(),
                        $now,
                        $now,
                        $this->getUri(),
                        $this->getUid(),
                        $this->getRecurenceDstTimezone(),
                     ];
        $result = $this->query($query, $bindvars);
        if ($result) {
            $this->setId($this->GetOne("SELECT `recurrenceId` FROM `tiki_calendar_recurrence` WHERE `created`=?", [$now]));
            if ($this->getId() > 0) {
                // create the recurrent events
                $this->createEvents();
                return true;
            }
        }
        return false;
    }

    /**
     * @param bool $updateManuallyChangedEvents
     * @return bool
     */
    private function update($updateManuallyChangedEvents = false)
    {
        $query = "UPDATE tiki_calendar_recurrence SET calendarId = ?, start = ?, end = ?, allday = ?, locationId = ?, categoryId = ?, nlId = ?, priority = ?, status = ?, "
                 . "url = ?, lang = ?, name = ?, description = ?, daily = ?, days = ?, weekly = ?, weeks = ?, weekdays = ?, monthly = ?, months = ?, dayOfMonth = ?, monthlyType = ?, monthlyWeekdayValue = ?, monthlyFirstlastWeekdayValue = ?, yearly = ?, years = ?, yearlyType = ?, dateOfYear = ?, yearlyWeekdayValue = ?, yearlyFirstlastWeekdayValue = ?, yearlyWeekMonth = ?, nbRecurrences = ?, "
                 . "startPeriod = ?, endPeriod = ?, user = ?, lastModif = ?, uri = ?, uid = ?, recurrenceDstTimezone = ? WHERE recurrenceId = ?";
        $now = time();
        $bindvars = [
                        $this->getCalendarId(),
                        $this->getStart(),
                        $this->getEnd(),
                        $this->isAllday() ? 1 : 0,
                        $this->getLocationId(),
                        $this->getCategoryId(),
                        $this->getNlId(),
                        $this->getPriority(),
                        $this->getStatus(),
                        $this->getUrl(),
                        $this->getLang(),
                        $this->getName(),
                        $this->getDescription(),
                        $this->isDaily() ? 1 : 0,
                        $this->getDays(),
                        $this->isWeekly() ? 1 : 0,
                        $this->getWeeks(),
                        implode(',', $this->getWeekdays()),
                        $this->isMonthly() ? 1 : 0,
                        $this->getMonths(),
                        $this->getDayOfMonth(),
                        $this->getMonthlyType(),
                        $this->getMonthlyWeekdayValue(),
                        $this->getMonthlyFirstlastWeekdayValue(),
                        $this->isYearly() ? 1 : 0,
                        $this->getYears(),
                        $this->getYearlyType(),
                        $this->getDateOfYear(),
                        $this->getYearlyWeekdayValue(),
                        $this->getYearlyFirstlastWeekdayValue(),
                        $this->getYearlyWeekMonth(),
                        $this->getNbRecurrences(),
                        $this->getStartPeriod(),
                        $this->getEndPeriod(),
                        $this->getUser(),
                        $now,
                        $this->getUri(),
                        $this->getUid(),
                        $this->getRecurenceDstTimezone(),
                        $this->getId()
                     ];
        $oldRec = new CalRecurrence($this->getId()); // we'll need old version to compare fields.
        $result = $this->query($query, $bindvars);
        if ($result) {
            // update the recurrent events, according to the way to handle the already changed events
            $this->updateEvents($updateManuallyChangedEvents, $oldRec);
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function createEvents()
    {
        global $user;
        $calendarlib = TikiLib::lib('calendar');

        $vcalendar = $this->constructVCalendar($this->getRecurenceDstTimezone());
        $start = $vcalendar->VEVENT->DTSTART->getDateTime()->getTimeStamp();
        $end = $this->getEndPeriod();
        if (! $end) {
            $end = strtotime(Tiki\SabreDav\CalDAVBackend::MAX_DATE);
        }
        $dstTimezone = $this->getRecurenceDstTimezone() ? new DateTimeZone($this->getRecurenceDstTimezone()) : $vcalendar->VEVENT->DTSTART->getDateTime()->getTimezone();
        $expanded = $vcalendar->expand(DateTime::createFromFormat('U', $start), DateTime::createFromFormat('U', $end), $dstTimezone);
        $tx = TikiDb::get()->begin();
        foreach ($expanded->VEVENT as $vevent) {
            $data = [
                'calendarId'   => $this->getCalendarId(),
                'start'        => $vevent->DTSTART->getDateTime()->getTimeStamp(),
                'end'          => $vevent->DTEND->getDateTime()->getTimeStamp(),
                'locationId'   => $this->getLocationId(),
                'categoryId'   => $this->getCategoryId(),
                'nlId'         => $this->getNlId(),
                'priority'     => $this->getPriority(),
                'status'       => $this->getStatus(),
                'url'          => $this->getUrl(),
                'lang'         => $this->getLang(),
                'name'         => $this->getName(),
                'description'  => $this->getDescription(),
                'user'         => $this->getUser(),
                'created'      => $this->getCreated(),
                'lastmodif'    => $this->getCreated(),
                'allday'       => $this->isAllday(),
                'recurrenceId' => $this->getId(),
                'changed'      => 0,
            ];

            $initial = $this->getInitialItem();
            $diff = array_diff($data, $initial);
            unset($diff['recurrenceId']);
            if (empty($initial['lang'])) {
                // manually created items seem to have lang == ''
                unset($diff['lang']);
            }

            if (! empty($diff)) {
                // different event, add a new one
                $calendarlib->set_item($user, null, $data, [], true);
            } else {
                // original event, update the recurrence id
                $initial['recurrenceId'] = $this->getId();
                $calendarlib->set_item($user, $initial['calitemId'], $initial);
            }
        }
        $tx->commit();
    }

    /**
     * Attempts to update expanded recurring events in db based off changes in the recurring event record.
     * Matches events by old schedule (before update) and original start date.
     * TODO: this currently does not support EXDATE exclusions (i.e. individual event deletes) from recurring schedule
     * because we match by position in the event list. In the future, this can be expanded to support EXDATE exclusions
     * in order to sync deleted events here and in Tiki\SabreDav\CalDAVBackend.
     * @param bool $updateManuallyChangedEvents
     * @param $oldRec
     */
    public function updateEvents($updateManuallyChangedEvents, $oldRec)
    {
        global $user;
        global $prefs;



        $query = "SELECT calitemId,calendarId, start, end, allday, locationId, categoryId, nlId, priority, status, url, lang, name, description, "
                 . "user, created, lastModif, changed, recurrenceStart "
                 . "FROM tiki_calendar_items WHERE recurrenceId = ? ORDER BY start";
        $bindvars = [(int)$this->getId()];
        $existing = $this->fetchAll($query, $bindvars);

        $changedFields = $this->compareFields($oldRec);

        if (! $changedFields) {
            if ($prefs['feature_categories'] == 'y') {
                $tx = TikiDb::get()->begin();
                foreach ($existing as $eventItem) {
                    TikiLib::lib('calendar')->update_item_categories($eventItem['calitemId'], $_REQUEST['cat_managed'], $_REQUEST['cat_categories'], $eventItem['name'], $eventItem['description']);
                }
                $tx->commit();
            }
            return;
        }

        $vcalendar = $oldRec->constructVCalendar($oldRec->getRecurenceDstTimezone());
        $start = $vcalendar->VEVENT->DTSTART->getDateTime()->getTimeStamp();
        $end = $oldRec->getEndPeriod();
        if (! $end) {
            $end = strtotime(Tiki\SabreDav\CalDAVBackend::MAX_DATE);
        }
        $dstTimezone = $oldRec->getRecurenceDstTimezone() ? new DateTimeZone($oldRec->getRecurenceDstTimezone()) : $vcalendar->VEVENT->DTSTART->getDateTime()->getTimezone();
        $old_expanded = $vcalendar->expand(DateTime::createFromFormat('U', $start), DateTime::createFromFormat('U', $end), $dstTimezone);

        $vcalendar = $this->constructVCalendar($this->getRecurenceDstTimezone());
        $start = $vcalendar->VEVENT->DTSTART->getDateTime()->getTimeStamp();
        $end = $oldRec->getEndPeriod();
        if (! $end) {
            $end = strtotime(Tiki\SabreDav\CalDAVBackend::MAX_DATE);
        }
        $dstTimezone = $this->getRecurenceDstTimezone() ? new DateTimeZone($this->getRecurenceDstTimezone()) : $vcalendar->VEVENT->DTSTART->getDateTime()->getTimezone();
        $new_expanded = $vcalendar->expand(DateTime::createFromFormat('U', $start), DateTime::createFromFormat('U', $end), $dstTimezone);

        $tx = TikiDb::get()->begin();
        foreach ($new_expanded->VEVENT as $key => $vevent) {
            $found = false;
            if (! empty($old_expanded->VEVENT[$key]->DTSTART)) {
                $old_start = $old_expanded->VEVENT[$key]->DTSTART->getDateTime()->getTimeStamp();
                foreach ($existing as $row) {
                    if (($row['recurrenceStart'] && $row['recurrenceStart'] == $old_start) || $row['start'] == $old_start) {
                        $found = $row;
                        break;
                    }
                }
            }

            if (! $found) {
                // create it
                $data = [
                    'calendarId'   => $this->getCalendarId(),
                    'start'        => $vevent->DTSTART->getDateTime()->getTimeStamp(),
                    'end'          => $vevent->DTEND->getDateTime()->getTimeStamp(),
                    'locationId'   => $this->getLocationId(),
                    'categoryId'   => $this->getCategoryId(),
                    'nlId'         => $this->getNlId(),
                    'priority'     => $this->getPriority(),
                    'status'       => $this->getStatus(),
                    'url'          => $this->getUrl(),
                    'lang'         => $this->getLang(),
                    'name'         => $this->getName(),
                    'description'  => $this->getDescription(),
                    'user'         => $this->getUser(),
                    'created'      => $this->getCreated(),
                    'lastmodif'    => $this->getCreated(),
                    'allday'       => $this->isAllday(),
                    'recurrenceId' => $this->getId(),
                    'changed'      => 0,
                ];
                TikiLib::lib('calendar')->set_item($user, null, $data, [], true);
            } elseif ($found['changed'] == 0 || $updateManuallyChangedEvents) {
                // update with changes
                foreach ($changedFields as $field) {
                    if (substr($field, 0, 1) != "_") {
                        $found[$field] = $this->$field;
                    }
                }
                $changedFieldsOfEvent = $this->compareFieldsOfEvent($found, $this);
                foreach ($changedFieldsOfEvent as $field) {
                    if (substr($field, 0, 1) == "_") {
                        $found['start'] = $vevent->DTSTART->getDateTime()->getTimeStamp();
                        $found['end'] = $vevent->DTEND->getDateTime()->getTimeStamp();
                        if ($found['changed']) {
                            $found['recurrenceStart'] = $found['start'];
                        }
                        break;
                    }
                }
                // keep changed flag as this event might still be changed and we only updated some of the fields here
                TikiLib::lib('calendar')->set_item($user, $found['calitemId'], $found, [], true);
            }
        }
        $tx->commit();
    }

    /**
     * Update individual events in a recurring event series that were manually tweaked in clients.
     */
    public function updateOverrides($events)
    {
        global $user;

        $existing = $this->getOverrides();

        foreach ($events as $event) {
            foreach ($existing as $row) {
                if (($row['recurrenceStart'] && $row['recurrenceStart'] == $event['recurrenceStart']) || $row['start'] == $event['recurrenceStart']) {
                    $event['calendarId'] = $row['calendarId'];
                    TikiLib::lib('calendar')->set_item($user, $row['calitemId'], $event, [], true);
                    break;
                }
            }
        }
    }

    /**
     * Get individual events in the recurring set that were overridden by users.
     */
    public function getOverrides($changed = null)
    {
        $query = "SELECT calitemId,calendarId, start, end, allday, locationId, categoryId, nlId, priority, status, url, lang, name, description, "
                 . "user, created, lastModif, changed, recurrenceStart "
                 . "FROM tiki_calendar_items WHERE recurrenceId = ?";
        $bindvars = [(int)$this->getId()];
        if (! is_null($changed)) {
            $query .= " AND changed = ?";
            $bindvars[] = $changed;
        }
        $query .= " ORDER BY start";
        return $this->fetchAll($query, $bindvars);
    }

    /**
     * @param $oldRec
     * @return array
     */
    public function compareFields($oldRec)
    {
        $result = [];
        if ($this->getCalendarId() != $oldRec->getCalendarId()) {
            $result[] = "calendarId";
        }
        if ($this->getStart() != $oldRec->getStart()) {
            $result[] = "_start";
        }
        if ($this->getEnd() != $oldRec->getEnd()) {
            $result[] = "_end";
        }
        if ($this->isAllday() != $oldRec->isAllday()) {
            $result[] = "allday";
        }
        if ($this->getLocationId() != $oldRec->getLocationId() && ! ($this->getLocationId() == '' && $oldRec->getLocationId() == 0)) {
            $result[] = "locationId";
        }
        if ($this->getCategoryId() != $oldRec->getCategoryId() && ! ($this->getCategoryId() == '' && $oldRec->getCategoryId() == 0)) {
            $result[] = "categoryId";
        }
        if ($this->getNlId() != $oldRec->getNlId()) {
            $result[] = "nlId";
        }
        if ($this->getPriority() != $oldRec->getPriority() && ! ($oldRec->getPriority() == '' && $oldRec->getPriority() == 0)) {
            $result[] = "priority";
        }
        if ($this->getStatus() != $oldRec->getStatus()) {
            $result[] = "status";
        }
        if ($this->getUrl() != $oldRec->getUrl()) {
            $result[] = "url";
        }
        if ($this->getLang() != $oldRec->getLang()) {
            $result[] = "lang";
        }
        if ($this->getName() != $oldRec->getName()) {
            $result[] = "name";
        }
        if ($this->getDescription() != $oldRec->getDescription()) {
            $result[] = "description";
        }
        if ($this->isDaily() && ($this->getDays() != $oldRec->getDays())) {
            $result[] = "_days";
        }
        if ($this->isWeekly() && ($this->getWeeks() != $oldRec->getWeeks())) {
            $result[] = "_weeks";
        }
        if ($this->isWeekly() && (implode(',', $this->getWeekdays()) != $oldRec->getWeekdays())) {
            $result[] = "_weekdays";
        }
        if ($this->isMonthly() && ($this->getMonths() != $oldRec->getMonths())) {
            $result[] = "_months";
        }
        if ($this->isMonthly() && ($this->getMonthlyType() != $oldRec->getMonthlyType())) {
            $result[] = "_monthlyType";
        }
        if ($this->isMonthly() && $this->getMonthlyType() == 'date' && ($this->getDayOfMonth() != $oldRec->getDayOfMonth())) {
            $result[] = "_dayOfMonth";
        }
        if ($this->isMonthly() && $this->getMonthlyType() == 'weekday' && ($this->getMonthlyWeekdayValue() != $oldRec->getMonthlyWeekdayValue())) {
            $result[] = "_monthlyWeekdayValue";
        }
        if ($this->isMonthly() && $this->getMonthlyType() == 'firstlastweekday' && ($this->getMonthlyFirstlastWeekdayValue() != $oldRec->getMonthlyFirstlastWeekdayValue())) {
            $result[] = "_monthlyFirstlastWeekdayValue";
        }
        if ($this->isYearly() && ($this->getYears() != $oldRec->getYears())) {
            $result[] = "_years";
        }
        if ($this->isYearly() && ($this->getYearlyType() != $oldRec->getYearlyType())) {
            $result[] = "_yearlyType";
        }
        if ($this->isYearly() && $this->getYearlyType() == 'date' && ($this->getDateOfYear() != $oldRec->getDateOfYear())) {
            $result[] = "_dateOfYear";
        }
        if ($this->isYearly() && $this->getYearlyType() == 'weekday' && ($this->getYearlyWeekdayValue() != $oldRec->getYearlyWeekdayValue())) {
            $result[] = "_yearlyWeekdayValue";
        }
        if ($this->isYearly() && ($this->getYearlyType() == 'weekday' || $this->getYearlyType() == 'firstlastweekday') && ($this->getYearlyWeekMonth() != $oldRec->getYearlyWeekMonth())) {
            $result[] = "_yearlyWeekMonth";
        }
        if ($this->isYearly() && $this->getYearlyType() == 'firstlastweekday' && ($this->getYearlyFirstlastWeekdayValue() != $oldRec->getYearlyFirstlastWeekdayValue())) {
            $result[] = "_yearlyFirstlastWeekdayValue";
        }
        return $result;
    }

    /**
     * @param $evt
     * @param $oldRec
     * @return array
     */
    public function compareFieldsOfEvent($evt, $oldRec)
    {
        $result = [];
        if ($evt['calendarId'] != $oldRec->getCalendarId()) {
            $result[] = "calendarId";
        }
        if (TikiLib::date_format2('Hi', $evt['start']) != $oldRec->getStart()) {
            $result[] = "start";
        }
        // checking the end is double check : is it the right hour ? is it the same day ?
        if ((TikiLib::date_format2('Hi', $evt['end']) != $oldRec->getEnd()) || (TikiLib::date_format2('Ymd', $evt['start']) != TikiLib::date_format2('Ymd', $evt['end']))) {
            $result[] = "end";
        }
        if ($evt['allday'] != $oldRec->isAllday()) {
            $result[] = "allday";
        }
        if ($evt['locationId'] != $oldRec->getLocationId()) {
            $result[] = "locationId";
        }
        if ($evt['categoryId'] != $oldRec->getCategoryId()) {
            $result[] = "categoryId";
        }
        if ($evt['nlId'] != $oldRec->getNlId()) {
            $result[] = "nlId";
        }
        if ($evt['priority'] != $oldRec->getPriority()) {
            $result[] = "priority";
        }
        if ($evt['status'] != $oldRec->getStatus()) {
            $result[] = "status";
        }
        if ($evt['url'] != $oldRec->getUrl()) {
            $result[] = "url";
        }
        if ($evt['lang'] != $oldRec->getLang()) {
            $result[] = "lang";
        }
        if ($evt['name'] != $oldRec->getName()) {
            $result[] = "name";
        }
        if ($evt['description'] != $oldRec->getDescription()) {
            $result[] = "description";
        }
        if (TikiLib::date_format2('Hi', $evt['start']) != str_pad($oldRec->getStart(), 4, "0", STR_PAD_LEFT)) {
            $result[] = "_start";
        }
        if (TikiLib::date_format2('Hi', $evt['end']) != str_pad($oldRec->getEnd(), 4, "0", STR_PAD_LEFT)) {
            $result[] = "_end";
        }
        if ($oldRec->isWeekly()) {
            $weekdays = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
            if (! in_array($weekdays[TikiLib::date_format2('w', $evt['start'])], $oldRec->getWeekdays())) {
                $result[] = "_weekdays";
            }
        } elseif ($oldRec->isMonthly()) {
            if ($oldRec->getMonthlyType() == 'weekday') {
                if ($this->checkWeekdayForADate($evt['start'], $oldRec->getMonthlyWeekdayValue())) {
                    $result[] = "_monthlyWeekdayValue";
                }
            } elseif ($oldRec->getMonthlyType() == 'firstlastweekday') {
                $result[] = "_monthlyFirstlastWeekdayValue";
            } else {
                $days = explode(',', $this->getDayOfMonth());
                if (! in_array(TikiLib::date_format2('j', $evt['start']), $days)) {
                    $result[] = "_dayOfMonth";
                }
            }
        } elseif ($oldRec->isYearly()) {
            if ($oldRec->getYearlyType() == 'weekday') {
                if ($this->checkWeekdayForADate($evt['start'], $oldRec->getYearlyWeekdayValue())) {
                    $result[] = "_yearlyWeekdayValue";
                }
                if (TikiLib::date_format2('n', $evt['start']) != $oldRec->getYearlyWeekMonth()) {
                    $result[] = "_yearlyWeekMonth";
                }
            } elseif ($oldRec->getYearlyType() == 'firstlastweekday') {
                $result[] = "_yearlyFirstlastWeekdayValue";
                if (TikiLib::date_format2('n', $evt['start']) != $oldRec->getYearlyWeekMonth()) {
                    $result[] = "_yearlyWeekMonth";
                }
            } else {
                if (TikiLib::date_format2('nd', $evt['start']) != $oldRec->getDateOfYear()) {
                    $result[] = "_dateOfYear";
                }
            }
        }
        return $result;
    }

    private function checkWeekdayForADate($date, $weekdayValue)
    {
        $weekdays = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
        if (TikiLib::date_format2('w', $date) != array_search(substr($weekdayValue, -2), $weekdays)) {
            return true;
        } else {
            $day = TikiLib::date_format2('j', $date);
            $total = TikiLib::date_format2('t', $date);
            if (substr($weekdayValue, 2) == '-1') {
                if ($total - $day >= 7) {
                    return true;
                }
            } else {
                if ($day - 7 * (intval(substr($weekdayValue, 0, 1)) - 1) < 0) {
                    return true;
                }
            }
        }
        return false;
    }

    public function fillUid($uid)
    {
        $this->query("update `tiki_calendar_recurrence` set `uid` = ? where `recurrenceId` = ?", [$uid, $this->getId()]);
    }

    public function getFirstItemId()
    {
        $query = "SELECT calitemId FROM `tiki_calendar_items` WHERE recurrenceId = ? ORDER BY calitemId";
        $result = $this->query($query, [(int)$this->getId()]);
        if ($row = $result->fetchRow()) {
            return $row['calitemId'];
        }
        return null;
    }

    public function constructVCalendar($timezone = null)
    {
        if (! $timezone) {
            static $calendar_timezones = [];
            if (isset($calendar_timezones[$this->getCalendarId()])) {
                $timezone = $calendar_timezones[$this->getCalendarId()];
            } else {
                $calendar = TikiLib::lib('calendar')->get_calendar($this->getCalendarId());
                $timezone = TikiLib::lib('tiki')->get_display_timezone($calendar['user']);
                $calendar_timezones[$this->getCalendarId()] = $timezone;
            }
        }
        if ($this->isAllday()) {
            $startOffset = 0;
            $endOffset = 86399;
        } else {
            $startOffset = str_pad($this->getStart(), 4, '0', STR_PAD_LEFT);
            $startOffset = substr($startOffset, 0, 2) * 60 * 60 + substr($startOffset, -2) * 60;
            $endOffset = str_pad($this->getEnd(), 4, '0', STR_PAD_LEFT);
            $endOffset = substr($endOffset, 0, 2) * 60 * 60 + substr($endOffset, -2) * 60;
        }
        // start/end period and start/end offsets are in UTC hours
        $dtzone = new DateTimeZone($timezone);
        $dtstart = DateTime::createFromFormat('U', TikiDate::getStartDay($this->getStartPeriod(), 'UTC') + $startOffset);
        $dtstart->setTimezone($dtzone);
        $dtstart->setTimestamp($dtstart->getTimestamp());
        $dtend = DateTime::createFromFormat('U', TikiDate::getStartDay($this->getStartPeriod(), 'UTC') + $endOffset);
        $dtend->setTimezone($dtzone);
        $dtend->setTimestamp($dtend->getTimestamp());

        $data = [
            'CREATED' => DateTime::createFromFormat('U', $this->getCreated() ?? 0)->format('Ymd\THis\Z'),
            'DTSTAMP' => DateTime::createFromFormat('U', $this->getLastModif() ?? 0)->format('Ymd\THis\Z'),
            'LAST-MODIFIED' => DateTime::createFromFormat('U', $this->getLastModif() ?? 0)->format('Ymd\THis\Z'),
            'SUMMARY' => $this->getName(),
            'PRIORITY' => $this->getPriority(),
            'STATUS' => \Tiki\SabreDav\Utilities::mapEventStatus($this->getStatus()),
            'TRANSP' => 'OPAQUE',
            'DTSTART' => $dtstart,
            'DTEND'   => $dtend,
            'X-Tiki-Allday' => $this->isAllday() ? 1 : 0,
        ];
        if (! empty($this->getUid())) {
            $data['UID'] = $this->getUid();
        }
        if (! empty($this->getRecurenceDstTimezone())) {
            $data['X-Tiki-RecurenceDstTimezone'] = $this->getRecurenceDstTimezone();
        }
        if (! empty($this->getDescription())) {
            $data['DESCRIPTION'] = $this->getDescription();
        }
        $locations = TikiLib::lib('calendar')->list_locations($this->getCalendarId());
        if (! empty($locations[$this->getLocationId()])) {
            $data['LOCATION'] = $locations[$this->getLocationId()];
        }
        if (! empty($this->getLocationId())) {
            $data['X-Tiki-LocationId'] = $this->getLocationId();
        }
        $categories = TikiLib::lib('calendar')->list_categories($this->getCategoryId());
        if (! empty($categories[$this->getCategoryId()])) {
            $data['CATEGORIES'] = $categories[$this->getCategoryId()];
        }
        if (! empty($this->getCategoryId())) {
            $data['X-Tiki-CategoryId'] = $this->getCategoryId();
        }
        if (! empty($this->getUrl())) {
            $data['URL'] = $this->getUrl();
        }
        if (! empty($this->getLang())) {
            $data['X-Tiki-Language'] = $this->getLang();
        }
        if (! empty($this->getRecurenceDstTimezone())) {
            $data['X-Tiki-Dst-Timezone'] = $this->getRecurenceDstTimezone();
        }

        $weekdays = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
        if ($this->isDaily()) {
            $rrule = 'FREQ=DAILY';
            if ($this->getDays() > 1) {
                $rrule .= ';INTERVAL=' . $this->getDays();
            }
        } elseif ($this->isWeekly()) {
            $rrule = 'FREQ=WEEKLY;BYDAY=' . implode(',', $this->getWeekdays());
            if ($this->getWeeks() > 1) {
                $rrule .= ';INTERVAL=' . $this->getWeeks();
            }
        } elseif ($this->isMonthly()) {
            if ($this->getMonthlyType() == 'weekday') {
                $rrule = 'FREQ=MONTHLY;BYDAY=' . $this->getMonthlyWeekdayValue();
            } elseif ($this->getMonthlyType() == 'firstlastweekday') {
                $rrule = 'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=' . $this->getMonthlyFirstlastWeekdayValue();
            } else {
                $rrule = 'FREQ=MONTHLY;BYMONTHDAY=' . $this->getDayOfMonth();
            }
            if ($this->getMonths() > 1) {
                $rrule .= ';INTERVAL=' . $this->getMonths();
            }
        } elseif ($this->isYearly()) {
            if ($this->getYearlyType() == 'weekday') {
                $rrule = 'FREQ=YEARLY;BYMONTH=' . $this->getYearlyWeekMonth() . ';BYDAY=' . $this->getYearlyWeekdayValue();
            } elseif ($this->getYearlyType() == 'firstlastweekday') {
                $rrule = 'FREQ=Yearly;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=' . $this->getYearlyFirstlastWeekdayValue() . ';BYMONTH=' . $this->getYearlyWeekMonth();
            } else {
                $doy = $this->getDateOfYear();
                $day = substr($doy, -2);
                $month = substr($doy, 0, strlen($doy) - 2);
                $rrule = 'FREQ=YEARLY;BYMONTH=' . $month . ';BYMONTHDAY=' . $day;
            }
            if ($this->getYears() > 1) {
                $rrule .= ';INTERVAL=' . $this->getYears();
            }
        } else {
            $rrule = 'FREQ=DAILY';
        }
        if ($this->getNbRecurrences() > 0) {
            $rrule .= ';COUNT=' . $this->getNbRecurrences();
        } elseif ($this->getEndPeriod() < strtotime(\Tiki\SabreDav\CalDAVBackend::MAX_DATE) - 86400) {
            $rrule .= ';UNTIL=' . DateTime::createFromFormat('U', $this->getEndPeriod())->format('Ymd\THis\Z');
        }
        $data['RRULE'] = $rrule;

        $vcalendar = new Sabre\VObject\Component\VCalendar();
        $vevent = $vcalendar->add('VEVENT', $data);

        if ((string)$vevent->UID != $this->getUid()) {
            // save UID for Tiki-generated calendar events as this must not change in the future
            // SabreDav automatically generates UID value if none is present
            $this->fillUid((string)$vevent->UID);
        }

        return $vcalendar;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'daily' => $this->isDaily(),
            'days' => $this->getDays(),
            'weekly' => $this->isWeekly(),
            'weeks' => $this->getWeeks(),
            'weekdays' => $this->getWeekdays(),
            'monthly' => $this->isMonthly(),
            'months' => $this->getMonths(),
            'dayOfMonth' => array_filter(explode(',', $this->getDayOfMonth())),
            'monthlyType' => $this->getMonthlyType(),
            'monthlyWeekdayValue' => $this->getMonthlyWeekdayValue(),
            'monthlyFirstlastWeekdayValue' => $this->getMonthlyFirstlastWeekdayValue(),
            'yearly' => $this->isYearly(),
            'years' => $this->getYears(),
            'yearlyType' => $this->getYearlyType(),
            'dateOfYear' => $this->getDateOfYear(),
            'yearlyMonth' => intval(floor($this->getDateOfYear() / 100)),
            'yearlyDay' => intval($this->getDateOfYear() - 100 * floor($this->getDateOfYear() / 100)),
            'yearlyWeekdayValue' => $this->getYearlyWeekdayValue() ?? " ",
            'yearlyFirstlastWeekdayValue' => $this->getYearlyFirstlastWeekdayValue(),
            'yearlyWeekMonth' => $this->getYearlyWeekMonth(),
            'nbRecurrences' => $this->getNbRecurrences(),
            'startPeriod' => $this->getStartPeriod(),
            'endPeriod' => $this->getEndPeriod(),
            'user' => $this->getUser(),
            'created' => $this->getCreated(),
            'lastModif' => $this->getLastModif(),
            'recurrenceDstTimezone' => $this->getRecurenceDstTimezone()
        ];
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $value
     */
    public function setId($value)
    {
        $this->id = $value;
    }

    public function getCalendarId()
    {
        return $this->calendarId;
    }

    /**
     * @param $value
     */
    public function setCalendarId($value)
    {
        $this->calendarId = $value;
    }

    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param $value
     */
    public function setStart($value)
    {
        $this->start = $value;
    }

    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @param $value
     */
    public function setEnd($value)
    {
        $this->end = $value;
    }

    public function isAllday()
    {
        return $this->allday ?? 0;
    }

    /**
     * @param $value
     */
    public function setAllday($value)
    {
        $this->allday = $value;
    }

    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * @param $value
     */
    public function setLocationId($value)
    {
        $this->locationId = $value;
    }

    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param $value
     */
    public function setCategoryId($value)
    {
        $this->categoryId = $value;
    }

    public function getNlId()
    {
        return $this->nlId;
    }

    /**
     * @param $value
     */
    public function setNlId($value)
    {
        $this->nlId = $value;
    }

    public function getPriority()
    {
        return $this->priority ?? '1';
    }

    /**
     * @param $value
     */
    public function setPriority($value)
    {
        $this->priority = $value;
    }

    public function getStatus()
    {
        return $this->status ?? '1';
    }

    /**
     * @param $value
     */
    public function setStatus($value)
    {
        $this->status = $value;
    }

    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param $value
     */
    public function setUrl($value)
    {
        $this->url = $value;
    }

    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @param $value
     */
    public function setLang($value)
    {
        $this->lang = $value;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $value
     */
    public function setName($value)
    {
        $this->name = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $value
     */
    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function isDaily()
    {
        return $this->daily;
    }

    /**
     * @param $value
     */
    public function setDaily($value)
    {
        $this->daily = $value;
    }

    /**
     * @param $value
     */
    public function setDays($value)
    {
        $this->days = $value;
    }

    public function getDays()
    {
        return $this->days;
    }

    public function isWeekly()
    {
        return $this->weekly;
    }

    /**
     * @param $value
     */
    public function setWeekly($value)
    {
        $this->weekly = $value;
    }

    /**
     * @param $value
     */
    public function setWeeks($value)
    {
        $this->weeks = $value;
    }

    public function getWeeks()
    {
        return $this->weeks;
    }

    public function getWeekdays()
    {
        return $this->weekdays;
    }

    /**
     * @param $value
     */
    public function setWeekdays($value)
    {
        $this->weekdays = array_filter(explode(',', $value));
    }

    public function isMonthly()
    {
        return $this->monthly;
    }

    /**
     * @param $value
     */
    public function setMonthly($value)
    {
        $this->monthly = $value;
    }

    /**
     * @param $value
     */
    public function setMonths($value)
    {
        $this->months = $value;
    }

    public function getMonths()
    {
        return $this->months;
    }

    public function getMonthlyType()
    {
        return $this->monthlyType;
    }

    /**
     * @param $value
     */
    public function setMonthlyType($value)
    {
        $this->monthlyType = $value;
    }

    public function getMonthlyWeekdayValue()
    {
        return $this->monthlyWeekdayValue;
    }

    /**
     * @param $value
     */
    public function setMonthlyWeekdayValue($value)
    {
        $this->monthlyWeekdayValue = $value;
    }

    public function getMonthlyFirstlastWeekdayValue()
    {
        return $this->monthlyFirstlastWeekdayValue;
    }

    /**
     * @param $value
     */
    public function setMonthlyFirstlastWeekdayValue($value)
    {
        $this->monthlyFirstlastWeekdayValue = $value;
    }

    public function getDayOfMonth()
    {
        return $this->dayOfMonth;
    }

    /**
     * @param $value
     */
    public function setDayOfMonth($value)
    {
        $this->dayOfMonth = $value;
    }

    public function isYearly()
    {
        return $this->yearly;
    }

    /**
     * @param $value
     */
    public function setYearly($value)
    {
        $this->yearly = $value;
    }

    /**
     * @param $value
     */
    public function setYears($value)
    {
        $this->years = $value;
    }

    public function getYears()
    {
        return $this->years;
    }

    public function getYearlyType()
    {
        return $this->yearlyType;
    }

    /**
     * @param $value
     */
    public function setYearlyType($value)
    {
        $this->yearlyType = $value;
    }

    public function getDateOfYear()
    {
        return $this->dateOfYear;
    }

    /**
     * @param $value
     */
    public function setDateOfYear($value)
    {
        $this->dateOfYear = $value;
    }

    public function getYearlyWeekdayValue()
    {
        return $this->yearlyWeekdayValue;
    }

    /**
     * @param $value
     */
    public function setYearlyWeekdayValue($value)
    {
        $this->yearlyWeekdayValue = $value;
    }

    public function getYearlyFirstlastWeekdayValue()
    {
        return $this->yearlyFirstlastWeekdayValue;
    }

    /**
     * @param $value
     */
    public function setYearlyFirstlastWeekdayValue($value)
    {
        $this->yearlyFirstlastWeekdayValue = $value;
    }

    public function getYearlyWeekMonth()
    {
        return $this->yearlyWeekMonth;
    }

    /**
     * @param $value
     */
    public function setYearlyWeekMonth($value)
    {
        $this->yearlyWeekMonth = $value;
    }

    public function getNbRecurrences()
    {
        return $this->nbRecurrences;
    }

    /**
     * @param $value
     */
    public function setNbRecurrences($value)
    {
        $this->nbRecurrences = $value;
    }

    public function getStartPeriod()
    {
        return $this->startPeriod;
    }

    /**
     * @param $value
     */
    public function setStartPeriod($value)
    {
        $this->startPeriod = $value ? $value : $this->now;
    }

    public function getEndPeriod()
    {
        return $this->endPeriod;
    }

    /**
     * @param $value
     */
    public function setEndPeriod($value)
    {
        $this->endPeriod = $value;
    }

    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param $value
     */
    public function setUser($value)
    {
        $this->user = $value;
    }

    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param $value
     */
    public function setCreated($value)
    {
        $this->created = $value;
    }

    public function getLastModif()
    {
        return $this->lastModif;
    }

    /**
     * @param $value
     */
    public function setLastModif($value)
    {
        $this->lastModif = $value;
    }

    /**
     * Calendar item to create recurrences from
     *
     * @return array
     */
    public function getInitialItem(): array
    {
        return $this->initialItem;
    }

    /**
     * @param array $value
     */
    public function setInitialItem(array $value)
    {
        $this->initialItem = $value;
    }

    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param $value
     */
    public function setUid($value)
    {
        $this->uid = $value;
    }

    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param $value
     */
    public function setUri($value)
    {
        $this->uri = $value;
    }

    /**
     * If specified for a recurring event, the actual event times will be set so the event is always at the same time of the day in that timezone.
     * This has nothing to do with the display or storage timezone of the event.
     */
    public function getRecurenceDstTimezone()
    {
        return $this->recurrenceDstTimezone;
    }

    /**
     * @param $value
     */
    public function setRecurenceDstTimezone($value)
    {
        $this->recurrenceDstTimezone = $value;
    }
}
