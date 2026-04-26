UPDATE `tiki_sefurl_regex_out`
SET `left` = 'tiki-download_file.php\\?fileId=(\\d+)(?:&amp;|&)display(?:=[^&]*)?(?=(?:&amp;|&|$))'
WHERE `right` = 'display$1'
  AND `feature` = 'feature_file_galleries'
  AND `type` = 'display';

UPDATE `tiki_sefurl_regex_out`
SET `left` = 'tiki-download_file.php\\?fileId=(\\d+)(?:&amp;|&)thumbnail(?:=[^&]*)?(?=(?:&amp;|&|$))'
WHERE `right` = 'thumbnail$1' AND `feature` = 'feature_file_galleries';

UPDATE `tiki_sefurl_regex_out`
SET `left` = 'tiki-download_file.php\\?fileId=(\\d+)(?:&amp;|&)preview(?:=[^&]*)?(?=(?:&amp;|&|$))'
WHERE `right` = 'preview$1' AND `feature` = 'feature_file_galleries';