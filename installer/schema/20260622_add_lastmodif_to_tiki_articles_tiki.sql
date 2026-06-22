ALTER TABLE `tiki_articles`
    ADD COLUMN `lastModif` int(14) DEFAULT NULL AFTER `created`;
