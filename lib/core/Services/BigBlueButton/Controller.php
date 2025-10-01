<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

class Services_BigBlueButton_Controller
{
    public function setUp()
    {
        global $prefs;

        Services_Exception_Disabled::check('bigbluebutton_feature');
    }

    public function action_join($input)
    {
        if (! $params = Tiki_Security::get()->decode($input->params->none())) {
            throw new Services_Exception_Denied(tr('Invalid meeting parameters.'));
        }

        $room = $params['name'];
        $attendee_password = "attendee-" . $params['name'];
        $moderator_password = "moderator-" . $params['name'];
        $meeting_name = $params['name'];

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
        ];

        global $user;
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
        if ($bigbluebuttonlib->createRoom($bbbParams)) {
            $bigbluebuttonlib->joinMeeting($room);
        }
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
