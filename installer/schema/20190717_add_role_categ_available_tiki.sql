CREATE TABLE `tiki_categories_roles_available` (
    `categId` INT(12) NOT NULL,
    `categRoleId` INT(12) NOT NULL,
    PRIMARY KEY (`categId`,`categRoleId`)
) ENGINE=MyISAM;