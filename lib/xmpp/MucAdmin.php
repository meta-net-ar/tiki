<?php

namespace Tiki\Xmpp;

use Fabiang\Xmpp\Protocol\ProtocolImplementationInterface;
use Fabiang\Xmpp\Util\XML;

class MucAdmin implements ProtocolImplementationInterface
{
    private $roomJid;
    private $userJid;
    private $affiliation;
    private $id;

    public function __construct(string $roomJid, string $userJid, string $affiliation = 'member')
    {
        $this->roomJid = $roomJid;
        $this->userJid = $userJid;
        $this->affiliation = $affiliation;
        $this->id = 'affil_' . uniqid();
    }

    public function toString(): string
    {
        return sprintf(
            '<iq type="set" to="%s" id="%s">' .
                '<query xmlns="http://jabber.org/protocol/muc#admin">' .
                    '<item affiliation="%s" jid="%s"/>' .
                '</query>' .
            '</iq>',
            XML::quote($this->roomJid),
            XML::quote($this->id),
            XML::quote($this->affiliation),
            XML::quote($this->userJid)
        );
    }
}
