ALTER TABLE `tiki_comments` DROP INDEX `threaded`;
ALTER TABLE `tiki_comments` ADD KEY `threaded` (`message_id`(40),`in_reply_to`(40),`parentId`);
ALTER TABLE tiki_comments DROP INDEX no_repeats;
ALTER TABLE tiki_comments ADD UNIQUE KEY no_repeats ( parentId, userName(20), title(30), commentDate, message_id(30), in_reply_to(30));