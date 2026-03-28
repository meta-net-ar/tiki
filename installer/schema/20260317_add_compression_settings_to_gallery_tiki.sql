ALTER TABLE `tiki_file_galleries`
ADD COLUMN `image_compression_enabled` CHAR(1) NOT NULL DEFAULT 'n' AFTER `image_max_size_y`,
ADD COLUMN `image_compression_preset_type` VARCHAR(50) NOT NULL DEFAULT 'slider' AFTER `image_compression_enabled`,
ADD COLUMN `image_compression_quality` INT(8) NOT NULL DEFAULT '85' AFTER `image_compression_preset_type`,
ADD COLUMN `image_compression_save_original` VARCHAR(1) NOT NULL DEFAULT 'n' AFTER `image_compression_quality`,
ADD COLUMN `image_compression_max_size_mb` DECIMAL(8,2) NOT NULL DEFAULT '2.00' AFTER `image_compression_save_original`;