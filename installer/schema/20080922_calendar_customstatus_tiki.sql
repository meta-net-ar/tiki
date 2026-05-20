
#2008-09-22 sylvieg
UPDATE tiki_calendar_options SET `optionName`='defaulteventstatus' WHERE `optionName`='customeventstatus';
ALTER TABLE tiki_calendars ADD COLUMN customstatus ENUM('n','y') NOT NULL DEFAULT 'y';

