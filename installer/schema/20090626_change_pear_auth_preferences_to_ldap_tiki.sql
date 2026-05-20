UPDATE tiki_preferences SET name='ldap_create_user_tiki' WHERE name='auth_create_user_tiki';
UPDATE tiki_preferences SET name='ldap_create_ldap_tiki' WHERE name='auth_create_auth_tiki';
UPDATE tiki_preferences SET name='ldap_skip_admin' WHERE name='auth_skip_admin';
UPDATE tiki_preferences SET name='auth_ldap_host' WHERE name='auth_pear_host';
UPDATE tiki_preferences SET name='auth_ldap_port' WHERE name='auth_pear_port';
UPDATE tiki_preferences SET value='ldap' WHERE name='auth_method' AND value='auth';
ALTER TABLE users_groups ADD COLUMN(`isExternal` CHAR DEFAULT 'n');
