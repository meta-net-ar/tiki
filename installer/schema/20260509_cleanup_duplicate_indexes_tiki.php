<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function upgrade_20260509_cleanup_duplicate_indexes_tiki($installer)
{
    $indexExists = $installer->fetchAll("SHOW INDEX FROM `tiki_comments` WHERE Key_name = 'idx_slvn_commentDate'");
    if (! empty($indexExists)) {
        $installer->query("ALTER TABLE `tiki_comments` DROP INDEX `idx_slvn_commentDate`", [], -1, -1, false);
    }

    $indexExists = $installer->fetchAll("SHOW INDEX FROM `tiki_link_cache` WHERE Key_name = 'urlindex'");
    if (! empty($indexExists)) {
        $installer->query("ALTER TABLE `tiki_link_cache` DROP INDEX `urlindex`", [], -1, -1, false);
    }
}
