<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\TikiDate;
use Tiki\SabreDav\CaldavClient;

/**
 * This class handles CRUD operations on availability slots for the current user.
 * Storage is using special WebDAV property 'calendar-availability' on the user's inbox.
 * This allows iTip clients to check availabiltiy of users when inviting them to events.
 *
 * Example iTip VFREEBUSY request and response:
curl -X POST -u xyz:pass -H 'Content-Type: text/calendar' --data-raw 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Example Corp.//CalDAV Client//EN
METHOD:REQUEST
BEGIN:VFREEBUSY
UID:4FD3AD926350
DTSTAMP:20230918T190420Z
DTSTART:20230918T000000Z
DTEND:20230928T000000Z
ORGANIZER;CN="Cyrus Daboo":mailto:organizer@example.org
ATTENDEE;CN="Wilfredo Sanchez Vega":mailto:attendee@example.org
END:VFREEBUSY
END:VCALENDAR
' https://TIKI-SITE/tiki-caldav.php/calendars/attendee/outbox
 *
 * Response:
<?xml version="1.0" encoding="utf-8"?>
<cal:schedule-response xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns" xmlns:cal="urn:ietf:params:xml:ns:caldav" xmlns:cs="http://calendarserver.org/ns/">
  <cal:response>
    <cal:recipient>
      <d:href>mailto:attendee@example.org</d:href>
    </cal:recipient>
    <cal:request-status>2.0;Success</cal:request-status>
    <cal:calendar-data>BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 4.5.3//EN
CALSCALE:GREGORIAN
METHOD:REPLY
BEGIN:VFREEBUSY
DTSTART:20230918T000000Z
DTEND:20230928T000000Z
DTSTAMP:20230918T084650Z
FREEBUSY;FBTYPE=BUSY-UNAVAILABLE:20230920T224700Z/20230921T055000Z
FREEBUSY;FBTYPE=BUSY-UNAVAILABLE:20230921T100000Z/20230921T113000Z
FREEBUSY;FBTYPE=BUSY-UNAVAILABLE:20230921T150000Z/20230922T055000Z
FREEBUSY;FBTYPE=BUSY-UNAVAILABLE:20230922T100000Z/20230922T113000Z
FREEBUSY;FBTYPE=BUSY-UNAVAILABLE:20230922T150000Z/20230923T113000Z
FREEBUSY;FBTYPE=BUSY-UNAVAILABLE:20230923T150000Z/20230924T113000Z
FREEBUSY;FBTYPE=BUSY-UNAVAILABLE:20230924T150000Z/20230925T055000Z
FREEBUSY;FBTYPE=BUSY-UNAVAILABLE:20230925T100000Z/20230925T113000Z
FREEBUSY;FBTYPE=BUSY-UNAVAILABLE:20230925T150000Z/20230926T055000Z
FREEBUSY;FBTYPE=BUSY-UNAVAILABLE:20230926T100000Z/20230926T113000Z
FREEBUSY;FBTYPE=BUSY-UNAVAILABLE:20230926T150000Z/20230927T055000Z
FREEBUSY;FBTYPE=BUSY-UNAVAILABLE:20230927T100000Z/20230927T113000Z
FREEBUSY;FBTYPE=BUSY-UNAVAILABLE:20230927T150000Z/20230928T000000Z
ATTENDEE:mailto:attendee@example.org
UID:4FD3AD926350
ORGANIZER;CN=Cyrus Daboo:mailto:organizer@example.org
END:VFREEBUSY
END:VCALENDAR
</cal:calendar-data>
  </cal:response>
</cal:schedule-response>
 *
 * TODO: implement/copy availability components to existing calendars as well, so free-busy REPORTs
 * on a calendar can also take into account VAVAILABILITY components specified in the user's INBOX.
 *
 * Example free-busy REPORT:
curl -X REPORT -H "Depth: 1" -H "Content-type: application/xml" -u xyz:pass --data-raw "<?xml version='1.0' encoding='utf-8' ?>
<C:free-busy-query xmlns:C='urn:ietf:params:xml:ns:caldav'>
   <C:time-range start = '20230808T120000Z' end='20230928T120000Z'/>
</C:free-busy-query>" https://TIKI-SITE/tiki-caldav.php/calendars/xyz/calendar-1
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 4.5.3//EN
CALSCALE:GREGORIAN
BEGIN:VFREEBUSY
DTSTART:20230808T120000Z
DTEND:20230928T120000Z
DTSTAMP:20230918T090126Z
FREEBUSY:20230814T160000Z/20230814T170000Z
FREEBUSY:20230821T120000Z/20230821T130000Z
FREEBUSY:20230821T160000Z/20230821T170000Z
FREEBUSY:20230828T160000Z/20230828T170000Z
END:VFREEBUSY
END:VCALENDAR
 */
class Services_Calendar_AvailabilityController extends Services_Calendar_BaseController
{
    public function setUp(): void
    {
        parent::setUp();

        Services_Exception_Denied::checkAuth();
    }

    public function action_index()
    {
        global $user;

        $definitions = [];

        $client = new CaldavClient();
        $result = $client->getAvailability($user);

        if ($result) {
            $allgood = true;
            if (isset($result->VAVAILABILITY) && is_iterable($result->VAVAILABILITY)) {
                foreach ($result->VAVAILABILITY as $vavailability) {
                    try {
                        $definitions[] = $this->convertAvailability($vavailability);
                    } catch (Exception $e) {
                        $result->remove($vavailability);
                        $allgood = false;
                    }
                }
            } else {
                //Handle cases where VAVAILABILITY is null or non-iterable
                $allgood = false;
            }
            if (! $allgood) {
                $client->saveAvailability($user, $result);
            }
        }
        return [
            'title' => tr('Manage Personal Availability'),
            'definitions' => $definitions
        ];
    }

    public function action_create($input)
    {
        global $user;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $client = new CaldavClient();
            $result = $client->getAvailability($user);
            if (! $result) {
                $result = $this->createAvailability();
            }
            $this->createAvailabilityFromInput($input, $result);
            $client->saveAvailability($user, $result);

            Feedback::success(tr('Availability component saved.'));

            return [
                'extra' => 'refresh',
            ];
        }
        return [
            'title' => 'Create Availability Slot',
            'definition' => [
                'dtstart' => '',
                'dtend' => '',
            ],
            'calendars' => $this->prepareCalendarList(),
            'displayTimezone' => TikiLib::lib('tiki')->get_display_timezone(),
        ];
    }

    public function action_edit($input)
    {
        global $user;

        $client = new CaldavClient();
        $result = $client->getAvailability($user);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (! $result) {
                $result = $this->createAvailability();
            }
            foreach ($result->VAVAILABILITY as $vavailability) {
                if ((string)$vavailability->UID == (string)$input->uid->raw()) {
                    $result->remove($vavailability);
                    $this->createAvailabilityFromInput($input, $result);
                }
            }
            $client->saveAvailability($user, $result);

            Feedback::success(tr('Availability component saved.'));

            return [
                'extra' => 'refresh',
            ];
        }

        $definition = [];

        if ($result) {
            foreach ($result->VAVAILABILITY as $vavailability) {
                $def = $this->convertAvailability($vavailability, true);
                if ($def['uid'] == $input->uid->raw()) {
                    $definition = $def;
                    break;
                }
            }
        }
        return [
            'title' => 'Edit Availability Slot',
            'definition' => $definition,
            'calendars' => $this->prepareCalendarList(),
            'displayTimezone' => TikiLib::lib('tiki')->get_display_timezone(),
        ];
    }

    public function action_delete($input)
    {
        global $user;

        $uid = $input->uid->text();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $client = new CaldavClient();
            $result = $client->getAvailability($user);
            if ($result) {
                foreach ($result->VAVAILABILITY as $vavailability) {
                    if ((string)$vavailability->UID == $uid) {
                        $result->remove($vavailability);
                    }
                }
                $client->saveAvailability($user, $result);
            }
            Feedback::success(tr('Availability component removed.'));
        }

        return [
            'title' => 'Remove availability component?',
            'uid' => $uid,
        ];
    }

    public function action_rrule($input)
    {
        $dtstart = date('Ymd\THis\Z', $input->start->raw());
        $rrule = $input->rrule->text();
        $calendar = Sabre\VObject\Reader::read("BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 2.0//EN
BEGIN:VAVAILABILITY
UID:temp
DTSTART:$dtstart
RRULE:$rrule
END:VAVAILABILITY
END:VCALENDAR");
        $rec = Tiki\SabreDav\Utilities::mapRRuleToRecurrence($calendar->VAVAILABILITY);
        $rec->setStartPeriod(TikiDate::getStartDay($calendar->VAVAILABILITY->DTSTART->getDateTime()->getTimeStamp()));
        return [
            'title' => 'Specify Recurrence Rule',
            'recurrence' => $rec->toArray(),
            'recurrent' => 1,
            'daynames' => $this->daynames,
            'monthnames' => $this->monthnames,
            'uid' => $input->uid->raw(),
        ];
    }

    public function action_rrule_save($input)
    {
        $recurrence = $this->createRecurrenceFromInput($input);
        $vcalendar = $recurrence->constructVCalendar();
        return [
            'rrule' => (string)$vcalendar->VEVENT->RRULE,
            'uid' => $input->uid->raw(),
        ];
    }

    public function action_new_availability()
    {
        $uid = 'vavailability-' . Sabre\VObject\UUIDUtil::getUUID();
        return [
            'uid' => $uid,
            'displayTimezone' => TikiLib::lib('tiki')->get_display_timezone(),
        ];
    }

    public function action_check($input)
    {
        $calitem = $input->asArray('calitem');
        $calitem = $this->processParticipants($calitem);
        $calitem['created'] = $calitem['lastModif'] = time();
        $vcalendar = Tiki\SabreDav\Utilities::constructCalendarData($calitem);

        $client = new CaldavClient();
        $slots = $client->getFreeBusyReport($vcalendar);
        $periods = [];
        foreach ($slots as $recipient => $list) {
            foreach ($list['slots'] as $slot) {
                if (! in_array($slot['start'], $periods)) {
                    $periods[] = $slot['start'];
                }
                if (! in_array($slot['end'], $periods)) {
                    $periods[] = $slot['end'];
                }
            }
        }
        sort($periods);

        $availability = [];
        foreach ($slots as $recipient => $list) {
            $availability[$recipient] = [];
            foreach ($periods as $time) {
                $busy = false;
                foreach ($list['slots'] as $item) {
                    if ($time >= $item['start'] && $time < $item['end']) {
                        $busy = $item['fbtype'] ?? true;
                        break;
                    }
                }
                $availability[$recipient][$time] = $busy;
            }
        }

        return [
            'title' => 'Availability Report',
            'slots' => $slots,
            'periods' => $periods,
            'availability' => $availability,
        ];
    }

    protected function convertAvailability($vavailability, $edit_mode = false)
    {
        $definition = [
            'uid' => strval($vavailability->UID),
            'summary' => strval($vavailability->SUMMARY),
            'description' => strval($vavailability->DESCRIPTION),
            'priority' => strval($vavailability->PRIORITY),
            'duration' => strval($vavailability->DURATION),
            'calendarId' => strval($vavailability->{'X-Tiki-CalendarId'}),
            'dtstamp' => '',
            'dtstart' => '',
            'dtend' => '',
        ];
        if (isset($vavailability->DTSTAMP)) {
            $definition['dtstamp'] = $vavailability->DTSTAMP->getDateTime()->getTimestamp();
        }
        if (isset($vavailability->DTSTART)) {
            $definition['dtstart'] = $vavailability->DTSTART->getDateTime()->getTimestamp();
        }
        if (isset($vavailability->DTEND)) {
            $definition['dtend'] = $vavailability->DTEND->getDateTime()->getTimestamp();
        }
        $definition['available'] = [];
        if (isset($vavailability->AVAILABLE)) {
            foreach ($vavailability->AVAILABLE as $available) {
                $av = [
                    'uid' => strval($available->UID),
                    'summary' => strval($available->SUMMARY),
                    'description' => strval($available->DESCRIPTION),
                    'duration' => strval($available->DURATION),
                    'rrule' => $available->RRULE,
                    'rrule_string' => (string)$available->RRULE,
                    'dtstamp' => '',
                    'dtstart' => '',
                    'dtend' => '',
                ];
                if (isset($available->DTSTAMP)) {
                    $av['dtstamp'] = $available->DTSTAMP->getDateTime()->getTimestamp();
                }
                if (isset($available->DTSTART)) {
                    $av['dtstart'] = $available->DTSTART->getDateTime()->getTimestamp();
                }
                if (isset($available->DTEND)) {
                    $av['dtend'] = $available->DTEND->getDateTime()->getTimestamp();
                }
                if (isset($available->{'X-Tiki-Slots'})) {
                    $av['slots'] = strval($available->{'X-Tiki-Slots'});
                }
                $definition['available'][] = $av;
            }
        }
        return $definition;
    }

    protected function createAvailabilityFromInput($input, $result)
    {
        $start = $input->dtstart->int();
        if (! $start) {
            $start = time();
        }

        if ($input->dtend->int()) {
            $end = $input->dtend->int();
        } else {
            $end = '';
        }

        $uid = $input->uid->raw();
        if (empty($uid)) {
            $uid = 'vavailability-' . Sabre\VObject\UUIDUtil::getUUID();
        }

        $availability = [
            'UID' => $uid,
            'SUMMARY' => $input->summary->text(),
            'DESCRIPTION' => $input->description->text(),
            'DTSTAMP' => new \DateTime(),
        ];
        if ($start) {
            $availability['DTSTART'] = \DateTime::createFromFormat('U', $start);
        }
        if ($end) {
            $availability['DTEND'] = \DateTime::createFromFormat('U', $end);
        }
        if ($input->duration->text()) {
            $availability['DURATION'] = $input->duration->text();
        }
        if ($input->calendarId->int()) {
            $availability['X-Tiki-CalendarId'] = $input->calendarId->int();
        }
        $vavailability = $result->add('VAVAILABILITY', $availability);
        $available = $input->asArray('available');
        foreach ($available['rrule'] as $uid => $rrule) {
            $record = [
                'UID' => $uid,
                'SUMMARY' => $available['summary'][$uid],
                'DESCRIPTION' => $available['description'][$uid],
                'RRULE' => $rrule,
            ];
            if ($available['dtstart'][$uid]) {
                $start = $available['dtstart'][$uid];
                $record['DTSTART'] = \DateTime::createFromFormat('U', $start);
            }
            if ($available['dtend'][$uid]) {
                $end = $available['dtend'][$uid];
                $record['DTEND'] = \DateTime::createFromFormat('U', $end);
            }
            if ($available['duration'][$uid]) {
                $record['DURATION'] = $available['duration'][$uid];
            }
            if ($available['slots'][$uid]) {
                $record['X-Tiki-Slots'] = $available['slots'][$uid];
            }
            $vavailability->add('AVAILABLE', $record);
        }
        return $vavailability;
    }

    protected function createAvailability()
    {
        $component = Sabre\VObject\Reader::read("BEGIN:VCALENDAR\nEND:VCALENDAR");
        return $component;
    }

    protected function prepareCalendarList()
    {
        $rawcals = TikiLib::lib('calendar')->list_calendars();
        $rawcals['data'] = Perms::filter(
            ['type' => 'calendar'],
            'object',
            $rawcals['data'],
            ['object' => 'calendarId'],
            'view_calendar'
        );
        return $rawcals['data'];
    }
}
