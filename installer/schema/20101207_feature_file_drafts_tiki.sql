CREATE TABLE IF NOT EXISTS `tiki_file_drafts` (
  `fileId` INT(14) NOT NULL,
  `filename` VARCHAR(80) DEFAULT NULL,
  `filesize` INT(14) DEFAULT NULL,
  `filetype` VARCHAR(250) DEFAULT NULL,
  `data` LONGBLOB,
  `user` VARCHAR(200) DEFAULT '',
  `path` VARCHAR(255) DEFAULT NULL,
  `hash` VARCHAR(32) DEFAULT NULL,
  `lastModif` INTEGER(14) DEFAULT NULL,
  `lockedby` VARCHAR(200) DEFAULT '',
  PRIMARY KEY (`fileId`, `user`)
) ENGINE=MyISAM;