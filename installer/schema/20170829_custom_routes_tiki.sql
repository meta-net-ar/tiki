CREATE TABLE IF NOT EXISTS `tiki_custom_route` (
  `id` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `description` VARCHAR(255) NULL,
  `type` VARCHAR(255) NOT NULL,
  `from` VARCHAR(255) NOT NULL,
  `redirect` text NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT '1',
  `user_id` INT(11) NOT NULL
) ENGINE=MyISAM;
