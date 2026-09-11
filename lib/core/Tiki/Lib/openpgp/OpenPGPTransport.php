<?php

namespace Tiki\Lib\openpgp;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class OpenPGPTransport extends AbstractTransport
{
    private $decoratedTransport;

    public function __construct(TransportInterface $decoratedTransport, ?EventDispatcherInterface $dispatcher = null, ?LoggerInterface $logger = null)
    {
        parent::__construct($dispatcher, $logger);
        $this->decoratedTransport = $decoratedTransport;
    }

    protected function doSend(SentMessage $message): void
    {
        // TODO: Implement doSend() method.
        global $openpgplib;
        $email = $message->getOriginalMessage();
        if (! $email instanceof Email) {
            // If it's not an Email object, we can't process it for PGP/MIME
            $this->decoratedTransport->send($email);
            return;
        }
        $originalSubject = $email->getSubject();
        $originalBody = $email->getHtmlBody() ?: $email->getTextBody();

        $recipients = [];
        foreach ($email->getTo() as $address) {
            $recipients[] = $address->getAddress();
        }
        foreach ($email->getCc() as $address) {
            $recipients[] = $address->getAddress();
        }
        foreach ($email->getBcc() as $address) {
            $recipients[] = $address->getAddress();
        }
        $pgpmime_msg = $openpgplib->prepareEncryptWithZendMail(
            $email->getHeaders()->toString(),
            (string) $originalBody,
            $recipients
        );

        $pgpmime_final_headers = $pgpmime_msg[0];
        $pgpmime_final_body = $pgpmime_msg[1];

        $pgpEncryptedRawMessage = new RawMessage($pgpmime_final_headers . "\r\n\r\n" . $pgpmime_final_body);

        $envelopeSender = $email->getFrom()[0];

        $envelopeRecipients = [];
        foreach ($email->getTo() as $address) {
            $envelopeRecipients[] = $address;
        }
        foreach ($email->getCc() as $address) {
            $envelopeRecipients[] = $address;
        }
        foreach ($email->getBcc() as $address) {
            $envelopeRecipients[] = $address;
        }
        $envelope = new Envelope($envelopeSender, $envelopeRecipients);

        $this->decoratedTransport->send($pgpEncryptedRawMessage, $envelope);
    }

    public function __toString(): string
    {
        return 'openpgp+smtp://' . $this->decoratedTransport->__toString();
    }
}
