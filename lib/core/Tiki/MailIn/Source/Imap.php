<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\MailIn\Source;

use Tiki\MailIn\Exception\TransportException;
use Laminas\Mail\Storage\Imap as ZendImap;
use Laminas\Mail\Exception\ExceptionInterface as ZendMailException;

class Imap extends Pop3
{
    protected function connect()
    {
        try {
            $imap = new ZendImap([
                'host' => $this->host,
                'port' => $this->port,
                'user' => $this->username,
                'password' => $this->password,
                'ssl' => $this->port == 993,
            ]);

            return $imap;
        } catch (ZendMailException $e) {
            throw new TransportException(tr("Login failed for IMAP account on %0:%1 for user %2", $this->host, '******', $this->username));
        }
    }
    public function getMessages()
    {
        $imap = $this->connect();
        $toDelete = [];
        foreach ($imap as $i => $source) {
            /* @var $source \Laminas\Mail\Storage\Message */
            $message = new Message($i, function () use ($i, &$toDelete) {
                $toDelete[] = $i;
            });
            $from = $source->from ?: $source->{'return-path'};
            if (! empty($source->{'message-id'})) {
                $message->setMessageId(str_replace(['<', '>'], '', $source->{'message-id'}));
            }
            $message->setRawFrom($from);
            $message->setSubject($source->subject);
            $message->setRecipient($source->to);
            $message->setHtmlBody($this->getBody($source, 'text/html'));
            $message->setBody($this->getBody($source, 'text/plain'));
            $content = '';
            foreach ($source->getHeaders() as $header) {
                $content .= $header->toString() . "\r\n";
            }
            $content .= "\r\n" . $source->getContent();
            $message->setContent($content);
            $this->handleAttachments($message, $source);
            yield $message;
        }
        if ($toDelete) {
            $toDelete = array_reverse($toDelete);

            foreach ($toDelete as $i) {
                $imap->removeMessage($i);
            }
        }
        $imap->close();
    }
}
