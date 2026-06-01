DROP TABLE IF EXISTS `tiki_tracker_config_history`;
CREATE TABLE `tiki_tracker_config_history` (
  `historyId` int NOT NULL auto_increment,
  `objectType` enum('tracker','trackerfield') NOT NULL,
  `objectId` int NOT NULL,
  `trackerId` int NOT NULL,
  `action` varchar(40) NOT NULL,
  `changedBy` varchar(200) NOT NULL default '',
  `changedAt` int NOT NULL,
  `ip` varchar(39) default NULL,
  `before` mediumtext,
  `after` mediumtext,
  `diff` mediumtext,
  PRIMARY KEY (`historyId`),
  KEY `object_idx` (`objectType`,`objectId`,`changedAt`),
  KEY `tracker_idx` (`trackerId`,`changedAt`),
  KEY `user_idx` (`changedBy`,`changedAt`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;
