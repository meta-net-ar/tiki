<?php

namespace Tiki\Event\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tiki\Event\Xmpp\UserRegistered;
use TikiLib;

class XmppRegistrationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'tiki.user.register.local' => 'onUserRegistered',
        ];
    }

    public function onUserRegistered($data): void
    {
        global $prefs;

        if (is_array($data)) {
            $username = $data['username'] ?? '';
            $groups   = $data['groups'] ?? [];
            $event    = new \Tiki\Event\Xmpp\UserRegistered($username, $groups);
        } elseif ($data instanceof \Tiki\Event\Xmpp\UserRegistered) {
            // If trigger() ever accepts an object
            $event = $data;
        } else {
            return;
        }

        $username = $event->getUsername();
        $groups   = $event->getGroups();
        $xmpplib  = \TikiLib::lib('xmpp');
        $joined = [];

        // Registered room
        if (! empty($prefs['xmpp_registered_room']) && in_array('Registered', $groups, true)) {
            $roomJid = $xmpplib->buildRoomJid($prefs['xmpp_registered_room']);
            $userJid = $xmpplib->getUserJidForLogin($username);
            $result = $xmpplib->ensureRoomExists($roomJid);

            if ($result && $xmpplib->setUserAffiliation($roomJid, $userJid, 'member')) {
                $joined[] = $roomJid;
            }
        }

        // Group mapping
        $mapJson  = trim($prefs['xmpp_group_room_map'] ?? '');
        $groupMap = $mapJson ? json_decode($mapJson, true) : [];
        if (! is_array($groupMap)) {
            $groupMap = [];
        }


        foreach ($groups as $g) {
            if ($g === 'Registered') {
                continue;
            }

            if (! empty($groupMap[$g])) {
                $roomJid = $xmpplib->buildRoomJid($groupMap[$g]);
                $userJid = $xmpplib->getUserJidForLogin($username);
                if ($xmpplib->ensureRoomExists($roomJid) && $xmpplib->setUserAffiliation($roomJid, $userJid, 'member')) {
                    $joined[] = $roomJid;
                }
            }
        }

        if ($joined) {
            $joined = array_values(array_unique($joined));
            $xmpplib->saveUserRooms($username, $joined);
        }
    }
}
