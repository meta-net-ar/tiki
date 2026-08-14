<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Installer\Installer;

/**
 * Adds tiki.file.attach relation from emails (files) stored in EmailFolder tracker field to the relevant tracker item.
 *
 * @param Installer $installer
 */
function upgrade_20240531_tiki_file_attach_relation_to_emails_tiki($installer)
{
    global $prefs;

    // temporarily disable incremental updates to speed up the relation inserts for bigger databases
    $old_incremental = $prefs['unified_incremental_update'];
    $prefs['unified_incremental_update'] = 'n';

    $relationlib = TikiLib::lib('relation');
    $rows = $installer->fetchAll("SELECT ttif.itemId, ttif.fieldId, ttif.value FROM `tiki_tracker_item_fields` ttif left join tiki_tracker_fields ttf on ttif.fieldId = ttf.fieldId WHERE ttf.type = 'EF'");
    foreach ($rows as $row) {
        $data = @json_decode($row['value'], true);
        if (empty($data)) {
            continue;
        }
        if (! is_array($data)) {
            continue;
        }
        foreach ($data as $folder => $fileIds) {
            if (empty($fileIds) || ! is_array($fileIds)) {
                continue;
            }
            foreach ($fileIds as $fileId) {
                if (! empty($fileId)) {
                    $relationlib->add_relation('tiki.file.attach', 'trackeritem', $row['itemId'], 'file', $fileId, true, $row['fieldId']);
                }
            }
        }
    }

    $prefs['unified_incremental_update'] = $old_incremental;
}
