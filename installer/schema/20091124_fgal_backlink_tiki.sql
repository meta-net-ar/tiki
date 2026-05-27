CREATE TABLE IF NOT EXISTS `tiki_file_backlinks` (
       `fileId` INT(14) NOT NULL,
       `objectId` INT(12) NOT NULL,
       KEY `objectId` (`objectId`),
       KEY `fileId` (`fileId`)
) ENGINE=MyISAM;