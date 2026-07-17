-- Remove columns from tiki_article_types
ALTER TABLE `tiki_article_types`
DROP COLUMN `show_post_expire`,
DROP COLUMN `show_expdate`,
DROP KEY `show_post_expire`; -- Remove index

-- Remove column from tiki_received_articles
ALTER TABLE `tiki_received_articles`
DROP COLUMN `expireDate`;

-- Remove column from tiki_articles
ALTER TABLE `tiki_articles`
DROP COLUMN `expireDate`,
DROP KEY `expireDate`; -- Remove index

-- Remove column from tiki_submissions
ALTER TABLE tiki_submissions
DROP COLUMN `expireDate`;
