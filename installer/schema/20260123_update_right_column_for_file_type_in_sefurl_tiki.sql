UPDATE `tiki_sefurl_regex_out`
SET `left` = 'tiki-download_file.php\\?fileId=(\\d+)(?:&amp;|&)display'
WHERE `right` = 'display$1' AND `feature` = 'feature_file_galleries';


UPDATE `tiki_sefurl_regex_out`
SET `left` = 'tiki-download_file.php\\?fileId=(\\d+)(?:&amp;|&)thumbnail'
WHERE `right` = 'thumbnail$1' AND `feature` = 'feature_file_galleries';

UPDATE `tiki_sefurl_regex_out`
SET `left` = 'tiki-download_file.php\\?fileId=(\\d+)(?:&amp;|&)preview'
WHERE `right` = 'preview$1' AND `feature` = 'feature_file_galleries';
