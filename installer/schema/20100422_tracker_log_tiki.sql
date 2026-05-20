CREATE TABLE IF NOT EXISTS `tiki_tracker_item_field_logs` (
  `version` INT(12) NOT NULL,
  `itemId` INT(12) NOT NULL DEFAULT '0',
  `fieldId` INT(12) NOT NULL DEFAULT '0',
  `value` text,
  `lang` CHAR(16) DEFAULT NULL,
  INDEX `version` (`version`),
  INDEX `itemId` (`itemId`),
  INDEX `fieldId` (`itemId`)
) ENGINE=MyISAM;
INSERT IGNORE INTO tiki_actionlog_conf(action, `objectType`, status) VALUES ('Updated', 'trackeritem', 'n');
INSERT IGNORE INTO tiki_actionlog_conf(action, `objectType`, status) VALUES ('Created', 'trackeritem', 'n');