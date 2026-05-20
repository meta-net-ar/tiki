CREATE TABLE IF NOT EXISTS `tiki_user_mailin_struct` (
    `mailin_struct_id` INT(12) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(200) NOT NULL,
    `subj_pattern` VARCHAR(255) NULL,
    `body_pattern` VARCHAR(255) NULL,
    `structure_id` INT(14) NOT NULL,
    `page_id` INT(14) NULL,
    `is_active` CHAR(1) NULL DEFAULT 'n',
   PRIMARY KEY (`mailin_struct_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;
