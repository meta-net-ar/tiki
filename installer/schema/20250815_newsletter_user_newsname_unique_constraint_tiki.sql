-- Ensure each author can only have one newsletter with a given name
ALTER TABLE `tiki_newsletters` ADD CONSTRAINT `uniq_author_name` UNIQUE (author(100), name(100));
