CREATE TABLE IF NOT EXISTS `tiki_2fa_email_tokens` (
  `userId` INT(8) NOT NULL,
  `type` VARCHAR(10) NOT NULL,
  `token` VARCHAR(60) NOT NULL,
  `attempts` INT NOT NULL DEFAULT 0,
  `created` bigint NOT NULL,
  PRIMARY KEY (`userId`)
) ENGINE=MyISAM;

ALTER TABLE `users_users` ADD COLUMN `last_mfa_date` bigint DEFAULT NULL;
