-- ALTER TABLE `users_users` ADD COLUMN `default_admin` bool DEFAULT false;
ALTER TABLE `users_users`
    ADD COLUMN `default_admin` bool DEFAULT null,
    ADD UNIQUE KEY `uniq_default_admin` (`default_admin`);

UPDATE `users_users` SET `default_admin` = true WHERE `login` = 'admin';
