ALTER TABLE `tiki_calendar_recurrence` ADD `daily` TINYINT(1) DEFAULT 0 AFTER `description`;
ALTER TABLE `tiki_calendar_recurrence` ADD `days` INT DEFAULT NULL AFTER `daily`;
ALTER TABLE `tiki_calendar_recurrence` ADD `weeks` INT DEFAULT NULL AFTER `weekly`;
ALTER TABLE `tiki_calendar_recurrence` ADD `months` INT DEFAULT NULL AFTER `monthly`;
ALTER TABLE `tiki_calendar_recurrence` ADD `years` INT DEFAULT NULL AFTER `yearly`;
ALTER TABLE `tiki_calendar_recurrence` ADD `yearlyType` enum('date', 'weekday') DEFAULT NULL AFTER `years`;
ALTER TABLE `tiki_calendar_recurrence` ADD `yearlyWeekdayValue` VARCHAR(4) DEFAULT NULL COMMENT 'Format => (-) + 1digit + 2 letters for weekday (1MO for every 1st Monday or -1TH for last Thursday of each month)' AFTER `dateOfYear`;
ALTER TABLE `tiki_calendar_recurrence` ADD `yearlyWeekMonth` INT DEFAULT NULL AFTER `yearlyWeekdayValue`;
