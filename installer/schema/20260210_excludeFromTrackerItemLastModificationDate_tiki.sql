ALTER TABLE `tiki_tracker_fields` 
ADD COLUMN `excludeFromTrackerItemLastModificationDate` CHAR(1) NOT NULL DEFAULT 'n' AFTER `excludeFromNotification`;