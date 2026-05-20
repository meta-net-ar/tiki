#2008-11-20 JohannesMoser
ALTER TABLE `tiki_polls` ADD `anonym` ENUM( 'a', 'u', 'i', 'c' ) NOT NULL DEFAULT 'u';
CREATE TABLE IF NOT EXISTS `tiki_poll_votes` (
  `pollId` INT(11) NOT NULL,
  `optionId` INT(11) NOT NULL,
  `voteId` INT(11) NOT NULL AUTO_INCREMENT,
  `identification` VARCHAR(300) NOT NULL,
  `time` INT(11) NOT NULL,
  PRIMARY KEY  (`voteId`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;