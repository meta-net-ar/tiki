ALTER TABLE `users_groups` ADD COLUMN `expireAfter` INT(14) DEFAULT 0;
ALTER TABLE `users_usergroups` ADD COLUMN `created` INT(14) DEFAULT NULL;
ALTER TABLE  `users_groups` ADD KEY `expireAfter` (`expireAfter`);
