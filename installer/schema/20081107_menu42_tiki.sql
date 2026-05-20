# sylvieg
DELETE FROM `tiki_menu_options` WHERE `menuId`='42' AND type='o' AND name='View templates' AND url='tiki-edit_templates.php' AND position='1155' AND section='feature_view_tpl' AND perm='tiki_p_edit_templates' AND groupname='' ;
INSERT INTO `tiki_menu_options` (`menuId`, `type`, `name`, `url`, `position`, `section`, `perm`, `groupname`, `userlevel`) VALUES (42,'o','View Templates','tiki-edit_templates.php',1155,'feature_view_tpl','tiki_p_edit_templates','',2);

DELETE FROM `tiki_menu_options` WHERE `menuId`='42' AND type='o' AND name='Edit CSS' AND url='tiki-edit_css.php' AND position='1158' AND section='feature_editcss' AND perm='tiki_p_create_css' AND groupname='' ;
INSERT INTO `tiki_menu_options` (`menuId`, `type`, `name`, `url`, `position`, `section`, `perm`, `groupname`, `userlevel`) VALUES (42,'o','Edit CSS','tiki-edit_css.php',1158,'feature_editcss','tiki_p_create_css','',2);

DELETE FROM `tiki_menu_options` WHERE `menuId`='42' AND type='5' AND name='Admin' AND url='tiki-admin.php' AND position='1050' AND section='feature_edit_templates' AND perm='tiki_p_edit_templates' AND groupname='' ;
INSERT INTO `tiki_menu_options` (`menuId`, `type`, `name`, `url`, `position`, `section`, `perm`, `groupname`, `userlevel`) VALUES (42,'r','Admin','tiki-admin.php',1050,'feature_edit_templates','tiki_p_edit_templates','',0);
DELETE FROM `tiki_menu_options` WHERE `menuId`='42' AND type='5' AND name='Admin' AND url='tiki-admin.php' AND position='1050' AND section='feature_view_tpl' AND perm='tiki_p_edit_templates' AND groupname='' ;
INSERT INTO `tiki_menu_options` (`menuId`, `type`, `name`, `url`, `position`, `section`, `perm`, `groupname`, `userlevel`) VALUES (42,'r','Admin','tiki-admin.php',1050,'feature_view_tpl','tiki_p_edit_templates','',0);
DELETE FROM `tiki_menu_options` WHERE `menuId`='42' AND type='5' AND name='Admin' AND url='tiki-admin.php' AND position='1050' AND section='feature_editcss' AND perm='tiki_p_create_css' AND groupname='' ;
INSERT INTO `tiki_menu_options` (`menuId`, `type`, `name`, `url`, `position`, `section`, `perm`, `groupname`, `userlevel`) VALUES (42,'r','Admin','tiki-admin.php',1050,'feature_editcss','tiki_p_create_css','',0);