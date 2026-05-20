ALTER TABLE `tiki_survey_questions` ADD COLUMN `mandatory` CHAR(1) NOT NULL DEFAULT 'n';
ALTER TABLE `tiki_survey_questions` ADD COLUMN `max_answers` INT(5) NOT NULL DEFAULT 0;
ALTER TABLE `tiki_survey_questions` ADD COLUMN `min_answers` INT(5) NOT NULL DEFAULT 0;
