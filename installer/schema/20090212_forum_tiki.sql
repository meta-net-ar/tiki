#sylvieg
ALTER TABLE `tiki_freetagged_objects` CHANGE `user` `user` VARCHAR(200) DEFAULT '';
ALTER TABLE `tiki_forums_queue` ADD tags VARCHAR(255) DEFAULT NULL;
ALTER TABLE `tiki_forums_queue` ADD email VARCHAR(255) DEFAULT NULL;