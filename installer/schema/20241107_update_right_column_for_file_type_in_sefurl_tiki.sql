UPDATE `tiki_sefurl_regex_out`
SET `right` = 'display$1'
WHERE `type` = 'file' AND `feature` = 'feature_file_galleries';
