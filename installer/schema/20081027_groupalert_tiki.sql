#gillesm 2008-10-27
#2008_10_26 GillesM
CREATE TABLE IF NOT EXISTS tiki_groupalert (    `groupName` VARCHAR(255) NOT NULL DEFAULT '', `objectType` VARCHAR( 20 ) NOT NULL DEFAULT '', `objectId`  VARCHAR(10) NOT NULL DEFAULT '', `displayEachuser`  CHAR( 1 ) DEFAULT NULL , PRIMARY KEY ( `groupName`,`objectType`,`objectId`) ) ENGINE=MyISAM ;
