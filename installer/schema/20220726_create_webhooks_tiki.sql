CREATE TABLE IF NOT EXISTS `tiki_webhooks` (
  `webhookId` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(200) NOT NULL,
  `user` VARCHAR(200) NOT NULL,
  `verification` VARCHAR(100) NOT NULL,
  `algo` VARCHAR(100) NOT NULL,
  `signatureHeader` VARCHAR(100) NOT NULL,
  `secret` text NOT NULL,
  `created` INT NOT NULL,
  `lastModif` INT NOT NULL,
  PRIMARY KEY (`webhookId`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;
