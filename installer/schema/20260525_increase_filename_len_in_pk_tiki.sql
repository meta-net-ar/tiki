ALTER TABLE `tiki_secdb`
    ADD COLUMN `filename_hash` CHAR(32) NOT NULL DEFAULT '' AFTER `filename`;

UPDATE `tiki_secdb`
SET `filename_hash` = MD5(`filename`)
WHERE `filename_hash` = '';

ALTER TABLE `tiki_secdb`
DROP PRIMARY KEY,
ADD PRIMARY KEY (`filename_hash`, `tiki_version`(20));