UPDATE `tiki_sefurl_regex_out`
SET `right` = 'dl$1'
WHERE `type` = 'file' AND `feature` = 'feature_file_galleries' and `right` = 'display$1';