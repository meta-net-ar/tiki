<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\SabreDav;

use Sabre\DAV\Client as DavClient;
use Sabre\DAV\Xml;
use Sabre\VObject;
use TikiLib;
use Exception;

class CaldavClient
{
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
