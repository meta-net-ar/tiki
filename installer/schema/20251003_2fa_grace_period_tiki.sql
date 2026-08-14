ALTER TABLE `users_users`
ADD `twoFactorAuthGracePeriod` INT(11) DEFAULT NULL AFTER `twoFactorSecret`,
ADD `twoFactorGracePeriodStart` INT(14) DEFAULT NULL AFTER `twoFactorAuthGracePeriod`;

ALTER TABLE `users_groups` ADD `twoFactorAuthGracePeriod` INT(11) DEFAULT NULL AFTER `isTplGroup`;
