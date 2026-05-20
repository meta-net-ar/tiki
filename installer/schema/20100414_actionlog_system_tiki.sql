INSERT IGNORE INTO tiki_actionlog_conf(action, `objectType`, status) VALUES ('%', 'system', 'y');
UPDATE `tiki_actionlog_conf` SET `status`='y' WHERE `action`='login' AND `objectType`='system';
UPDATE `tiki_actionlog_conf` SET `status`='y' WHERE `action`='%' AND `objectType`='system';
