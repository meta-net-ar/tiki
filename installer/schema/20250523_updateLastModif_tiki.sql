ALTER TABLE `tiki_tracker_fields` 
ADD COLUMN `updateLastModif` CHAR(1) DEFAULT 'y' AFTER `excludeFromNotification`;