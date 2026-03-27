-- This patch is for testing the CI warning capture. It should be deleted before merging.
-- It triggers a 1292 truncated double warning on populated databases.
UPDATE `tiki_preferences` SET `value` = 'true' WHERE `name` = 0;
