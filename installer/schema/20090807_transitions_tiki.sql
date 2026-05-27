-- 2009-08-07 lphuberdeau

INSERT INTO `users_permissions` (`permName`, `permDesc`, `level`, `type`, `admin`, `feature_check`) VALUES('tiki_p_trigger_transition', 'Can trigger the transition between two states', 'admin', 'transition', NULL, 'feature_group_transition,feature_category_transition');

CREATE TABLE IF NOT EXISTS `tiki_transitions` (
    `transitionId` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50),
    `preserve` INT(1) NOT NULL DEFAULT 0,
    `type` VARCHAR(20) NOT NULL,
    `from` VARCHAR(255) NOT NULL,
    `to` VARCHAR(255) NOT NULL,
    PRIMARY KEY(`transitionId`),
    KEY `transition_lookup` (`type`, `from`)
) ENGINE=MyISAM;

