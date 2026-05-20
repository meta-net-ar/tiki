# This contains 'on' or NULL
ALTER TABLE `tiki_sent_newsletters` ADD COLUMN `is_html` VARCHAR(2) DEFAULT NULL;
