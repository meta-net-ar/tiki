ALTER TABLE `tiki_file_galleries` DROP INDEX `parentIdAndName`;
ALTER TABLE `tiki_file_galleries` MODIFY `name` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `tiki_file_galleries` ADD INDEX `parentIdAndName` (`parentId`, `name`(191));
