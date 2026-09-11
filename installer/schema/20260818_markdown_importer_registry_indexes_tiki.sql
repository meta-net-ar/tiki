ALTER TABLE `tiki_markdown_imports`
  DROP INDEX `uq_source_relpath`,
  ADD UNIQUE KEY `uq_source_relpath` (`source_key`(75), `relpath`(165)),
  DROP INDEX `idx_status`,
  ADD KEY `idx_source_status` (`source_key`, `last_status`);
