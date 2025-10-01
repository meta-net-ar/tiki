-- Rename zend_mail_* preferences to mailer_*
UPDATE tiki_preferences
SET name = CONCAT('mailer', SUBSTRING(name, LENGTH('zend_mail') + 1))
WHERE name LIKE 'zend_mail%';
-- Rename zend_http_* preferences to http_*
UPDATE tiki_preferences
SET name = CONCAT('http', SUBSTRING(name, LENGTH('zend_http') + 1))
WHERE name LIKE 'zend_http%';
