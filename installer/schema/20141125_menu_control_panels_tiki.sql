# xavidp. Option ' Control Panels' is set with a space in purpose, in order to have it shown in the first position under "Settings"
UPDATE tiki_menu_options SET name=' Control Panels' WHERE name=' Panels';
UPDATE tiki_menu_options SET name='Settings' WHERE name='Configuration';