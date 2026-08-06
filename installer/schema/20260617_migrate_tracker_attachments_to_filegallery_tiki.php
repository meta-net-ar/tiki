<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Installer\Installer;

/**
 * The tracker "Attachments" tab (useAttachments option) and the deprecated Attachment
 * (type A) tracker field have both been removed, so nothing can write new rows into
 * tiki_tracker_item_attachments anymore. Any row still there is leftover data from one
 * of those two removed features and would be silently lost on upgrade, so block the
 * upgrade until it has been migrated to a "Files" tracker field with the existing
 * console.php tracker:convert-attachments command.
 *
 * Attachments left behind by tracker items that no longer exist can't be migrated (there
 * is no item left to attach a file to), so those are removed automatically before the check.
 *
 * Once no attachment data remains, also block on any leftover Attachment (type A) field
 * definition: nothing removes those automatically, and they are dead weight that should be
 * removed from their tracker before the upgrade completes.
 *
 * @param Installer $installer
 * @return bool
 */
function upgrade_20260617_migrate_tracker_attachments_to_filegallery_tiki(Installer $installer)
{
    $trklib = TikiLib::lib('trk');

    // Auto-remove orphan attachments whose tracker items no longer exist — they cannot be migrated.
    $installer->query('DELETE a FROM tiki_tracker_item_attachments a LEFT JOIN tiki_tracker_items i USING (itemId) WHERE i.itemId IS NULL');

    $remaining = $installer->fetchAll('SELECT DISTINCT i.trackerId FROM tiki_tracker_item_attachments a JOIN tiki_tracker_items i USING (itemId)');

    if ($remaining) {
        $trackerIds = array_column($remaining, 'trackerId');

        $message = tr('Tiki found tracker item attachments still stored in the database, left over from the removed tracker "Attachments" tab and/or the deprecated Attachment field type. Affected tracker(s):') . PHP_EOL;
        foreach ($trackerIds as $trackerId) {
            $trackerInfo = $trklib->get_tracker($trackerId);
            $trackerName = $trackerInfo['name'] ?? ('#' . $trackerId);
            $message .= "  - $trackerName (trackerId=$trackerId)" . PHP_EOL;
        }
        $message .= PHP_EOL;
        $message .= tr('Before continuing, migrate these attachments to a "Files" tracker field (create one first if the tracker does not have one yet), then run:') . PHP_EOL;
        $message .= '  php console.php tracker:convert-attachments <trackerId> <fieldId> [galleryId]' . PHP_EOL;
        $message .= tr('<fieldId> must be the ID of that "Files" field. Add --preview to check the result first without making any changes.') . PHP_EOL;
        $message .= tr('Run the database update again once all attachments have been migrated.');

        throw new Exception($message);
    }

    // type comparison must be case-sensitive: lowercase 'a' is the unrelated, still valid TextArea field type
    $deprecatedFields = $installer->fetchAll('SELECT fieldId, trackerId, name FROM tiki_tracker_fields WHERE BINARY type = ?', ['A']);

    if ($deprecatedFields) {
        $message = tr('Tiki found tracker fields still using the removed deprecated Attachment field type:') . PHP_EOL;
        foreach ($deprecatedFields as $field) {
            $trackerInfo = $trklib->get_tracker($field['trackerId']);
            $trackerName = $trackerInfo['name'] ?? ('#' . $field['trackerId']);
            $message .= "  - {$field['name']} (fieldId={$field['fieldId']}) in tracker $trackerName (trackerId={$field['trackerId']})" . PHP_EOL;
        }
        $message .= PHP_EOL;
        $message .= tr('These field definitions are no longer usable and must be removed from their tracker (Tracker admin > Fields > select the field(s) > Remove), then run the database update again.');

        throw new Exception($message);
    }

    $installer->query("DELETE FROM tiki_tracker_options WHERE name IN ('useAttachments', 'showAttachments', 'orderAttachments')");
    $installer->query("DELETE FROM users_permissions WHERE permName = 'tiki_p_tracker_view_attachments'");
    $installer->query('DROP TABLE IF EXISTS `tiki_tracker_item_attachments`');

    return true;
}
