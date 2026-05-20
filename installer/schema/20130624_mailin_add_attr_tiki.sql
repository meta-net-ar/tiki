ALTER TABLE `tiki_mailin_accounts` ADD COLUMN `routing` CHAR(1) NOT NULL DEFAULT 'y';
ALTER TABLE `tiki_mailin_accounts` ADD COLUMN `leave_email` CHAR(1) NOT NULL DEFAULT 'n';