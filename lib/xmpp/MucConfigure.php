<?php

namespace Tiki\Xmpp;

use Fabiang\Xmpp\Protocol\ProtocolImplementationInterface;
use Fabiang\Xmpp\Util\XML;

class MucConfigure implements ProtocolImplementationInterface
{
    private string $roomJid;
    private array $config;
    private string $id;

    public function __construct(string $roomJid, array $config = [])
    {
        $this->roomJid = $roomJid;
        $this->config = array_merge([
            'muc#roomconfig_persistentroom' => '1',
            'muc#roomconfig_publicroom' => '1',
            'muc#roomconfig_membersonly' => '0',
            'muc#roomconfig_enablelogging' => '1',
            'muc#roomconfig_allowinvites' => '1',
            'muc#roomconfig_changesubject' => '0',
            'muc#roomconfig_moderatedroom' => '0',
        ], $config);
        $this->id = 'config_' . uniqid();
    }

    public function toString(): string
    {
        $fields = '';
        foreach ($this->config as $var => $value) {
            $fields .= "<field var=\"{$var}\"><value>{$value}</value></field>";
        }
        return "<iq type='set' to='{$this->roomJid}' id='{$this->id}'>
                    <query xmlns='http://jabber.org/protocol/muc#owner'>
                        <x xmlns='jabber:x:data' type='submit'>{$fields}</x>
                    </query>
                </iq>";
    }
}
