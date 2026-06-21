UPDATE `tiki_api_tokens`
SET `token` = CONCAT('sha256:', SHA2(`token`, 256))
WHERE LEFT(`token`, 7) != 'sha256:';
