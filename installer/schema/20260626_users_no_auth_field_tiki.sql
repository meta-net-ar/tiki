ALTER TABLE `users_users` ADD COLUMN `no_auth` CHAR(1) DEFAULT 'n' AFTER `last_mfa_date`;
