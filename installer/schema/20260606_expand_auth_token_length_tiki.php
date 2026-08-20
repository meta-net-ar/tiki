<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Expand auth token length for HMAC-SHA256 hashes and seed a per-install signing secret.
 * Uses PHP random_bytes() so this works on MySQL 5.5 (no RANDOM_BYTES()).
 *
 * @param $installer
 */
function upgrade_20260606_expand_auth_token_length_tiki($installer)
{
    $installer->query(
        "ALTER TABLE `tiki_auth_tokens` CHANGE `token` `token` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin"
    );

    $secret = bin2hex(random_bytes(32));
    $installer->query(
        "INSERT IGNORE INTO `tiki_preferences` (`name`, `value`) VALUES (?, ?)",
        ['auth_token_secret', $secret]
    );
}
