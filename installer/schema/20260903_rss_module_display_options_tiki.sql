ALTER TABLE `tiki_rss_modules` ADD COLUMN `showDesc` char(1) default 'n' AFTER `showPubDate`;
ALTER TABLE `tiki_rss_modules` ADD COLUMN `showImage` char(1) default 'n' AFTER `showDesc`;
ALTER TABLE `tiki_rss_modules` ADD COLUMN `displayMode` varchar(10) default 'list' AFTER `showImage`;
