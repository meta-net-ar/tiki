
CREATE TABLE IF NOT EXISTS `tiki_activity_stream` (
  `activityId` INT(8) NOT NULL AUTO_INCREMENT,
  `eventType` VARCHAR(100) NOT NULL,
  `eventDate` INT NOT NULL,
  `arguments` BLOB,
  PRIMARY KEY(`activityId`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `tiki_activity_stream_mapping` (
  `field_name` VARCHAR(50) NOT NULL,
  `field_type` VARCHAR(15) NOT NULL,
  PRIMARY KEY(`field_name`)
) ENGINE=MyISAM;

