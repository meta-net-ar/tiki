<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Installer\Installer;

function upgrade_20260516_add_missing_forum_mail_columns_tiki(Installer $installer): bool
{
    if (! $installer->tableExists('tiki_forums')) {
        return true;
    }

    $columns = [
        'outbound_address' => "`outbound_address` varchar(250) default NULL AFTER `approval_type`",
        'outbound_mails_for_inbound_mails' => "`outbound_mails_for_inbound_mails` char(1) default NULL AFTER `outbound_address`",
        'outbound_mails_reply_link' => "`outbound_mails_reply_link` char(1) default NULL AFTER `outbound_mails_for_inbound_mails`",
        'outbound_from' => "`outbound_from` varchar(250) default NULL AFTER `outbound_mails_reply_link`",
        'inbound_pop_server' => "`inbound_pop_server` varchar(250) default NULL AFTER `outbound_from`",
        'inbound_pop_port' => "`inbound_pop_port` int(4) default NULL AFTER `inbound_pop_server`",
        'inbound_pop_user' => "`inbound_pop_user` varchar(200) default NULL AFTER `inbound_pop_port`",
        'inbound_pop_password' => "`inbound_pop_password` varchar(80) default NULL AFTER `inbound_pop_user`",
    ];

    foreach ($columns as $column => $definition) {
        if (! forumMailColumnExists20260516($installer, $column)) {
            $installer->query("ALTER TABLE `tiki_forums` ADD COLUMN $definition");
        }
    }

    return true;
}

function forumMailColumnExists20260516(Installer $installer, string $column): bool
{
    global $dbs_tiki;

    return (int) $installer->getOne(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = 'tiki_forums'
                AND COLUMN_NAME = ?",
        [$dbs_tiki, $column]
    ) > 0;
}
