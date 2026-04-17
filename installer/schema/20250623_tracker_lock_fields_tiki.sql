ALTER TABLE `tiki_tracker_items` ADD COLUMN `is_locked` tinyint(1) default 0;
ALTER TABLE `tiki_trackers` ADD COLUMN `lock_on_close` tinyint(1) default 0;
