<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\MailIn\Source;

use Tiki\MailIn\Exception\MailInException;
use Tiki\MailIn\Exception\TransportException;
use Hm_IMAP;
use ZBateson\MailMimeParser\MailMimeParser;

class Imap implements SourceInterface
{
    protected $host;
    protected $port;
    protected $username;
    protected $password;

    protected $ssl;
    private $mailMimeParser;
    protected $connection = null;

    public function __construct($host, $port, $username, $password, $ssl = false)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->ssl = $ssl;
        $this->mailMimeParser = new MailMimeParser();
    }

    public function test()
    {
        try {
            $imap = $this->connect();
            $imap->disconnect();
            return true;
        } catch (TransportException $e) {
            return false;
        }
    }

    /**
     * @return \Generator
     * @throws TransportException
     */
    public function getMessages()
    {
        $imap = null;
        $toDelete = [];
        try {
            $imap = $this->connect();
            list($total, $results) = $imap->get_mailbox_page('INBOX', 'ARRIVAL', false, 'ALL');
            foreach ($results as $uid => $result) {
                $message = new Message($uid, function () use ($uid, &$toDelete) {
                    $toDelete[] = $uid;
                });
                $rawEmailContent = $imap->get_message_content($uid, 0);
                $zbMessage = $this->mailMimeParser->parse($rawEmailContent, true);
                $from = $zbMessage->getHeaderValue('From');
                if (! $from) {
                    $from = $zbMessage->getHeaderValue('Return-Path');
                }
                $message->setMessageId($zbMessage->getHeaderValue('Message-Id'));
                $message->setRawFrom($from);
                $message->setSubject($zbMessage->getHeaderValue('Subject'));
                $message->setRecipient($zbMessage->getHeaderValue('To'));
                $message->setHtmlBody($zbMessage->getHtmlContent());
                $message->setBody($zbMessage->getTextContent());
                $message->setContent($rawEmailContent);
                $this->handleAttachments($message, $zbMessage);

                yield $message;
            }
        } catch (TransportException $e) {
            $imap?->disconnect();
        }
        if ($toDelete) {
            $this->deleteMessages($imap, $toDelete);
        }
        $imap->disconnect();
    }

    public function deleteMessages($imap, $uids)
    {
        if ($imap->message_action('DELETE', $uids)['status']) {
            $imap->message_action('EXPUNGE', $uids);
            return true;
        }
    }

    /**
     * @param $message Message
     * @param $zbMessage
     */
    private function handleAttachments($message, $zbMessage)
    {

        foreach ($zbMessage->getAllAttachmentParts() as $attachmentPart) {
            $filename = $attachmentPart->getFilename();

            if (empty($filename)) {
                $filename = $attachmentPart->getHeaderValue('Content-ID');
                if (empty($contentId)) {
                    $filename = 'unnamed_attachment_' . uniqid();
                }
            }

            $contentId = $attachmentPart->getHeaderValue('Content-ID') ?: uniqid();

            $message->addAttachment(
                $contentId,
                $filename,
                $attachmentPart->getContentType(),
                $attachmentPart->getSize(),
                $attachmentPart->getContent()
            );
        }
    }
    /**
     * @throws TransportException
     */
    public function connect(): Hm_IMAP // @phpstan-ignore class.notFound (this exists, but cypht defines is conditionally for some reason, which confuses phpstan)
    {
        global $tikipath;
        try {
            $config = [
                'server'    => $this->host,
                'port'      => $this->port,
                'tls'       => $this->port === 993,
                'type'      => 'imap',
                'username'  => $this->username ,
                'password'  => $this->password,
                'use_cache' => true,
            ];
            require_once $tikipath . '/lib/cypht/integration/classes.php';
            require_once APP_PATH . 'modules/core/message_functions.php';
            require_once APP_PATH . 'modules/imap/hm-imap.php';

            $imap = new Hm_IMAP(); // @phpstan-ignore class.notFound (this exists, but cypht defines is conditionally for some reason, which confuses phpstan)
            $authenticate = $imap->connect($config);
            if (! $authenticate) {
                throw new MailInException();
            }
            return $imap;
        } catch (MailInException $e) {
            throw new TransportException(tr("Login failed for IMAP account on %0:%1 for user %2", $this->host, '******', $this->username));
        }
    }
}
