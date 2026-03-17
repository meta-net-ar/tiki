ALTER TABLE `tiki_file_galleries` ADD COLUMN `image_compression_enabled` CHAR(1) DEFAULT 'y' AFTER `image_max_size_y`;
ALTER TABLE `tiki_file_galleries` ADD COLUMN `image_compression_preset_type` VARCHAR(50) DEFAULT 'slider' AFTER `image_compression_enabled`;
ALTER TABLE `tiki_file_galleries` ADD COLUMN `image_compression_quality` INT(3) DEFAULT 85 AFTER `image_compression_preset_type`;
ALTER TABLE `tiki_file_galleries` ADD COLUMN `image_compression_save_original` CHAR(1) DEFAULT 'n' AFTER `image_compression_quality`;
ALTER TABLE `tiki_file_galleries` ADD COLUMN `image_compression_max_size_mb` DECIMAL(8,2) DEFAULT 2.0 AFTER `image_compression_save_original`;
UPDATE `tiki_file_galleries` SET `image_max_size_x` = 1600 WHERE `image_max_size_x` = 0;
UPDATE `tiki_file_galleries` SET `image_max_size_y` = 1200 WHERE `image_max_size_y` = 0;
