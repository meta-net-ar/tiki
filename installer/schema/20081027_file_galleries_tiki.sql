#gillesm 2008-10-27
ALTER TABLE tiki_file_galleries ADD `groupforAlert` VARCHAR(255) DEFAULT NULL;
ALTER TABLE tiki_file_galleries DROP `groupforAlert`;
