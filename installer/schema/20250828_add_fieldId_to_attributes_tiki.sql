ALTER TABLE `tiki_object_attributes` ADD COLUMN `fieldId` INT DEFAULT NULL AFTER `value`;
UPDATE `tiki_object_attributes` toa
    SET toa.`fieldId` = (
        SELECT ttf.`fieldId`
        FROM `tiki_tracker_fields` ttf, `tiki_tracker_items` tti
        WHERE tti.itemId = toa.`itemId` AND ttf.trackerId = tti.trackerId AND ttf.type = 'CAL'
        LIMIT 1
    )
    WHERE toa.`attribute` = 'tiki.calendar.item' and toa.`type` = 'trackeritem';