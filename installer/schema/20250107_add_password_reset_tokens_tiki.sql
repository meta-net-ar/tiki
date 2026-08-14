CREATE TABLE IF NOT EXISTS `tiki_password_reset_tokens` (
  `tokenId` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(200) NOT NULL,
  `token` varchar(64) NOT NULL,
  `created` int NOT NULL,
  `expires` int NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`tokenId`),
  UNIQUE KEY `token` (`token`),
  KEY `user` (`user`),
  KEY `expires` (`expires`)
) ENGINE=MyISAM; 