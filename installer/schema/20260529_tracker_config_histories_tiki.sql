DROP TABLE IF EXISTS `tiki_tracker_config_histories`;
CREATE TABLE `tiki_tracker_config_histories` (
  `historyId` int NOT NULL auto_increment,
  `objectType` enum('tracker','trackerfield') NOT NULL,
  `objectId` int NOT NULL,
  `action` varchar(40) NOT NULL,
  `user` varchar(200) NOT NULL default '',
  `lastModif` int NOT NULL,
  `ip` varchar(39) default NULL,
  `version` int NOT NULL default 1,
  `data` mediumtext,
  PRIMARY KEY (`historyId`),
  KEY `object_idx` (`objectType`,`objectId`,`lastModif`),
  KEY `user_idx` (`user`,`lastModif`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;
