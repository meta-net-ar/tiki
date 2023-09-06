CREATE TABLE IF NOT EXISTS `tiki_custom_color_modes` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(50) NOT NULL,
	`icon` VARCHAR(50) NOT NULL,
    `custom` VARCHAR(2) NOT NULL DEFAULT 'n',
	`css_variables` TEXT NULL,
	PRIMARY KEY (`id`),
  	UNIQUE INDEX (`name`)
) ENGINE=MyISAM;

INSERT INTO `tiki_custom_color_modes` (`name`, `icon`) VALUES ('light', 'sun'), ('dark','moon'), ('auto', 'circle-half');