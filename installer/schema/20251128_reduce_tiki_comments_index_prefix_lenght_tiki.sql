ALTER TABLE `tiki_comments` DROP INDEX `threaded`;
ALTER TABLE `tiki_comments` ADD KEY `threaded` (`message_id`(40),`in_reply_to`(40),`parentId`);
