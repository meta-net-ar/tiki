<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Installer\Installer;

/**
 * Migration script to improve scheduler notification system
 * - Adds scheduler_notify_admins preference
 * - Merges scheduler_users_to_notify_on_healed into scheduler_users_to_notify_on_stalled
 * - Removes old scheduler_users_to_notify_on_healed preference
 * - Adds enable_send_notification_override column to tiki_scheduler table
 *
 * @param Installer $installer
 */
function upgrade_20250618_scheduler_enable_send_notification_override_tiki($installer)
{
    // Get current notification preferences
    $stalledUsers = $installer->getOne("SELECT value FROM tiki_preferences WHERE name = 'scheduler_users_to_notify_on_stalled'");
    $healedUsers = $installer->getOne("SELECT value FROM tiki_preferences WHERE name = 'scheduler_users_to_notify_on_healed'");

    // Set scheduler_notify_admins based on whether specific users are configured
    $notifyAdmins = 'y'; // Default to notify admins
    if (! empty($stalledUsers)) {
        $notifyAdmins = 'n'; // If specific users are set, don't notify all admins by default
    }

    // Insert the new notify_admins preference if it doesn't exist
    $installer->query("
        INSERT INTO tiki_preferences (name, value)
        SELECT 'scheduler_notify_admins', ?
        WHERE NOT EXISTS (SELECT 1 FROM tiki_preferences WHERE name = 'scheduler_notify_admins')
    ", [$notifyAdmins]);

    // Merge users from scheduler_users_to_notify_on_healed into scheduler_users_to_notify_on_stalled
    if (! empty($stalledUsers) && ! empty($healedUsers)) {
        $stalledUserArray = array_filter(array_map('trim', explode(',', $stalledUsers)));
        $healedUserArray = array_filter(array_map('trim', explode(',', $healedUsers)));

        $mergedUserArray = array_unique(array_merge($stalledUserArray, $healedUserArray));
        $mergedUsers = implode(',', $mergedUserArray);

        $installer->query("
            UPDATE tiki_preferences 
            SET value = ?
            WHERE name = 'scheduler_users_to_notify_on_stalled'
        ", [$mergedUsers]);
    }

    // Remove the old scheduler_users_to_notify_on_healed preference
    $installer->query("DELETE FROM tiki_preferences WHERE name = 'scheduler_users_to_notify_on_healed'");

    // Add the enable_send_notification_override column to tiki_scheduler table
    $installer->query("ALTER TABLE `tiki_scheduler` ADD `enable_send_notification_override` TINYINT DEFAULT 0");
}
