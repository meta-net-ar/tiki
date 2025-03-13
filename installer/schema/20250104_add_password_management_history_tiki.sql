CREATE TABLE IF NOT EXISTS `tiki_user_passwords_history` (
  `passId` int(8) NOT NULL auto_increment,
  `user` varchar(200) NOT NULL default '',
  `hash` varchar(60) default NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(`passId`)
) ENGINE=MyISAM;
