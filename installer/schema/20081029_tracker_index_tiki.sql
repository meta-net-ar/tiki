#sylvieg 2008-10-29
ALTER TABLE `tiki_tracker_items` ADD INDEX `trackerId` (`trackerId`);
ALTER TABLE `tiki_tracker_fields` ADD INDEX `trackerId` (`trackerId`);
ALTER TABLE `tiki_tracker_item_attachments` ADD INDEX `itemId` (`itemId`);