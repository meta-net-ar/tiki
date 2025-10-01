CREATE TABLE IF NOT EXISTS `tiki_webauthn_credentials` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user` VARCHAR(100) DEFAULT NULL,
  `device_name` VARCHAR(100) DEFAULT NULL,
  `authenticator_id` VARCHAR(100) DEFAULT NULL,
  `user_handle` VARCHAR(100) DEFAULT NULL,
  `credential_id` text DEFAULT NULL,
  `public_key` text DEFAULT NULL,
  `sign_count` BIGINT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `last_signin` TIMESTAMP NULL DEFAULT NULL
) ENGINE=MyISAM;

INSERT INTO `tiki_menu_options` (`menuId`, `type`, `name`, `url`, `position`, `section`, `perm`, `groupname`, `userlevel`) VALUES (42, 's', 'Webauthn', 'tiki-webauthn.php', 1300, 'auth_webauthn_enabled', '', '', 0);
