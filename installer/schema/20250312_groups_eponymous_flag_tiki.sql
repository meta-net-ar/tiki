ALTER TABLE `users_groups` ADD `isEponymousGroup` CHAR(1) NOT NULL DEFAULT 'n';
UPDATE `users_groups` SET `isEponymousGroup` = IF((SELECT `value` FROM `tiki_preferences` WHERE `name` = 'eponymousGroups') = 'y' AND EXISTS(SELECT `userId` FROM `users_users` WHERE `login` = `users_groups`.`groupName`), 'y', 'n');
