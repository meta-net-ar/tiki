INSERT IGNORE INTO `tiki_menu_options` (`menuId`, `type`, `name`, `url`, `position`, `section`, `perm`, `groupname`, `userlevel`) VALUES (42,'o','Wiki configs','tiki-admin.php?page=wiki',260,'feature_wiki','tiki_p_admin','',0);
UPDATE `tiki_menu_options` SET `icon` = 'cog' WHERE `name` = 'Wiki configs';
