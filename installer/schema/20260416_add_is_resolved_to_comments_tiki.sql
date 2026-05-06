ALTER TABLE `tiki_comments` ADD COLUMN `is_resolved` char(1) NOT NULL DEFAULT 'n' AFTER `locked`;
