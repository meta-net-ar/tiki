-- Activate the preference as disabled by default (but preserve if already set)
INSERT IGNORE INTO `tiki_preferences` (`name`, `value`) VALUES ('vimeo_upload', 'n');

-- Permissions
INSERT IGNORE INTO `users_permissions` (`permName`, `level`) VALUES
('tiki_p_admin_vimeo', 'admin');

-- Menu entries
INSERT IGNORE INTO `tiki_menu_options`
(`menuId`,`type`,`name`,`url`,`position`,`section`,`perm`,`groupname`,`userlevel`) VALUES
(42,'s','Vimeo Video','tiki-list_vimeo_entries.php',970,'vimeo_upload','tiki_p_admin | tiki_p_list_videos | tiki_p_upload_videos','',0),
(42,'o','List Vimeo Media','tiki-list_vimeo_entries.php',972,'vimeo_upload','tiki_p_admin | tiki_p_list_videos','',0),
(42,'o','Upload Vimeo Video','tiki-vimeo_upload.php',974,'vimeo_upload','tiki_p_admin | tiki_p_upload_videos','',0);

-- Set icon for the menu section
UPDATE `tiki_menu_options` SET icon = 'vimeo' WHERE `name` = 'Vimeo Video' AND `menuId` = 42;
