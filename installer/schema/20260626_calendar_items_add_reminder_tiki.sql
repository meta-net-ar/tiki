ALTER TABLE `tiki_calendar_items` ADD COLUMN `sendReminder` tinyint NOT NULL default '1' AFTER `description`;
