ALTER TABLE `tiki_calendar_recurrence` ADD `daily` tinyint(1) default 0 after `description`;
ALTER TABLE `tiki_calendar_recurrence` ADD `days` int default NULL after `daily`;
ALTER TABLE `tiki_calendar_recurrence` ADD `weeks` int default NULL after `weekly`;
ALTER TABLE `tiki_calendar_recurrence` ADD `months` int default NULL after `monthly`;
ALTER TABLE `tiki_calendar_recurrence` ADD `years` int default NULL after `yearly`;
ALTER TABLE `tiki_calendar_recurrence` ADD `yearlyType` enum('date', 'weekday') default NULL after `years`;
ALTER TABLE `tiki_calendar_recurrence` ADD `yearlyWeekdayValue` varchar(4) default NULL COMMENT 'Format => (-) + 1digit + 2 letters for weekday (1MO for every 1st Monday or -1TH for last Thursday of each month)' after `dateOfYear`;
ALTER TABLE `tiki_calendar_recurrence` ADD `yearlyWeekMonth` int default NULL after `yearlyWeekdayValue`;
