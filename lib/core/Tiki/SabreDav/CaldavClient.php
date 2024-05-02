<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\SabreDav;

use Sabre\CalDAV\Subscriptions\Subscription;
use Sabre\DAV\Client as DavClient;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Xml;
use Sabre\HTTP\Sapi;
use Sabre\HTTP\Request;
use Sabre\VObject;
use TikiLib;
use Exception;
use DateInterval;

class CaldavClient
{
    protected $authBackend;
    protected $server;

    public function __construct()
    {
        $this->authBackend = new InternalAuth();
        $this->server = Utilities::buildSabreDavServer($this->authBackend);
    }

    public function saveCalendarObject($calitem)
    {
        global $url_path;
        if (empty($calitem['calitemId'])) {
            $calitem['created'] = time();
            $uri = uniqid();
        } else {
            $uri = Utilities::getCalendarObjectUri($calitem);
            $existing = TikiLib::lib('calendar')->get_item($calitem['calitemId']);
            $calitem['created'] = $existing['created'];
        }
        $calitem['lastModif'] = time();
        if (! empty($calitem['user'])) {
            $user = $calitem['user'];
        } else {
            $calendar = TikiLib::lib('calendar')->get_calendar($calitem['calendarId']);
            $user = $calendar['user'];
        }
        $vcal = Utilities::constructCalendarData($calitem);
        $r = Sapi::createFromServerArray($_SERVER);
        $r->setMethod('PUT');
        $r->setUrl($url_path . 'tiki-caldav.php/calendars/' . $user . '/' . Utilities::getCalendarUri($calitem['calendarId']) . '/' . $uri);
        $r->setBody($vcal->serialize());
        $this->invokeBackendMethod($r);
    }

    public function saveRecurringCalendarObject($calRecurrence, $updateManuallyChangedEvents = true)
    {
        global $url_path;
        if (empty($calRecurrence->getId())) {
            $uri = uniqid();
        } else {
            $uri = Utilities::getCalendarObjectUri($calRecurrence);
        }
        $vcal = $calRecurrence->constructVCalendar();
        $vcal->VEVENT->{'X-Tiki-UpdateManuallyChangedEvents'} = intval($updateManuallyChangedEvents);
        // TODO: Sabredav validation fails when multiple events reside in the same calendar, so we can only send one event per request
        // foreach ($calRecurrence->getOverrides(1) as $calitem) {
        //     $eventcal = Utilities::constructCalendarData($calitem);
        //     $vcal->add($eventcal->VEVENT);
        // }
        $r = Sapi::createFromServerArray($_SERVER);
        $r->setMethod('PUT');
        $r->setUrl($url_path . 'tiki-caldav.php/calendars/' . $calRecurrence->getUser() . '/' . Utilities::getCalendarUri($calRecurrence->getCalendarId()) . '/' . $uri);
        $r->setBody($vcal->serialize());
        $this->invokeBackendMethod($r);
    }

    public function deleteCalendarObject($calitem_or_rec, $recurring_all = true)
    {
        global $url_path;
        if (is_array($calitem_or_rec)) {
            $user = $calitem_or_rec['user'];
            $calendarId = $calitem_or_rec['calendarId'];
        } else {
            $user = $calitem_or_rec->getUser();
            $calendarId = $calitem_or_rec->getCalendarId();
        }
        if (empty($user)) {
            $calendar = TikiLib::lib('calendar')->get_calendar($calendarId);
            $user = $calendar['user'];
        }
        $r = Sapi::createFromServerArray($_SERVER);
        $r->setMethod('DELETE');
        $r->setUrl($url_path . 'tiki-caldav.php/calendars/' . $user . '/' . Utilities::getCalendarUri($calendarId) . '/' . Utilities::getCalendarObjectUri($calitem_or_rec));
        $r->setHeader('X-Tiki-Delete-All-Recurring', $recurring_all ? 1 : 0);
        $this->invokeBackendMethod($r);
    }

    public function createSubscription($data)
    {
        global $url_path;
        $data['uri'] = 'autogen';
        $r = Sapi::createFromServerArray($_SERVER);
        $r->setMethod('MKCOL');
        $r->setUrl($url_path . 'tiki-caldav.php/calendars/' . $data['user'] . '/' . $data['uri']);
        $r->setHeader('Content-Type', 'application/xml');
        $r->setBody(<<<XML
<?xml version='1.0' encoding='UTF-8' ?>
<D:mkcol xmlns:D='DAV:' xmlns:C='http://calendarserver.org/ns/' xmlns:A='http://apple.com/ns/ical/'>
   <D:set>
      <D:prop>
         <D:displayname>{$data['name']}</D:displayname>
         <D:resourcetype>
            <D:collection/>
            <C:subscribed/>
         </D:resourcetype>
         <C:source>
            <D:href>{$data['source']}</D:href>
        </C:source>
         <A:refreshrate>{$data['refresh_rate']}</A:refreshrate>
         <A:calendar-order>{$data['order']}</A:calendar-order>
         <A:calendar-color>{$data['color']}</A:calendar-color>
         <C:subscribed-strip-todos>{$data['strip_todos']}</C:subscribed-strip-todos>
         <C:subscribed-strip-alarms>{$data['strip_alarms']}</C:subscribed-strip-alarms>
         <C:subscribed-strip-attachments>{$data['strip_attachments']}</C:subscribed-strip-attachments>
      </D:prop>
   </D:set>
</D:mkcol>
XML
        );
        $this->invokeBackendMethod($r);
    }

    public function updateSubscription($data)
    {
        $caldavBackend = new CalDAVBackend();
        $propPatch = new PropPatch([
            '{DAV:}displayname' => $data['name'],
            '{http://calendarserver.org/ns/}source' => new Xml\Property\Href($data['source']),
            '{http://apple.com/ns/ical/}refreshrate' => $data['refresh_rate'],
            '{http://apple.com/ns/ical/}calendar-order' => $data['order'],
            '{http://apple.com/ns/ical/}calendar-color' => $data['color'],
            '{http://calendarserver.org/ns/}subscribed-strip-todos' => $data['strip_todos'],
            '{http://calendarserver.org/ns/}subscribed-strip-alarms' => $data['strip_alarms'],
            '{http://calendarserver.org/ns/}subscribed-strip-attachments' => $data['strip_attachments'],
        ]);
        $caldavBackend->updateSubscription(
            $data['subscriptionId'],
            $propPatch
        );
        $propPatch->commit();
    }

    public function deleteSubscription($subscriptionId)
    {
        $caldavBackend = new CalDAVBackend();
        $caldavBackend->deleteSubscription($subscriptionId);
    }

    public function syncSubscription($subscriptionInfo)
    {
        $source = $subscriptionInfo['source'];
        if (empty($source)) {
            throw new Exception(tr('Calendar subscription source not defined.'));
        }
        $client = TikiLib::lib('tiki')->get_http_client($source);
        $response = $client->send();
        $header = $response->getHeaders()->get('Content-type');
        if ($header && $header->getMediaType() == 'text/calendar') {
            $contents = $response->getBody();
        } else {
            $result = TikiLib::lib('tiki')->matchAuthSource($source);
            if ($result['method'] != 'basic') {
                throw new Exception(tr('Source URL is not configured with Basic HTTP authentication: %0', $source));
            }
            $client = new DavClient([
                'baseUri' => $source,
                'userName' => $result['arguments']['username'],
                'password' => $result['arguments']['password'],
            ]);
            $response = $client->request('REPORT', '', '
<c:calendar-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">
    <d:prop>
        <c:calendar-data />
    </d:prop>
    <c:filter>
        <c:comp-filter name="VCALENDAR" />
    </c:filter>
</c:calendar-query>', ['Depth' => 1]);
            if ($response['statusCode'] > 399) {
                throw new Exception(tr('Invalid status code while fetching remote calendar: %0', $response['statusCode']));
            }
            $result = $client->parseMultiStatus($response['body']);
            $calendars = array_map(function ($row) {
                return isset($row[200]) ? array_shift($row[200]) : '';
            }, $result);
            $vcalendar = null;
            foreach ($calendars as $content) {
                if (is_null($vcalendar)) {
                    $vcalendar = VObject\Reader::read($content);
                } else {
                    $temp = VObject\Reader::read($content);
                    foreach ($temp->VEVENT as $vevent) {
                        $vcalendar->add($vevent);
                    }
                    unset($temp);
                }
            }
            if (! is_null($vcalendar)) {
                $contents = $vcalendar->serialize();
            } else {
                $contents = '';
            }
        }
        TikiLib::lib('calendar')->update_subscription($subscriptionInfo['subscriptionId'], [
            'last_sync' => time(),
            'vcalendar' => $contents,
        ]);
    }

    public function getAvailability($user)
    {
        $inboxProps = $this->server->getProperties(
            'calendars/' . $user . '/inbox',
            '{urn:ietf:params:xml:ns:caldav}calendar-availability'
        );

        if ($inboxProps) {
            return VObject\Reader::read(
                $inboxProps['{urn:ietf:params:xml:ns:caldav}calendar-availability']
            );
        }

        return null;
    }

    public function saveAvailability($user, $availability)
    {
        return $this->server->updateProperties('calendars/' . $user . '/inbox', [
            '{urn:ietf:params:xml:ns:caldav}calendar-availability' => $availability->serialize()
        ]);
    }

    /**
     * Perform a free-busy REPORT query against a CalDAV server (Tiki).
     * Could be extended to use other non-Tiki caldav servers as the request and response
     * should use the same format.
     *
     * @param VCalendar vcalendar object with information about event start/end times, attendees, etc.
     */
    public function getFreeBusyReport($vcalendar)
    {
        global $url_path;

        // extend start/end period with a couple of weeks, so we see other possible availability dates
        $start = $vcalendar->VEVENT->DTSTART->getDateTime();
        $start = $start->sub(new DateInterval('P1W'));
        $end = $vcalendar->VEVENT->DTEND->getDateTime();
        $end = $end->add(new DateInterval('P1W'));
        $freebusy = [
            'DTSTAMP' => (string)$vcalendar->VEVENT->DTSTAMP,
            'DTSTART' => $start,
            'DTEND' => $end,
            'ORGANIZER' => $vcalendar->VEVENT->ORGANIZER,
        ];

        $request = new VObject\Component\VCalendar();
        $request->METHOD = 'REQUEST';
        $request->add('VFREEBUSY', $freebusy);

        foreach ($vcalendar->VEVENT->ATTENDEE as $attendee) {
            $request->VFREEBUSY->add('ATTENDEE', $attendee);
        }

        $organizer = (string)$vcalendar->VEVENT->ORGANIZER;
        if (substr($organizer, 0, strlen('mailto:')) === 'mailto:') {
            $organizer = TikiLib::lib('user')->get_user_by_email(substr($organizer, strlen('mailto:')));
        }

        $r = Sapi::createFromServerArray($_SERVER);
        $r->setMethod('POST');
        $r->setHeader('Content-Type', 'text/calendar');
        $r->setUrl($url_path . 'tiki-caldav.php/calendars/' . $organizer . '/outbox');
        $r->setBody($request->serialize());
        $this->invokeBackendMethod($r);

        $slots = [];

        $body = $this->server->httpResponse->getBody();
        $xml = new Xml\Service();
        $parsed = $xml->expect('{urn:ietf:params:xml:ns:caldav}schedule-response', $body);
        if (! empty($parsed)) {
            foreach ($parsed as $response) {
                $recipient = $caldata = null;
                if (! empty($response['value'])) {
                    foreach ($response['value'] as $prop) {
                        if ($prop['name'] == '{urn:ietf:params:xml:ns:caldav}recipient') {
                            $recipient = $prop['value'];
                        }
                        if ($prop['name'] == '{urn:ietf:params:xml:ns:caldav}calendar-data') {
                            $caldata = $prop['value'];
                        }
                    }
                }
                if (! empty($recipient[0]['value'])) {
                    $recipient = $recipient[0]['value'];
                }
                if (substr($recipient, 0, strlen('mailto:')) === 'mailto:') {
                    $recipient = substr($recipient, strlen('mailto:'));
                }
                if (empty($slots[$recipient])) {
                    $slots[$recipient] = ['slots' => []];
                }
                $vobject = VObject\Reader::read($caldata);
                $slots[$recipient]['start'] = $vobject->VFREEBUSY->DTSTART->getDateTime()->getTimestamp();
                $slots[$recipient]['end'] = $vobject->VFREEBUSY->DTEND->getDateTime()->getTimestamp();
                if ($vobject->VFREEBUSY->FREEBUSY) {
                    foreach ($vobject->VFREEBUSY->FREEBUSY as $freebusy) {
                        list($start, $end) = explode('/', (string)$freebusy);
                        $slots[$recipient]['slots'][] = [
                            'start' => \DateTime::createFromFormat('Ymd\THis\Z', $start)->getTimestamp(),
                            'end' => \DateTime::createFromFormat('Ymd\THis\Z', $end)->getTimestamp(),
                            'fbtype' => (string)$freebusy['FBTYPE'] === '' ? 'BUSY' : (string)$freebusy['FBTYPE'],
                        ];
                    }
                }
            }
        }

        return $slots;
    }

    protected function invokeBackendMethod(Request $r)
    {
        global $base_url;
        $r->setBaseUrl($this->server->getBaseUri());
        $r->setAbsoluteUrl($base_url . $r->getUrl());
        $this->server->httpRequest = $r;
        $this->server->invokeMethod($this->server->httpRequest, $this->server->httpResponse, false);
    }

    protected function parseMultiStatus($body)
    {
        $xml = new Xml\Service();
        $multistatus = $xml->expect('{DAV:}multistatus', $body);

        $result = [];

        foreach ($multistatus->getResponses() as $response) {
            $result[$response->getHref()] = $response->getResponseProperties();
        }

        return $result;
    }
}
