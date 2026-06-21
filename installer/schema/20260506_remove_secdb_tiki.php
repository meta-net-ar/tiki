<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * This removes the tiki_secdb table from the database, if it exists.
 *
 * @param $installer
 */
function upgrade_20260506_remove_secdb_tiki($installer)
{
    $query = "DROP TABLE IF EXISTS `tiki_secdb`";
    $installer->query($query);
}
