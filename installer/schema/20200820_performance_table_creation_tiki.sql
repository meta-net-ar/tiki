INSERT INTO `tiki_menu_options` (`menuId`, `type`, `name`, `url`, `position`, `section`, `perm`, `groupname`, `userlevel`) VALUES (42,'o','Performance','tiki-performance_stats.php',1088,'','tiki_monitor_performance','',0);
CREATE TABLE `tiki_performance` (
    `id` INT(12) NOT NULL AUTO_INCREMENT,
    `url` VARCHAR (255) NOT NULL, -- rt_start, string containing where the start timer came from
    `time_taken` INT(12) NOT NULL, -- Time it took to process
    PRIMARY KEY (`id`)
) ENGINE=MyISAM;

