CREATE TABLE IF NOT EXISTS `tiki_object_relations` (
    `relationId` INT PRIMARY KEY AUTO_INCREMENT,
    `relation` VARCHAR(25) NOT NULL,
    `source_type` VARCHAR(50) NOT NULL,
    `source_itemId` VARCHAR(255) NOT NULL,
    `target_type` VARCHAR(50) NOT NULL,
    `target_itemId` VARCHAR(255) NOT NULL,
    KEY `relation_source_ix` (`source_type`, `source_itemId`),
    KEY `relation_target_ix` (`target_type`, `target_itemId`)
) ENGINE=MyISAM;
