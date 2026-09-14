DELETE FROM tiki_menu_options WHERE menuId = 42 AND name = 'Encryption Keys' AND (url <> 'tiki-admin.php?page=security&cookietab=6' OR section <> 'feature_user_encryption');
INSERT IGNORE INTO tiki_menu_options (`menuId`, `type`, `name`, `url`, `position`, `section`, `perm`, `groupname`, `userlevel`, `icon`) VALUES (42,'o','Encryption Keys','tiki-admin.php?page=security&cookietab=6',1251,'feature_user_encryption','tiki_p_admin','',0,'key');
UPDATE tiki_menu_options SET icon = 'key' WHERE menuId = 42 AND name = 'Encryption Keys';
