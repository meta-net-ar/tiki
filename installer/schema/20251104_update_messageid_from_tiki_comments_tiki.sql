ALTER TABLE `tiki_comments`
    DROP INDEX `threaded`,
    DROP INDEX `no_repeats`,
    MODIFY `message_id` TEXT default NULL,
    MODIFY `in_reply_to` varchar(255) default NULL,
    ADD KEY `threaded` (`message_id`(89),`in_reply_to`(88),`parentId`);
