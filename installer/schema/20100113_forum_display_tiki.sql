ALTER TABLE `tiki_forums` ADD COLUMN `topics_list_lastpost_title` CHAR(1) DEFAULT NULL AFTER `topics_list_lastpost`;
UPDATE `tiki_forums` SET `topics_list_lastpost_title`=`topics_list_lastpost`;
