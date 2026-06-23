UPDATE `tiki_preferences`
    SET `value` = REGEXP_REPLACE(`value`, '(^|[|,])\\s*help\\s*([|,]|$)', '$1help,launchplugins$2')
    WHERE `name` LIKE 'toolbar\_%'
      AND `value` RLIKE '[|,]?\s?help\s?[|,]?'
      AND `value` NOT RLIKE '[|,]?\s?launchplugins\s?[|,]?';
