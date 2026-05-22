CREATE TABLE IF NOT EXISTS `tiki_bruteforce_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `operation` VARCHAR(50) NOT NULL,
  `properties` TEXT NOT NULL,
  `properties_hash` CHAR(64) NOT NULL,
  `attempt_time` bigint NOT NULL,
  `attempt_count` INT NOT NULL,
  INDEX `idx_operation_hash` (`operation`, `properties_hash`),
  INDEX `idx_attempt_time` (`attempt_time`)
) ENGINE=MyISAM;
