DROP TABLE IF EXISTS `tiki_rss_proxy`;
CREATE TABLE `tiki_rss_proxy` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `host_name` TEXT NOT NULL,
  `port` VARCHAR(20) NOT NULL,
  `username` TEXT NULL DEFAULT NULL,
  `password` TEXT NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;
