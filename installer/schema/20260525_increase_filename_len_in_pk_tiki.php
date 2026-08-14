<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Installer\Installer;

/**
 * Adds the filename_hash column to tiki_secdb and updates the primary key.
 *
 * Some instances may already contain this column if the migration was
 * previously applied, so the schema change is performed only when needed.
 *
 * @param Installer $installer
 *
 * @return true
 */
function upgrade_20260525_increase_filename_len_in_pk_tiki(Installer $installer)
{
    global $dbs_tiki;

    $exists = $installer->getOne(
        "SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE COLUMN_NAME = 'filename_hash'
            AND TABLE_SCHEMA = '" . $dbs_tiki . "'
            AND TABLE_NAME = 'tiki_secdb'"
    );

    if (! boolval($exists)) {
        $installer->query("
            ALTER TABLE `tiki_secdb`
                ADD COLUMN `filename_hash` CHAR(32) NOT NULL DEFAULT '' AFTER `filename`
        ");

        $installer->query("
            UPDATE `tiki_secdb`
            SET `filename_hash` = MD5(`filename`)
            WHERE `filename_hash` = ''
        ");

        $installer->query("
            ALTER TABLE `tiki_secdb`
                DROP PRIMARY KEY,
                ADD PRIMARY KEY (`filename_hash`, `tiki_version`(20))
        ");
    }

    return true;
}
