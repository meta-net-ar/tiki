ALTER TABLE `tiki_comments` ADD INDEX `idx_slvn_commentDate` (`commentDate`);
ALTER TABLE `tiki_articles` ADD INDEX `idx_slvn_created` (`created`);
ALTER TABLE `tiki_calendars` ADD INDEX `idx_slvn_created` (`created`);
ALTER TABLE `tiki_tracker_items` ADD INDEX `idx_slvn_created` (`created`);
