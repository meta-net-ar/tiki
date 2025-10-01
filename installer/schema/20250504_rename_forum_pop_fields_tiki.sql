ALTER TABLE `tiki_forums` CHANGE `inbound_pop_server` `inbound_imap_server` VARCHAR(250) DEFAULT NULL;
ALTER TABLE `tiki_forums` CHANGE `inbound_pop_port` `inbound_imap_port` INT(4) DEFAULT NULL;
ALTER TABLE `tiki_forums` CHANGE `inbound_pop_user` `inbound_imap_user` VARCHAR(200) DEFAULT NULL;
ALTER TABLE `tiki_forums` CHANGE `inbound_pop_password` `inbound_imap_password` VARCHAR(80) DEFAULT NULL;
ALTER TABLE `tiki_forums` ADD COLUMN `inbound_imap_ssl` VARCHAR(5) DEFAULT NULL AFTER `inbound_imap_password`;
UPDATE `tiki_forums` SET `inbound_imap_port` = '993', `inbound_imap_ssl` = 'TLS' WHERE `inbound_imap_port` = '995';
UPDATE `tiki_forums` SET `inbound_imap_port` = '143' WHERE `inbound_imap_port` = '110';
