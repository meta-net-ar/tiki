#2010-05-25 nkoth
ALTER TABLE tiki_tracker_fields ADD COLUMN `validation` VARCHAR(255) DEFAULT '';
ALTER TABLE tiki_tracker_fields ADD COLUMN `validationParam` VARCHAR(255) DEFAULT '';

