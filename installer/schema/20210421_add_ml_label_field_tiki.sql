ALTER TABLE `tiki_machine_learning_models` ADD `labelField` VARCHAR(191) AFTER `trackerFields`;
ALTER TABLE `tiki_machine_learning_models` ADD `ignoreEmpty` TINYINT(1) AFTER `labelField`;
