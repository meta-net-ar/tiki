<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mime\Email;

error_reporting(E_ALL);

#[AsCommand(
    name: 'mail-queue:send',
    description: 'Send the messages stored in the Mail Queue'
)]
class MailQueueSendCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs;

        // make sure we do not get into a loop, so disabling (additional) queueing of mails during queue processing.
        $originalZendMailQueuePreference = $prefs['mailer_queue'] ?? null;
        $prefs['mailer_queue'] = 'n';

        $output->writeln('Mail queue processor starting...');

        $where = ! empty($prefs['mailer_queue_max_retries']) ? 'where `attempts` <= ' . $prefs['mailer_queue_max_retries'] : '';
        $messages = \TikiDb::get()->fetchAll('SELECT messageId, message FROM tiki_mail_queue ' . $where);

        foreach ($messages as $message) {
            $messageId = $message['messageId'];
            $serializedEmail = $message['message'];
            $output->writeln('Sending message ID: ' . $messageId . '...');
            $mail = unserialize($serializedEmail);
            $error = '';
            if ($mail instanceof Email) {
                $tikiMail = new \TikiMail();
                $tikiMail->setFrom($mail->getFrom()[0]->getAddress(), $mail->getFrom()[0]->getName());
                $tikiMail->setSubject($mail->getSubject());
                $recipients = $this->collectRecipients($mail);
                $query = 'DELETE FROM tiki_mail_queue WHERE messageId = ?';
                if (empty($recipients)) {
                    $output->writeln('Deleted object id: ' . $messageId . ' (mail has no recipients)');
                } elseif ($tikiMail->send($recipients)) {
                    $output->writeln('Sent.');
                } else {
                    $query = 'UPDATE tiki_mail_queue SET attempts = attempts + 1 WHERE messageId = ?';
                    $output->writeln('Failed sending mail object id: ' . $messageId . ' (' . $error . ')');
                }

                \TikiDb::get()->query($query, [$messageId]);
            } else {
                $output->writeln('ERROR: Unable to unserialize the mail object id:' . $messageId);
            }
        }
        $output->writeln('Mail queue processed...');

        // restore zend mail queue setting - should not be necessary, but in case $prefs is cached after.
        if (is_null($originalZendMailQueuePreference)) {
            unset($prefs['mailer_queue']);
        } else {
            $prefs['mailer_queue'] = $originalZendMailQueuePreference;
        }

        return Command::SUCCESS;
    }

    private function collectRecipients($mail)
    {
        $recipients = [];
        foreach ($mail->getTo() as $destination) {
            $recipients[] = $destination->getAddress();
        }
        foreach ($mail->getCc() as $destination) {
            $recipients[] = $destination->getAddress();
        }
        foreach ($mail->getBcc() as $destination) {
            $recipients[] = $destination->getAddress();
        }
        return $recipients;
    }
}
