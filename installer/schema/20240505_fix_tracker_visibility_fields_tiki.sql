UPDATE `tiki_tracker_fields` SET `visibleInViewMode` = 'y' WHERE `visibleInViewMode` IS NULL;
UPDATE `tiki_tracker_fields` SET `visibleInEditMode` = 'y' WHERE `visibleInEditMode` IS NULL;
UPDATE `tiki_tracker_fields` SET `visibleInHistoryMode` = 'y' WHERE `visibleInHistoryMode` IS NULL;
ALTER TABLE `tiki_tracker_fields` MODIFY COLUMN `visibleInViewMode` CHAR(1) NOT NULL DEFAULT 'y', MODIFY COLUMN `visibleInEditMode` CHAR(1) NOT NULL DEFAULT 'y', MODIFY COLUMN `visibleInHistoryMode` CHAR(1) NOT NULL DEFAULT 'y';
