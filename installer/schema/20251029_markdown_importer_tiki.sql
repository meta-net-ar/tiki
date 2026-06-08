CREATE TABLE `tiki_markdown_imports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source_key` VARCHAR(190) NOT NULL,
  `relpath` VARCHAR(500) NOT NULL,
  `page_name` VARCHAR(255) NOT NULL,
  `checksum` VARCHAR(64) NOT NULL,
  `size_bytes` BIGINT NULL,
  `mtime_utc` INT NULL,
  `last_imported` INT NOT NULL,
  `last_status` ENUM('ok','skipped','error','orphan') NOT NULL DEFAULT 'ok',
  `orphaned_at` INT NULL,
  `error_note` TEXT NULL,
  `is_journal` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_source_relpath` (`source_key`(120), `relpath`(120)),
  KEY `idx_page` (`page_name`(190)),
  KEY `idx_status` (`last_status`),
  KEY `idx_orphaned` (`orphaned_at`)
) ENGINE=MyISAM;