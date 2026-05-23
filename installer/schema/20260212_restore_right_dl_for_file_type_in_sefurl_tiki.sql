UPDATE `tiki_sefurl_regex_out`
SET `right` = 'dl$1'
WHERE `left` = 'tiki-download_file.php\\?fileId=(\\d+)'
  AND `feature` = 'feature_file_galleries'
  AND `type` = 'file';
