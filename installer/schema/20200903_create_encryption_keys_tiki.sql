CREATE TABLE IF NOT EXISTS `tiki_encryption_keys` (
  `keyId` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `description` text NULL,
  `algo` VARCHAR(50) NULL,
  `shares` INT(11) NOT NULL,
  `users` text NULL,
  `secret` VARCHAR(191) NOT NULL,
  PRIMARY KEY  (`keyId`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;
