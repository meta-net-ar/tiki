ALTER TABLE `tiki_calendar_subscriptions` DROP `calendarId`;
ALTER TABLE `tiki_calendar_subscriptions` DROP KEY `user`;
ALTER TABLE `tiki_calendar_subscriptions` ADD `uri` VARCHAR(100) NOT NULL AFTER `subscriptionId`;
ALTER TABLE `tiki_calendar_subscriptions` ADD KEY(`uri`);