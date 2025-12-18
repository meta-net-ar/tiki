-- Convert tiki_queue.entryId from INT to BIGINT to prevent AUTO_INCREMENT overflow
ALTER TABLE `tiki_queue`
  MODIFY COLUMN `entryId` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;
