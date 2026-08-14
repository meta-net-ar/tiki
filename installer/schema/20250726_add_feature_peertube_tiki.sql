-- Activate the preference as disabled by default
INSERT INTO `tiki_preferences` (`name`, `value`) VALUES ('feature_peertube', 'n')
ON DUPLICATE KEY UPDATE `value` = 'n';

-- Permissions
INSERT IGNORE INTO `users_permissions` (`permName`, `level`) VALUES
('tiki_p_admin_peertube', 'admin');

-- Menu entries
INSERT IGNORE INTO `tiki_menu_options`
(`menuId`,`type`,`name`,`url`,`position`,`section`,`perm`,`groupname`,`userlevel`) VALUES
(42,'s','PeerTube Video','tiki-list_peertube_entries.php',960,'feature_peertube','tiki_p_admin | tiki_p_admin_peertube | tiki_p_list_videos','',0),
(42,'o','List PeerTube Media','tiki-list_peertube_entries.php',962,'feature_peertube','tiki_p_admin | tiki_p_admin_peertube | tiki_p_list_videos','',0),
(42,'o','Upload PeerTube Video','tiki-peertube_upload.php',964,'feature_peertube','tiki_p_admin | tiki_p_admin_peertube | tiki_p_upload_videos','',0);

-- Set icon for the menu section
UPDATE `tiki_menu_options` SET icon = 'video' WHERE `name` = 'PeerTube Video' AND `menuId` = 42;
