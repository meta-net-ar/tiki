CREATE TABLE IF NOT EXISTS `tiki_wiki_recycle_bin` (
  `pageName` VARCHAR(160) NOT NULL,
  `deletedAt` INT(14) NOT NULL,
  `deletedBy` VARCHAR(200) NOT NULL DEFAULT '',
  `comment` MEDIUMTEXT NULL,
  PRIMARY KEY (`pageName`)
) ENGINE=MyISAM;
