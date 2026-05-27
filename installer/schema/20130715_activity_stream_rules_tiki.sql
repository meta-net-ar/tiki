
CREATE TABLE IF NOT EXISTS `tiki_activity_stream_rules` (
  `ruleId` INT(8) NOT NULL AUTO_INCREMENT,
  `eventType` VARCHAR(100) NOT NULL,
  `ruleType` VARCHAR(20) NOT NULL,
  `rule` TEXT,
  `notes` TEXT,
  PRIMARY KEY(`ruleId`)
) ENGINE=MyISAM;

