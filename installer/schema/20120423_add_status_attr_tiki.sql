ALTER TABLE tiki_history ADD status VARCHAR(60) DEFAULT '' AFTER is_html;
ALTER TABLE tiki_user_modules ADD status VARCHAR(60) DEFAULT '' AFTER parse;
ALTER TABLE tiki_pages ADD status VARCHAR(60) DEFAULT '' AFTER keywords;
