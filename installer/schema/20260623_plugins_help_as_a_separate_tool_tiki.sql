-- Only run on MySQL 8+, because REGEXP_REPLACE is not available in earlier versions and it cause failure in the CI.
-- This script is irrelevant for Tiki 26 and earlier (which use older MySQL versions) because the 'launchplugins' tool was not yet available in those versions.
/*!80000
UPDATE `tiki_preferences`
    SET `value` = REGEXP_REPLACE(`value`, '(^|[|,])\\s*help\\s*([|,]|$)', '$1help,launchplugins$2')
    WHERE `name` LIKE 'toolbar\_%'
      AND `value` RLIKE '[|,]?\s?help\s?[|,]?'
      AND `value` NOT RLIKE '[|,]?\s?launchplugins\s?[|,]?'
*/;
