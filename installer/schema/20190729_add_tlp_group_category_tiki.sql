ALTER TABLE tiki_categories ADD COLUMN `tplGroupContainerId` INT(12) DEFAULT NULL;
ALTER TABLE tiki_categories ADD COLUMN `tplGroupPattern` VARCHAR(200) DEFAULT NULL;