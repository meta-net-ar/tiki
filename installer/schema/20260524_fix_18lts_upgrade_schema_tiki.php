<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function upgrade_20260524_fix_18lts_upgrade_schema_tiki($installer)
{
    if ($installer->tableExists('tiki_secdb')) {
        $installer->query('ALTER TABLE `tiki_secdb` DROP PRIMARY KEY');
        $installer->query("ALTER TABLE `tiki_secdb` ADD PRIMARY KEY (`filename_hash`, `tiki_version`(20))");
    }

    if ($installer->tableExists('tiki_payment_requests')) {
        $dueDateColumn = $installer->query("SHOW COLUMNS FROM `tiki_payment_requests` LIKE 'due_date'", countQueries: false);
        if ($dueDateColumn && ! empty($dueDateColumn->result)) {
            $installer->query("ALTER TABLE `tiki_payment_requests` CHANGE `due_date` `due_date` TIMESTAMP NULL DEFAULT NULL");
        }
    }

    return true;
}
