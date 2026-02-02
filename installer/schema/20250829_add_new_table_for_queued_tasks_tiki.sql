DROP TABLE IF EXISTS `tiki_queued_tasks`;
CREATE TABLE `tiki_queued_tasks` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `owner` INT(11) NOT NULL,
  `type` VARCHAR(20) NOT NULL,
  `params` LONGTEXT NULL DEFAULT NULL,
  `status` ENUM('Pending','InProgress','Completed','Failed') NOT NULL DEFAULT 'Pending',
  `result` LONGTEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `started_at` TIMESTAMP NULL DEFAULT NULL,
  `ended_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_owner` (`owner`)
) ENGINE=MyISAM;

INSERT INTO `tiki_menu_options` (`menuId`, `type`, `name`, `url`, `position`, `section`, `perm`, `groupname`, `userlevel`) VALUES (42, 'o', 'Queued Tasks', 'tiki-admin_queued_tasks.php', 1271, 'feature_queued_tasks', 'tiki_p_admin', '', 0);
