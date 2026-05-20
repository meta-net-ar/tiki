#2008-12-11 niclone
CREATE TABLE IF NOT EXISTS `tiki_sent_newsletters_files` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `editionId` INT(11) NOT NULL,
  `name` VARCHAR(256) NOT NULL,
  `type` VARCHAR(64) NOT NULL,
  `size` INT(11) NOT NULL,
  `filename` VARCHAR(256) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `editionId` (`editionId`)
) ENGINE=MyISAM;
