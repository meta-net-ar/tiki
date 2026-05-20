ALTER TABLE `tiki_tracker_fields` MODIFY `validationMessage` VARCHAR(255) DEFAULT '' AFTER `validationParam`;
ALTER TABLE `tiki_blogs` MODIFY `show_related` CHAR(1) DEFAULT NULL AFTER `always_owner`;
