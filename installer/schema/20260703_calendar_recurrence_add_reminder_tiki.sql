ALTER TABLE `tiki_calendar_recurrence` ADD COLUMN `sendReminder` tinyint NOT NULL default '1' AFTER `description`;
