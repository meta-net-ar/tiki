SET @pcant=0;
SELECT (@pcant:=COUNT(*)) FROM users_permissions WHERE `permName` = 'tiki_p_view_newsletter';
INSERT INTO `users_permissions` (`permName`, `permDesc`, `level`, `type`, `admin`, `feature_check`) VALUES('tiki_p_view_newsletter', 'Can view the archive of a newsletters', 'basic', 'newsletters', NULL, 'feature_newsletters');
INSERT INTO users_objectpermissions (`groupName`, `permName`,`objectType`, `objectId`)  SELECT `groupName`, 'tiki_p_view_newsletter', `objectType` , `objectId` FROM users_objectpermissions WHERE `permName`='tiki_p_subscribe_newsletters' AND @pcant=0;
INSERT INTO users_grouppermissions (`groupName`, `permName`)  SELECT `groupName`, 'tiki_p_view_newsletter' FROM users_grouppermissions WHERE `permName`='tiki_p_subscribe_newsletters' AND @pcant=0;