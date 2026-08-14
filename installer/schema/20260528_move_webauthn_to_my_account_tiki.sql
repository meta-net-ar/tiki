-- Move Webauthn from standalone section to child of My Account and rename to Manage Passkeys
UPDATE `tiki_menu_options` SET `name` = 'Manage Passkeys', `type` = 'o', `position` = 120, `section` = 'feature_mytiki,auth_webauthn_enabled', `groupname` = 'Registered' WHERE `name` = 'Webauthn';
