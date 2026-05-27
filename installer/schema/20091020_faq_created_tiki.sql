ALTER TABLE `tiki_faq_questions` ADD COLUMN `created` INT(14) DEFAULT NULL;
ALTER TABLE `tiki_faq_questions` ADD KEY `created` (`created`);