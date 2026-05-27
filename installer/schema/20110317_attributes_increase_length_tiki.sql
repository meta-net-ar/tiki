ALTER TABLE `tiki_object_attributes` modify `itemId` VARCHAR(160) NOT NULL;
ALTER TABLE `tiki_object_attributes` modify `attribute` VARCHAR(70) NOT NULL;
ALTER TABLE `tiki_object_attributes` modify `value` VARCHAR(255);
ALTER TABLE `tiki_object_relations` modify `relation` VARCHAR(70) NOT NULL;
ALTER TABLE `tiki_object_relations` modify `target_itemId` VARCHAR(160) NOT NULL;
ALTER TABLE `tiki_object_relations` modify `source_itemId` VARCHAR(160) NOT NULL;