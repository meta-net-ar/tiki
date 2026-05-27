CREATE TABLE IF NOT EXISTS `tiki_todo` (
    `todoId` INT(12) NOT NULL AUTO_INCREMENT,
    `after` INT(12) NOT NULL,
    `event` ENUM('creation', 'modification', 'upload'),
    `objectType` VARCHAR(50),
    `objectId` VARCHAR(255) DEFAULT NULL,
    `from` VARCHAR(255) DEFAULT NULL,
    `to` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`todoId`),
    KEY `what` (`objectType`, `objectId`),
    KEY `after` (`after`)
) ENGINE=MyISAM;
CREATE TABLE IF NOT EXISTS `tiki_todo_notif` (
    `todoId` INT(12) NOT NULL,
    `objectId` VARCHAR(255) DEFAULT NULL,
    KEY `todoId` (`todoId`),
    KEY `objectId` (`objectId`)
) ENGINE=MyISAM;
