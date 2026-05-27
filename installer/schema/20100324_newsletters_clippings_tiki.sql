ALTER TABLE `tiki_newsletters` ADD COLUMN `allowArticleClip` CHAR(1) DEFAULT 'y';
ALTER TABLE `tiki_newsletters` ADD COLUMN `autoArticleClip` CHAR(1) DEFAULT 'n';
ALTER TABLE `tiki_newsletters` ADD COLUMN `articleClipTypes` text;
ALTER TABLE `tiki_newsletters` ADD COLUMN `articleClipRange` INT(14) DEFAULT NULL;
