<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @param $installer
 */
function upgrade_20241026_fgal_add_galleries_trash_tiki($installer)
{
    // Add trash file Gallery
    $installer->query(
        "INSERT INTO `tiki_file_galleries`" .
        " (`name`, `type`, `description`, `visible`, `user`, `public`, `parentId`)" .
        " VALUES ('Trash Files', 'system', '', 'y', 'admin', 'y', -1)"
    );
    // Search last insert id
    $result = $installer->getOne("SELECT `galleryId` FROM `tiki_file_galleries` WHERE `name` = 'Trash Files' and `type`='system'");

    if ($result != 0) {
        $installer->query("INSERT INTO `tiki_preferences` (`name`, `value`) VALUES ('fgal_user_trash_id', '" . $result . "');");
    }
}
