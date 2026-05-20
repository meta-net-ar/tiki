UPDATE tiki_modules SET name='who_is_there', params='content=count' WHERE name='logged_users';
UPDATE tiki_modules SET name='who_is_there', params='content=list' WHERE name='online_users';
