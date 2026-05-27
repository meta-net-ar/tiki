ALTER TABLE `tiki_blogs` ADD COLUMN `use_excerpt` CHAR(1) DEFAULT NULL AFTER `use_author`;
ALTER TABLE `tiki_blog_posts` ADD COLUMN `excerpt` text DEFAULT NULL AFTER `data_size`;
