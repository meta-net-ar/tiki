ALTER TABLE `tiki_calendar_items` MODIFY COLUMN `status` varchar(255) NOT NULL default 'Tentative';
ALTER TABLE `tiki_calendar_recurrence` MODIFY COLUMN `status` varchar(255) NOT NULL default 'Tentative';
UPDATE `tiki_calendar_items` SET `status`='Tentative' WHERE `status`= '0';
UPDATE `tiki_calendar_items` SET `status`='Confirmed' WHERE `status`= '1';
UPDATE `tiki_calendar_items` SET `status`='Cancelled' WHERE `status`= '2';
UPDATE `tiki_calendar_recurrence` SET `status`='Tentative' WHERE `status`= '0';
UPDATE `tiki_calendar_recurrence` SET `status`='Confirmed' WHERE `status`= '1';
UPDATE `tiki_calendar_recurrence` SET `status`='Cancelled' WHERE `status`= '2';
