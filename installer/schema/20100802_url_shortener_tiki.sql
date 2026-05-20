DROP TABLE IF EXISTS `tiki_url_shortener`;
CREATE TABLE IF NOT EXISTS `tiki_url_shortener` (
  `urlId` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user` VARCHAR(200) NOT NULL,
  `longurl` TINYTEXT NOT NULL,
  `longurl_hash` VARCHAR(32) NOT NULL,
  `service` VARCHAR(32) NOT NULL,
  `shorturl` VARCHAR(63) NOT NULL,
  PRIMARY KEY (`urlId`),
  UNIQUE KEY `shorturl` (`shorturl`),
  KEY `longurl_hash` (`longurl_hash`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;