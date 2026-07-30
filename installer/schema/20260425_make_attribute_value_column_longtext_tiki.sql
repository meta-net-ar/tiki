UPDATE tiki_object_attributes SET value = '' WHERE value IS NULL;
ALTER TABLE `tiki_object_attributes` CHANGE `value` `value` longtext NOT NULL;