#nyloth
ALTER TABLE `tiki_newsletter_subscriptions` ADD COLUMN `included` CHAR(1) NOT NULL DEFAULT 'n';
