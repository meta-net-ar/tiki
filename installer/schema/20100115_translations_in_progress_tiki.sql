CREATE TABLE IF NOT EXISTS `tiki_translations_in_progress` (
   `page_id` INT(14) NOT NULL,
   `language` CHAR(2) NOT NULL,
   KEY `page_id` (`page_id`),
   KEY `language` (`language`),
   UNIQUE (`page_id`, `language`)
) ENGINE=MyISAM;