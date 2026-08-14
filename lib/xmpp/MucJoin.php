<?php

namespace Tiki\Xmpp;

use Fabiang\Xmpp\Protocol\ProtocolImplementationInterface;
use Fabiang\Xmpp\Util\XML;

class MucJoin implements ProtocolImplementationInterface
{
    private string $roomJid;
    private string $nick;
    private int $priority;

    public function __construct(string $roomJid, string $nick = 'admin', int $priority = 1)
    {
        $this->roomJid = $roomJid;
        $this->nick    = $nick;
        $this->priority = $priority;
    }

    public function toString(): string
    {
        $to = XML::quote($this->roomJid) . '/' . XML::quote($this->nick);
        // MUC presence without password
        return '<presence to="' . $to . '">'
             . '<priority>' . $this->priority . '</priority>'
             . '<x xmlns="http://jabber.org/protocol/muc"/>'
             . '</presence>';
    }
}
