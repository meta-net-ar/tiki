ALTER TABLE `tiki_articles`
    ADD COLUMN `lastModif` int DEFAULT NULL AFTER `created`;
