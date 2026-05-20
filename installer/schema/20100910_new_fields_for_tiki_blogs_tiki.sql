ALTER TABLE tiki_blogs ADD COLUMN `use_title` CHAR(1) DEFAULT 'y' AFTER `use_find`;
ALTER TABLE tiki_blogs ADD COLUMN `use_title_in_post` CHAR(1) DEFAULT 'y' AFTER `use_title`;
ALTER TABLE tiki_blogs ADD COLUMN `use_description` CHAR(1) DEFAULT 'y' AFTER `use_title_in_post`;
ALTER TABLE tiki_blogs ADD COLUMN `use_breadcrumbs` CHAR(1) DEFAULT 'n' AFTER `use_description`;
