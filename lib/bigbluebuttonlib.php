<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use BigBlueButton\BigBlueButton;
use BigBlueButton\Parameters\CreateMeetingParameters;
use BigBlueButton\Parameters\JoinMeetingParameters;
use BigBlueButton\Parameters\GetRecordingsParameters;
use BigBlueButton\Parameters\DeleteRecordingsParameters;
use BigBlueButton\Parameters\GetMeetingInfoParameters;
use BigBlueButton\Parameters\EndMeetingParameters;
use BigBlueButton\Responses\GetMeetingInfoResponse;
use BigBlueButton\Parameters\MetaParameters;
use BigBlueButton\Exception\BigBlueButtonException;
use Tiki\Exceptions\BigBlueButton\ServerSaltKeyException;
use BigBlueButton\Responses\ApiVersionResponse;

/**
 *
 */
class BigBlueButtonLib
{
    private $version = false;
    private $bbb;

    public function __construct()
    {
        global $prefs;
        $this->bbb = new BigBlueButton($prefs['bigbluebutton_server_location'], $prefs['bigbluebutton_shared_secret']);
    }
    /**
     * @return bool|string
     */
    private function getVersion()
    {
        if ($this->version !== false) {
            return $this->version;
        }

        $response = $this->bbb->getApiVersion();
        if ($response instanceof ApiVersionResponse && $response->success()) {
            $version = $response->getVersion();
            if (false !== $pos = strpos($version, '-')) {
                $version = substr($version, 0, $pos);
            }
            $this->version = $version;
            return $version;
        }

        $this->version = '0.6';
        return $this->version;
    }

    /**
     * @return array|mixed
     */
    public function getMeetings()
    {
        $cachelib = TikiLib::lib('cache');

        if (! $meetings = $cachelib->getSerialized('bbb_meetinglist')) {
            $meetings = [];

            $response = $this->bbb->getMeetings();
            if ($response->success()) {
                $xml = $response->getRawXml();
                foreach ($xml->meetings->meeting as $meeting) {
                    $meetings[] = $this->grabValues($meeting);
                }
            }

            $cachelib->cacheItem('bbb_meetinglist', serialize($meetings));
        }

        return $meetings;
    }

    /**
     * @param $room
     * @return array
     */
    public function getAttendees($room, $username = false)
    {
        $getMeetingInfoParams = new GetMeetingInfoParameters($room);
        $response = $this->bbb->getMeetingInfo($getMeetingInfoParams);

        if ($response->getReturnCode() !== 'FAILED') {
            $reflection = new \ReflectionClass($response);
            $property = $reflection->getProperty('rawXml');
            $rawXml = $property->getValue($response);

            $attendees = [];
            if (isset($rawXml->attendees)) {
                foreach ($rawXml->attendees->attendee as $attendee) {
                    $attendeeData = [
                        'userID' => (string) $attendee->userID,
                        'fullName' => (string) $attendee->fullName,
                        'role' => (string) $attendee->role,
                        'isPresenter' => (string) $attendee->isPresenter,
                        'isListeningOnly' => (string) $attendee->isListeningOnly,
                        'hasJoinedVoice' => (string) $attendee->hasJoinedVoice,
                        'hasVideo' => (string) $attendee->hasVideo,
                        'clientType' => (string) $attendee->clientType
                    ];

                    if ($username && ! empty($attendeeData['fullName'])) {
                        preg_match('!\(([^\)]+)\)!', $attendeeData['fullName'], $match);
                        $attendeeData['fullName'] = $match[1] ?? $attendeeData['fullName'];
                    }

                    $attendees[] = $attendeeData;
                }
            }
            return $attendees;
        }
        return [];
    }

    /**
     * @param $node
     * @return array
     */
    private function grabValues($node, $username = false)
    {
        $values = [];

        foreach ($node->childNodes as $n) {
            if ($n instanceof DOMElement) {
                $values[$n->tagName] = $n->textContent;
            }
        }
        if ($username && ! empty($values['fullName'])) {
            preg_match('!\(([^\)]+)\)!', $values['fullName'], $match);
            $values['fullName'] = $match[1];
        } else {
            $values['fullName'] = trim(preg_replace('!\(([^\)]+)\)!', '', $values['fullName'] ?? ''));
        }
        return $values;
    }

    /**
     * @param $room
     * @return bool
     */
    public function roomExists($room)
    {
        $getMeetingInfoParams = new GetMeetingInfoParameters($room);
        $response = $this->bbb->getMeetingInfo($getMeetingInfoParams);
        if ($response->getReturnCode() == 'FAILED') {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $room
     * @param array $params
     */
    public function createRoom($params): bool
    {
        global $prefs;
        $tikilib = TikiLib::lib('tiki');
        $cachelib = TikiLib::lib('cache');
        $meetingID = $params['meetingID'];
        $meetingName = $params['name'] ?? "meet-" . $params['meetingID'];
        $duration = $params['duration'] ?? $prefs['bigbluebutton_recording_max_duration'];
        $urlLogout = $tikilib->tikiUrl($params['logoutURL']);

        $createMeetingParams = new CreateMeetingParameters($meetingID, $meetingName);
        $createMeetingParams->setAttendeePassword($params['attendeePW']);
        $createMeetingParams->setModeratorPassword($params['moderatorPW']);
        $createMeetingParams->setLogoutUrl($urlLogout);

        if (! empty($params['recording']) && $params['recording'] == 'true') {
            $createMeetingParams->setRecord(true);
            $createMeetingParams->setAllowStartStopRecording(true);
            $createMeetingParams->setAutoStartRecording(true);
            $createMeetingParams->setDuration($duration);
        } else {
            $createMeetingParams->setRecord(false);
        }

        $response = $this->bbb->createMeeting($createMeetingParams);
        $cachelib->invalidate('bbb_meetinglist');

        return $response->getReturnCode() !== 'FAILED';
    }

    /**
     * @param $room
     */
    public function joinMeeting($room)
    {
        $name = $this->getAttendeeName();
        $password = $this->getAttendeePassword($room);

        $joinParams = new JoinMeetingParameters(
            $room,
            $name,
            $password
        );

        $joinParams->setRedirect(true);
        $joinParams->setUserID('user-' . uniqid());

        $joinUrl = $this->bbb->getJoinMeetingURL($joinParams);
        header("Location: " . $joinUrl);
        exit;
    }

    /**
     * @param $recordingID
     */
    public function removeRecording($recordingID)
    {
        $deleteRecordingsParams = new DeleteRecordingsParameters($recordingID);
        $response = $this->bbb->deleteRecordings($deleteRecordingsParams);

        if ($response->getReturnCode() == 'SUCCESS') {
            Feedback::success("Recording with ID " . $recordingID . " was deleted successfully.");
        } else {
            Feedback::error("Could not delete recording. Please try again later.");
        }
    }

    /**
     * @return bool|mixed|null|string
     */
    private function getAttendeeName()
    {
        global $user, $tikilib;

        if ($realName = $tikilib->get_user_preference($user, 'realName')) {
            $realName .= " (" . $user . ")";
            return $realName;
        } elseif ($user) {
            return $user;
        } elseif (! empty($_SESSION['bbb_name'])) {
            return $_SESSION['bbb_name'];
        } else {
            return tra('anonymous');
        }
    }

    /**
     * @param $room
     * @return mixed
     */
    private function getAttendeePassword($room)
    {
        if ($meeting = $this->getMeeting($room)) {
            $perms = Perms::get('bigbluebutton', $room);

            if ($perms->bigbluebutton_moderate) {
                return $meeting['moderatorPW'];
            } else {
                return $meeting['attendeePW'];
            }
        }
    }

    /**
     * @param $room
     * @return mixed
     */
    private function getMeeting($room)
    {
        $getMeetingInfoParams = new GetMeetingInfoParameters($room);
        $response = $this->bbb->getMeetingInfo($getMeetingInfoParams);
        if ($response->getReturnCode() == 'FAILED') {
            return false;
        } else {
            $reflection = new \ReflectionClass($response);
            $property = $reflection->getProperty('rawXml');
            $rawXml = $property->getValue($response);

            return [
                'meetingID' => (string) $rawXml->meetingID,
                'meetingName' => (string) $rawXml->meetingName,
                'internalMeetingID' => (string) $rawXml->internalMeetingID,
                'createTime' => (string) $rawXml->createTime,
                'createDate' => (string) $rawXml->createDate,
                'attendeePW' => (string) $rawXml->attendeePW,
                'moderatorPW' => (string) $rawXml->moderatorPW,
                'running' => (string) $rawXml->running,
                'duration' => (string) $rawXml->duration,
                'recording' => (string) $rawXml->recording,
                'hasBeenForciblyEnded' => (string) $rawXml->hasBeenForciblyEnded,
            ];
        }
    }

    /**
     * @return bool
     */
    private function isRecordingSupported()
    {
        $version = $this->getVersion();
        return version_compare($version, '0.8') >= 0;
    }


    /**
     * @param $room
     *
     * @return array
     * @throws Exception
     */
    public function getRecordings($room)
    {
        if (! $this->isRecordingSupported()) {
            return [];
        }

        $recordingParams = new GetRecordingsParameters();
        $recordingParams->setMeetingID($room);

        $response = $this->bbb->getRecordings($recordingParams);

        if (! $response || ! $response->getRawXml()) {
            throw new ServerSaltKeyException(tr('Invalid shared secret key entered. Please contact the site administrator to insert a valid shared secret key in the RTC > BigBlueButton control panel.'));
        }

        $data = [];
        if (isset($response->getRawXml()->recordings->recording)) {
            foreach ($response->getRawXml()->recordings->recording as $recording) {
                $published = ((string) $recording->published === 'true');

                $info = [
                    'recordID'  => (string) $recording->recordID,
                    'startTime' => floor(((string) $recording->startTime) / 1000),
                    'endTime'   => ceil(((string) $recording->endTime) / 1000),
                    'playback'  => [],
                    'published' => $published,
                ];

                foreach ($recording->playback as $playback) {
                    $formatType = (string) $playback->format->type;
                    $url = (string) $playback->format->url;
                    $info['playback'][$formatType] = $url;
                }

                $data[] = $info;
            }

            usort($data, ['BigBlueButtonLib', 'cmpStartTime']);
        }

        return $data;
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    private static function cmpStartTime($a, $b)
    {
        if ($a['startTime'] == $b['startTime']) {
            return 0;
        }
        return ($a['startTime'] > $b['startTime']) ? -1 : 1;
    }
}
