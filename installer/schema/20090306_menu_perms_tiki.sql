#sylvieg
UPDATE `tiki_menu_options` SET `perm`='tiki-list_file_gallery.php|tiki_p_view_file_gallery|tiki_p_upload_files' WHERE url='tiki-list_file_gallery.php' AND `type`='s';
UPDATE `tiki_menu_options` SET `perm`='tiki_p_upload_files' WHERE url='tiki-upload_file.php';