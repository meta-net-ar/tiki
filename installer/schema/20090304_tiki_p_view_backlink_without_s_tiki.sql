#sylvieg
UPDATE users_permissions SET `permName`='tiki_p_view_backlink' WHERE `permName`='tiki_p_view_backlinks';
UPDATE users_objectpermissions SET `permName`='tiki_p_view_backlink' WHERE `permName`='tiki_p_view_backlinks';