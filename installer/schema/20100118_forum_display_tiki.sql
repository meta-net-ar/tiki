ALTER TABLE `tiki_forums` ADD COLUMN `topics_list_lastpost_avatar` CHAR(1) DEFAULT NULL AFTER `topics_list_lastpost_title`;
ALTER TABLE `tiki_forums` ADD COLUMN `topics_list_author_avatar` CHAR(1) DEFAULT NULL AFTER `topics_list_author`;
