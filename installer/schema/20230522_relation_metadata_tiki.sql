ALTER TABLE `tiki_object_relations` ADD COLUMN `metadata_itemId` INT(12) DEFAULT NULL;
ALTER TABLE `tiki_object_relations` ADD KEY `metadata_itemId` (`metadata_itemId`);
ALTER TABLE `tiki_object_relations` ADD COLUMN `source_fieldId` INT(12) DEFAULT NULL AFTER `source_itemId`;
ALTER TABLE `tiki_object_relations` DROP KEY `relation_source_ix`;
ALTER TABLE `tiki_object_relations` ADD KEY `relation_source_ix` (`source_type`, `source_itemId`, `source_fieldId`);
UPDATE `tiki_object_relations` tor, `tiki_tracker_item_fields` ttif, `tiki_tracker_fields` ttf
SET tor.source_fieldId = ttif.fieldId
WHERE ttif.fieldId = ttf.fieldId
    AND tor.source_type = 'trackeritem' AND ttif.itemId = tor.source_itemId
    AND ttf.type = 'REL' AND ttf.options LIKE CONCAT('%', tor.relation, '%');
