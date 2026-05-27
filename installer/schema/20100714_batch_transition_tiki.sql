ALTER TABLE `tiki_transitions` ADD COLUMN `batch` CHAR(1) DEFAULT NULL;
ALTER TABLE `tiki_transitions`ADD COLUMN `objectId` INT(12) DEFAULT NULL AFTER `type`;
ALTER TABLE `tiki_transitions` ADD KEY `batch` (`batch`);
ALTER TABLE `tiki_transitions` ADD KEY `objectId` (`objectId`);