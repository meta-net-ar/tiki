ALTER TABLE `tiki_tracker_fields` 
ADD COLUMN `excludeFromTrackerItemLastModificationDate` CHAR(1) DEFAULT 'n' AFTER `excludeFromNotification`;