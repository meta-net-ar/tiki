ALTER TABLE `tiki_auth_tokens` CHANGE `token` `token` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin;

INSERT IGNORE INTO `tiki_preferences` (`name`, `value`) VALUES ('auth_token_secret', SHA2(CONCAT(UUID(), RAND()), 256));
