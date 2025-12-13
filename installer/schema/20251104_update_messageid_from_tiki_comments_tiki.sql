ALTER TABLE `tiki_comments` MODIFY `message_id` TEXT default NULL;
ALTER TABLE `tiki_comments` MODIFY `in_reply_to` varchar(255) default NULL;