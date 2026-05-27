ALTER TABLE `tiki_calendar_recurrence` MODIFY COLUMN `yearlyType` enum('date','weekday','firstlastweekday'); 
ALTER TABLE `tiki_calendar_recurrence` ADD COLUMN `yearlyFirstlastWeekdayValue` INT DEFAULT NULL AFTER `yearlyWeekdayValue`;
