CREATE TABLE IF NOT EXISTS `tiki_password_reset_tokens` (
  `tokenId` INT(11) NOT NULL AUTO_INCREMENT,
  `user` VARCHAR(200) NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `created` INT NOT NULL,
  `expires` INT NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`tokenId`),
  UNIQUE KEY `token` (`token`),
  KEY `user` (`user`),
  KEY `expires` (`expires`)
) ENGINE=MyISAM; 