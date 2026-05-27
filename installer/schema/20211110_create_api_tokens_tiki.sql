CREATE TABLE IF NOT EXISTS `tiki_api_tokens` (
  `tokenId` INT(11) NOT NULL AUTO_INCREMENT,
  `user` VARCHAR(200) NOT NULL,
  `token` VARCHAR(100) NOT NULL,
  `created` INT NOT NULL,
  `lastModif` INT NOT NULL,
  `expireAfter` INT NULL,
  `hits` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`tokenId`),
  KEY `token` (`token`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;
