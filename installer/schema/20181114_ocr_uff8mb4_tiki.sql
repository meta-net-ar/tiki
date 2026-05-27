ALTER TABLE `tiki_files` CHANGE `ocr_data` `ocr_data` MEDIUMTEXT COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tiki_files` CHANGE `ocr_lang` `ocr_lang` VARCHAR(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
