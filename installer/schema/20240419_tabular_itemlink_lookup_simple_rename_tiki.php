<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Installer\Installer;

/**
 * Change various default prefs as discussed on https://dev.tiki.org/Tiki27-Pref-Changes-Discussion
 *
 * @param Installer $installer
 */
function upgrade_20240419_tabular_itemlink_lookup_simple_rename_tiki($installer)
{
    $tabulars = $installer->fetchAll("SELECT tabularId, trackerId, format_descriptor FROM tiki_tabular_formats");
    foreach ($tabulars as $tabular) {
        $descriptor = @json_decode($tabular['format_descriptor']);
        if (empty($descriptor)) {
            continue;
        }
        if (! is_array($descriptor)) {
            continue;
        }
        foreach ($descriptor as $i => $field_descriptor) {
            if (empty($field_descriptor->mode) || $field_descriptor->mode != 'lookup-simple') {
                continue;
            }
            $fields = $installer->fetchAll("SELECT fieldId, type, options FROM tiki_tracker_fields WHERE trackerId = ? AND permName = ?", [$tabular['trackerId'], $field_descriptor->field]);
            if (empty($fields) || $fields[0]['type'] !== 'r') {
                continue;
            }
            $opts = @json_decode($fields[0]['options'], true);
            if (empty($opts['selectMultipleValues'])) {
                continue;
            }
            $descriptor[$i]->mode = 'lookup-simple new line separated';
        }
        $descriptor = json_encode($descriptor);
        $installer->query("UPDATE tiki_tabular_formats SET format_descriptor = ? WHERE tabularId = ?", [$descriptor, $tabular['tabularId']]);
    }
}
