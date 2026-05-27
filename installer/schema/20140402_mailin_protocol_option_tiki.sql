ALTER TABLE `tiki_mailin_accounts`
    CHANGE `pop` `host` VARCHAR(255) DEFAULT NULL,
    ADD COLUMN `protocol` VARCHAR(10) NOT NULL DEFAULT 'pop' AFTER `account`;
