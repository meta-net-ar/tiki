UPDATE `tiki_modules` SET `rows` = 10 WHERE `rows` IS NULL OR `rows` <= 0;
ALTER TABLE `tiki_modules` CHANGE `rows` `rows` int NOT NULL DEFAULT 10;
