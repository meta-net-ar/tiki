DELETE FROM `tiki_menu_options` WHERE 
`menuId` = 42 AND `type` = 'o' AND `name` = 'New Invoice' AND `url` = 'tiki-edit_invoice.php' AND `position` = 791
AND  `section` = 'feature_invoice' AND `perm` = 'tiki_p_admin' AND `groupname` = '' AND `userlevel` = 0;

DELETE FROM `tiki_menu_options` WHERE 
`menuId` = 42 AND `type` = 'r' AND `name` = 'Invoice' AND `url` = 'tiki-list_invoices.php' AND `position` = 790
AND  `section` = 'feature_invoice' AND `perm` = 'tiki_p_admin' AND `groupname` = '' AND `userlevel` = 0;