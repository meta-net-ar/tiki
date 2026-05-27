CREATE TABLE IF NOT EXISTS `tiki_invit` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `inviter` VARCHAR(200) NOT NULL,
  `groups` VARCHAR(255) DEFAULT NULL,
  `ts` INT(11) NOT NULL,
  `emailsubject` VARCHAR(255) NOT NULL,
  `emailcontent` text NOT NULL,
  `wikicontent` text,
  `wikipageafter` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `tiki_invited` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_invit` INT(11) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `firstname` VARCHAR(24) NOT NULL,
  `lastname` VARCHAR(24) NOT NULL,
  `used` enum('no','registered','logged') NOT NULL,
  `used_on_user` VARCHAR(200) DEFAULT NULL,
  PRIMARY KEY  (`id`),
  KEY `id_invit` (`id_invit`),
  KEY `used_on_user` (`used_on_user`)
) ENGINE=MyISAM;

INSERT INTO `users_permissions` (`permName`, `permDesc`, `level`, `type`, `admin`, `feature_check`) VALUES ('tiki_p_invit', 'Can invit users by email, and include them in groups', 'registered', 'tiki', NULL, 'feature_invit');
