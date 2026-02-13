<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Services_BigBlueButton_Controller
{
    public function setUp()
    {
        global $prefs;

        Services_Exception_Disabled::check('bigbluebutton_feature');
    }

    // phpcs:ignore
    public function action_create($input)
    {
        if (! $params = Tiki_Security::get()->decode($input->params->none())) {
            throw new Services_Exception_Denied(tr('Invalid meeting parameters.'));
        }
        $room = $params['name'];
        $attendee_password = $input['isPublic'] ? "attendee-" . $params['name'] : $input['attendeePW'];
        $moderator_password = $input['isPublic'] ? "moderator-" . $params['name'] : $input['adminPW'];
        $meeting_name = $params['name'];
        $isPublic = $input['isPublic'] === '1';

        $bbbParams = [
            'meetingID' => $room,
            'name' => $meeting_name,
            'attendeePW' => $attendee_password,
            'moderatorPW' => $moderator_password,
            'welcome' => $params['welcome'] ?? '',
            'logoutURL' => $params['logout'] ?? '',
            'recording' => ($params['recording'] ?? '0') === '1',
            'voiceBridge' => $params['voicebridge'] ?? '',
            'meta_presenter' => $params['configuration']['presentation']['active'] ?? false,
            'prefix' => $params['prefix'],
            'isPublic' => $isPublic,
        ];

        global $user, $prefs;
        if (! $user && $input->bbb_name->text()) {
            $_SESSION['bbb_name'] = $params['prefix'] . $input->bbb_name->text();
        }

        $bigbluebuttonlib = TikiLib::lib('bigbluebutton');

        // Attempt to create room made before joining as the BBB server has no persistency.
        // Prior check ensures that the user has appropriate rights to create the room in the
        // first place or that the room was already officially created and this is only a
        // re-create if the BBB server restarted.
        //
        // This avoids the issue occuring when tiki cache thinks the room exist and it's gone
        // on the other hand. It does not solve the issue if the room is lost on the BBB server
        // and tiki cache gets flushed. To cover that one, create can be granted to everyone for
        // the specific object.
        $joinParams = [
            'isPublic' => $isPublic,
            'autoJoin' => true,
        ];
        if (! $isPublic && (empty($input['attendeePW']) || empty($input['adminPW']))) {
            throw new Services_Exception_Denied(tr('You must provide both the attendee and moderator passwords to start a private meeting.'));
        }

        $joinUrl = null;
        if ($bigbluebuttonlib->roomExists($room)) {
            $joinUrl = $bigbluebuttonlib->joinMeeting($room, $joinParams);
        } else {
            $perms = Perms::get();
            if (! ($perms->bigbluebutton_create || $perms->bigbluebutton_join)) {
                throw new Services_Exception_Denied(tr('You do not have permission to create or join this meeting.'));
            } else {
                if ($bigbluebuttonlib->createRoom($bbbParams)) {
                    $joinUrl = $bigbluebuttonlib->joinMeeting($room, $joinParams);
                }
            }
        }

        if ($prefs['bigbluebutton_use_iframe'] !== 'y') {
            header('Location: ' . $joinUrl);
            exit;
        }

        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['joinUrl' => $joinUrl]);
        exit;
    }

    public function action_join($input)
    {
        if (! $params = Tiki_Security::get()->decode($input->params->none())) {
            throw new Services_Exception_Denied(tr('Invalid meeting parameters.'));
        }
        $room = $params['name'];
        $bigbluebuttonlib = TikiLib::lib('bigbluebutton');
        $passcode = $input['meetingPass'];
        $isPublic = $input['isPublic'] === '1';

        if (! $bigbluebuttonlib->roomExists($room)) {
            throw new Services_Exception_Denied(tr('The meeting you are trying to join does not exist.'));
        }

        $perms = Perms::get();
        if (! ($perms->bigbluebutton_create || $perms->bigbluebutton_join)) {
            throw new Services_Exception_Denied(tr('You do not have permission to join this meeting.'));
        }

        if (! $isPublic) {
            if (empty($input['meetingPass'])) {
                throw new Services_Exception_Denied(tr('You must provide the meeting passcode to join a private meeting.'));
            }

            $meetingInfo = $bigbluebuttonlib->getMeeting($room);
            if ($meetingInfo['moderatorPW'] !== $passcode && $meetingInfo['attendeePW'] !== $passcode) {
                throw new Services_Exception_Denied(tr('The meeting passcode you provided is not valid.'));
            }
        }

        $joinUrl = $bigbluebuttonlib->joinMeeting($room, [
            'autoJoin' => false,
            'isPublic' => $isPublic,
            'passCode' => $passcode,
        ]);

        global $prefs;
        if ($prefs['bigbluebutton_use_iframe'] !== 'y') {
            header('Location: ' . $joinUrl);
            exit;
        }

        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['joinUrl' => $joinUrl]);
        exit;
    }

    public function action_delete_recording($input)
    {
        if (! Perms::get()->admin) {
            throw new Services_Exception_Denied();
        }

        $bigbluebuttonlib = TikiLib::lib('bigbluebutton');
        $bigbluebuttonlib->removeRecording($input->recording_id->text());

        return [
        ];
    }
}
