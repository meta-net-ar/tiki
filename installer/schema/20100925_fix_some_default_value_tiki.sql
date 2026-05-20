ALTER TABLE `tiki_language` MODIFY `source` text NOT NULL;
ALTER TABLE `tiki_language` MODIFY `changed` TINYINT(1) DEFAULT NULL;
ALTER TABLE `tiki_user_votings` MODIFY `user` VARCHAR(200) NOT NULL DEFAULT '';
ALTER TABLE `tiki_pages_translation_bits` MODIFY `flags` SET('critical') DEFAULT '';
ALTER TABLE `tiki_user_mail_accounts` MODIFY `imap` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `tiki_user_mail_accounts` MODIFY `mbox` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `tiki_user_mail_accounts` MODIFY `maildir` VARCHAR(255) DEFAULT NULL;
