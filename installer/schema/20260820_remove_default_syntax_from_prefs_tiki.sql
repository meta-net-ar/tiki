UPDATE `tiki_preferences` SET `value`= REPLACE(`value`, '{syntax type="tiki" editor="plain"}\n', '') WHERE `value` LIKE '{syntax type="tiki" editor="plain"}%';
