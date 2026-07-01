ALTER TABLE `users_users` ADD COLUMN `login_disabled` CHAR(1) DEFAULT 'n' AFTER `last_mfa_date`;
