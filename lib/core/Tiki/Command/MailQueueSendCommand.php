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
        $originalZendMailQueuePreference = $prefs['zend_mail_queue'] ?? null;
        $prefs['zend_mail_queue'] = 'n';

        $output->writeln('Mail queue processor starting...');

        $messages = \TikiDb::get()->fetchAll('SELECT messageId, message FROM tiki_mail_queue');

        foreach ($messages as $message) {
            $output->writeln('Sending message ' . $message['messageId'] . '...');
            $mail = unserialize($message['message']);
            $error = '';

            if ($mail && (get_class($mail) === 'Laminas\Mail\Message' || get_class($mail) === 'Zend\Mail\Message')) {
                $tikiMail = new \TikiMail();
                $tikiMail->setFrom($mail->getFrom()->current()->getEmail(), $mail->getFrom()->current()->getName());
                $tikiMail->setSubject($mail->getSubject());
                $tikiMail->setLaminasMessageBody($mail);
                $recipients = $this->collectRecipients($mail);

                if ($tikiMail->send($recipients)) {
                    $query = 'DELETE FROM tiki_mail_queue WHERE messageId = ?';
                    $output->writeln('Sent.');
                } else {
                    $query = 'UPDATE tiki_mail_queue SET attempts = attempts + 1 WHERE messageId = ?';
                    $output->writeln('Failed sending mail object id: ' . $message['messageId'] . ' (' . $error . ')');
                }

                \TikiDb::get()->query($query, [$message['messageId']]);
            } else {
                $output->writeln('ERROR: Unable to unserialize the mail object id:' . $message['messageId']);
            }
        }
        $output->writeln('Mail queue processed...');

        // restore zend mail queue setting - should not be necessary, but in case $prefs is cached after.
        if (is_null($originalZendMailQueuePreference)) {
            unset($prefs['zend_mail_queue']);
        } else {
            $prefs['zend_mail_queue'] = $originalZendMailQueuePreference;
        }

        return Command::SUCCESS;
    }

    private function collectRecipients($mail)
    {
        $recipients = [];
        foreach ($mail->getTo() as $destination) {
            $recipients[] = $destination->getEmail();
        }
        foreach ($mail->getCc() as $destination) {
            $recipients[] = $destination->getEmail();
        }
        foreach ($mail->getBcc() as $destination) {
            $recipients[] = $destination->getEmail();
        }
        return $recipients;
    }
}
