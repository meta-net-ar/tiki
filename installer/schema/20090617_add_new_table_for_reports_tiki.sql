CREATE TABLE IF NOT EXISTS `tiki_user_reports` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user` VARCHAR(200) COLLATE latin1_general_ci NOT NULL,
  `interval` VARCHAR(20) COLLATE latin1_general_ci NOT NULL,
  `view` VARCHAR(8) COLLATE latin1_general_ci NOT NULL,
  `type` VARCHAR(5) COLLATE latin1_general_ci NOT NULL,
  `time_to_send` DATETIME NOT NULL,
  `always_email` TINYINT(1) NOT NULL,
  `last_report` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `tiki_user_reports_cache` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user` VARCHAR(200) COLLATE latin1_general_ci NOT NULL,
  `event` VARCHAR(200) COLLATE latin1_general_ci NOT NULL,
  `data` text COLLATE latin1_general_ci NOT NULL,
  `time` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;