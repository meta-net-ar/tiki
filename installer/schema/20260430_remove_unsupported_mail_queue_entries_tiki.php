<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Installer\Installer;

/**
 * Remove unsupported mail queue entries that are serialized in old Laminas/Zend format.
 * These entries cannot be processed by the current Symfony Email-based system and should be cleaned up.
 *
 * The mail queue system migrated from Laminas\Mail\Message to Symfony\Component\Mime\Email
 * as of Tiki 29. Users upgrading from Tiki < 29 may have old serialized messages left in the queue
 * that cannot be deserialized and processed.
 *
 * @param Installer $installer
 */
function upgrade_20260430_remove_unsupported_mail_queue_entries_tiki($installer)
{
    // Get all mail queue entries
    $allQueueEntries = $installer->fetchAll('SELECT messageId, message FROM tiki_mail_queue');

    $incompatibleIds = [];

    foreach ($allQueueEntries as $entry) {
        $messageId = $entry['messageId'];
        $serialized = $entry['message'];

        $previousErrorReporting = error_reporting(0);
        $mail = unserialize($serialized);
        error_reporting($previousErrorReporting);

        if ($mail instanceof \Symfony\Component\Mime\Email) {
        } else {
            $incompatibleIds[] = $messageId;
        }
    }

    // This will bubble up to the console and stop the migration
    if (! empty($incompatibleIds) && ! $installer->autoRegister) {
        throw new Exception(
            sprintf(
                'Found %d unsupported mail queue entries (serialized in old Laminas/Zend format). These entries cannot be processed. '
                    . 'WARNING: These entries will be DELETED if you proceed with --auto-register, and queued email data will be lost. '
                    . 'To delete them and continue, rerun:<info> php console.php database:update --auto-register</info>',
                count($incompatibleIds)
            )
        );
    }

    // Delete all incompatible entries if auto-register is on or if there are any to delete
    if (! empty($incompatibleIds)) {
        foreach ($incompatibleIds as $messageId) {
            $installer->query('DELETE FROM tiki_mail_queue WHERE messageId = ?', [$messageId]);
        }

        error_log(sprintf(
            'Tiki upgrade: Removed %d unsupported mail queue entries. These were serialized in old Laminas/Zend format.',
            count($incompatibleIds)
        ));
    }

    return true;
}
