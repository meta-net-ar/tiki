UPDATE IGNORE `tiki_preferences`
SET `name` = 'markdown_wysiwyg_initial_edit_type'
WHERE `name` = 'markdown_wysiwyg_intitial_edit_type';

DELETE FROM `tiki_preferences`
WHERE `name` = 'markdown_wysiwyg_intitial_edit_type';
