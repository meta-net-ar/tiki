ALTER TABLE users_groups ADD COLUMN `isRole` CHAR(1) DEFAULT 'n';
CREATE TABLE `tiki_categories_roles` (
    `categId` INT(12) NOT NULL,
    `categRoleId` INT(12) NOT NULL,
    `groupRoleId` INT(12) NOT NULL,
    `groupId` INT(12) NOT NULL,
    PRIMARY KEY (`categId`,`categRoleId`,`groupRoleId`)
) ENGINE=MyISAM;
