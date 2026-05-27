ALTER TABLE `tiki_tracker_fields`
  ADD `encryptionKeyId` INT(11) NULL,
  ADD INDEX `encryptionKeyId` (`encryptionKeyId`);
