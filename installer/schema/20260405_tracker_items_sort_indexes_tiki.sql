-- Composite indexes on tiki_tracker_items to speed up tracker list queries.
-- Eliminates full-table filesort when sorting a filtered trackerId at high offsets.

ALTER TABLE `tiki_tracker_items` ADD INDEX `idx_tracker_lastModif` (`trackerId`, `lastModif`);
ALTER TABLE `tiki_tracker_items` ADD INDEX `idx_tracker_created`   (`trackerId`, `created`);
