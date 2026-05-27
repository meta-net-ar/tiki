-- 2009-10-21 lphuberdeau - Upstreaming from Mozilla

CREATE TABLE IF NOT EXISTS `tiki_page_lists` (
  `list_type_id` INT(8) UNSIGNED NOT NULL,
  `priority` INT(8) UNSIGNED NOT NULL,
  `page_name` VARCHAR(160) NOT NULL,
  `score` FLOAT DEFAULT NULL,
  PRIMARY KEY  (`list_type_id`,`page_name`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `tiki_page_list_types` (
  `id` INT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(40) NOT NULL,
  `title` VARCHAR(160) DEFAULT NULL,
  `description` VARCHAR(200) DEFAULT NULL,
  PRIMARY KEY  (`name`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

