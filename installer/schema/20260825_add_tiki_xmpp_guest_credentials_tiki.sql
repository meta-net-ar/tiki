CREATE TABLE IF NOT EXISTS `tiki_xmpp_guest_credentials` (
  `username` varchar(64) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created` int NOT NULL,
  `last_seen` int NOT NULL,
  PRIMARY KEY (`username`),
  KEY `last_seen` (`last_seen`)
) ENGINE=MyISAM;
