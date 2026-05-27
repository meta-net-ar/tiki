#2008-09-16  MatWho
ALTER TABLE tiki_user_mail_accounts ADD COLUMN `flagsPublic` CHAR(1) DEFAULT 'n' AFTER `smtpPort`;
ALTER TABLE tiki_user_mail_accounts ADD COLUMN `autoRefresh` INT(4) NOT NULL DEFAULT 0 AFTER `flagsPublic`;
ALTER TABLE tiki_webmail_messages ADD COLUMN `flaggedMsg` VARCHAR(50) DEFAULT '' AFTER `isFlagged`;

#2008-09-27  MatWho
INSERT INTO users_permissions (`permName`, `permDesc`, level, type) VALUES ('tiki_p_use_group_webmail', 'Can use group webmail', 'registered', 'webmail');
INSERT INTO users_permissions (`permName`, `permDesc`, level, type) VALUES ('tiki_p_admin_group_webmail', 'Can administrate group webmail accounts', 'registered', 'webmail');
INSERT INTO users_permissions (`permName`, `permDesc`, level, type) VALUES ('tiki_p_use_personal_webmail', 'Can use personal webmail accounts', 'registered', 'webmail');
INSERT INTO users_permissions (`permName`, `permDesc`, level, type) VALUES ('tiki_p_admin_personal_webmail', 'Can administrate personal webmail accounts', 'registered', 'webmail');

