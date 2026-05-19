<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Installer\Installer;

function upgrade_20260519_itemlink_duplicate_cascade_tiki(Installer $installer): void
{
    $fields = $installer->fetchAll(
        "SELECT fieldId, options FROM tiki_tracker_fields WHERE type = 'r'"
    );

    foreach ($fields as $field) {
        $options = @json_decode($field['options'], true);

        if (! is_array($options)) {
            continue;
        }

        $updated = Tracker_Field_ItemLink::syncDuplicateCascadeDefaultForUpgrade($options);

        if ($updated === $options) {
            continue;
        }

        $installer->query(
            "UPDATE tiki_tracker_fields SET options = ? WHERE fieldId = ?",
            [json_encode($updated), $field['fieldId']]
        );
    }
}
