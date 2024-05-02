ALTER TABLE `tiki_calendar_recurrence` MODIFY COLUMN `monthlyType` enum('date','weekday','firstlastweekday'); 
ALTER TABLE `tiki_calendar_recurrence` ADD COLUMN `monthlyFirstlastWeekdayValue` int default NULL AFTER `monthlyWeekdayValue`;
