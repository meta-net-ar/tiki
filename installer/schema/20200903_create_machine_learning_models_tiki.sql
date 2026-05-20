CREATE TABLE IF NOT EXISTS `tiki_machine_learning_models` (
  `mlmId` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `description` text NULL,
  `sourceTrackerId` INT(11) NOT NULL,
  `trackerFields` text NULL,
  `payload` text NULL,
  PRIMARY KEY  (`mlmId`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;
