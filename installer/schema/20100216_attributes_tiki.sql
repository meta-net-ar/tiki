CREATE TABLE IF NOT EXISTS `tiki_object_attributes` (
    `attributeId` INT PRIMARY KEY AUTO_INCREMENT,
    `type` VARCHAR(50) NOT NULL,
    `itemId` VARCHAR(255) NOT NULL,
    `attribute` VARCHAR(25) NOT NULL,
    `value` VARCHAR(100),
    UNIQUE `item_attribute_uq` ( `type`, `itemId`, `attribute` ),
    KEY `attribute_lookup_ix` (`attribute`, `value`)
) ENGINE=MyISAM;
