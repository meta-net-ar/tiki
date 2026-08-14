<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Installer\Installer;

/**
 * Migration script to rename bigbluebutton_server_salt preference to bigbluebutton_shared_secret
 * This aligns with BigBlueButton's official terminology which uses "shared secret" instead of "server salt"
 *
 * @param Installer $installer
 */
function upgrade_20251123_bbb_rename_server_salt_to_shared_secret_tiki($installer)
{
    // Check if the old preference exists
    $old_pref_value = $installer->getOne(
        "SELECT value FROM `tiki_preferences` WHERE `name` = ?",
        ['bigbluebutton_server_salt']
    );

    if (! empty($old_pref_value)) {
        // Check if the new preference already exists
        $new_pref_exists = $installer->getOne(
            "SELECT value FROM `tiki_preferences` WHERE `name` = ?",
            ['bigbluebutton_shared_secret']
        );

        if (empty($new_pref_exists)) {
            // Rename the preference
            $installer->queryException(
                'UPDATE `tiki_preferences` SET `name` = ? WHERE `name` = ?',
                ['bigbluebutton_shared_secret', 'bigbluebutton_server_salt']
            );
        } else {
            // If both exist, keep the new one and delete the old one
            $installer->queryException(
                'DELETE FROM `tiki_preferences` WHERE `name` = ?',
                ['bigbluebutton_server_salt']
            );
        }
    }
}
