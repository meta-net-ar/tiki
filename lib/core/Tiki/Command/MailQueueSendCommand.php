<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Laminas\Mail\Exception\ExceptionInterface as ZendMailException;
use SlmMail\Exception\ExceptionInterface as SlmMailException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TikiLib;

error_reporting(E_ALL);

#[AsCommand(
    name: 'mail-queue:send',
    description: 'Send the messages stored in the Mail Queue'
)]
class MailQueueSendCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        require_once('lib/mail/maillib.php');
        global $prefs;
        $logslib = TikiLib::lib('logs');
        tiki_mail_setup();
        $output->writeln('Mail queue processor starting...');

        $messages = \TikiDb::get()->fetchAll('SELECT messageId, message FROM tiki_mail_queue');

        foreach ($messages as $message) {
            $output->writeln('Sending message ' . $message['messageId'] . '...');
            $mail = unserialize($message['message']);
            $error = '';

            if ($mail && (get_class($mail) === 'Laminas\Mail\Message' || get_class($mail) === 'Zend\Mail\Message')) {
                $query = 'DELETE FROM tiki_mail_queue WHERE messageId = ?';
                if (empty($this->collectRecipients($mail))) {
                    $output->writeln('Deleted object id: ' . $message['messageId'] . ' (mail has no recipients)');
                } else {
                    try {
                        tiki_send_email($mail);
                        $title = 'mail';
                    } catch (ZendMailException | SlmMailException $e) {
                        $title = 'mail error';
                        $error = $e->getMessage();
                    }

                    if ($error || $prefs['log_mail'] == 'y') {
                        foreach ($mail->getTo() as $destination) {
                            $logslib->add_log($title, $error . "\n " . $destination->getEmail() . '/' . $mail->getSubject());
                        }
                        foreach ($mail->getCc() as $destination) {
                            $logslib->add_log($title, $error . "\n " . $destination->getEmail() . '/' . $mail->getSubject());
                        }
                        foreach ($mail->getBcc() as $destination) {
                            $logslib->add_log($title, $error . "\n " . $destination->getEmail() . '/' . $mail->getSubject());
                        }
                    }

                    if ($error) {
                        $query = 'UPDATE tiki_mail_queue SET attempts = attempts + 1 WHERE messageId = ?';
                        $output->writeln('Failed sending mail object id: ' . $message['messageId'] . ' (' . $error . ')');
                    } else {
                        $output->writeln('Sent.');
                    }
                }

                \TikiDb::get()->query($query, [$message['messageId']]);
            } else {
                $output->writeln('ERROR: Unable to unserialize the mail object id:' . $message['messageId']);
            }
        }
        $output->writeln('Mail queue processed...');
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
