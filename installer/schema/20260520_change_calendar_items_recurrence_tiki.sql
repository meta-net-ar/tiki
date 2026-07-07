ALTER TABLE `tiki_calendar_items`
CHANGE `start` `start` INT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `end` `end` INT UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `tiki_calendar_recurrence`
CHANGE `startPeriod` `startPeriod` INT UNSIGNED,
CHANGE `endPeriod` `endPeriod` INT UNSIGNED;
