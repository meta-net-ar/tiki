<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Installer\Installer;

/**
 * Repairs the values stored by Computed (type C) tracker fields, which displayed as empty
 * because computeFields() saved to the "itemId" event argument while tracker item events
 * carry it as "object", sending every result to itemId 0.
 *
 * That is fixed in Tracker_Field_Computed. This removes the itemId 0 rows and recomputes
 * every computed field of every item.
 *
 * @param Installer $installer
 * @return void
 */
function upgrade_20260924_fix_computed_tracker_field_values_tiki(Installer $installer)
{
    $computedFields = $installer->fetchAll(
        'SELECT fieldId, trackerId FROM `tiki_tracker_fields` WHERE `type` = ?',
        ['C']
    );

    if (! $computedFields) {
        return;
    }

    $installer->query(
        'DELETE FROM `tiki_tracker_item_fields` WHERE `itemId` = 0 AND `fieldId` IN ('
        . implode(',', array_map('intval', array_column($computedFields, 'fieldId'))) . ')'
    );

    $trklib = TikiLib::lib('trk');

    foreach (array_unique(array_column($computedFields, 'trackerId')) as $trackerId) {
        $definition = Tracker_Definition::get($trackerId);

        if (! $definition) {
            continue;
        }

        $items = $installer->fetchAll(
            'SELECT itemId FROM `tiki_tracker_items` WHERE `trackerId` = ?',
            [(int) $trackerId]
        );

        foreach (array_column($items, 'itemId') as $itemId) {
            $values = $trklib->get_tracker_item($itemId);

            if (! is_array($values)) {
                continue;
            }

            foreach ($definition->getFields() as $field) {
                if ($field['type'] != 'C') {
                    continue;
                }

                $fieldId = $field['fieldId'];
                $handler = $definition->getFieldFactory()->getHandler($field, $values);
                $value = $handler->getFieldData()['value'];

                if ($value !== null && (string) $value !== (string) ($values[$fieldId] ?? '')) {
                    $trklib->modify_field($itemId, $fieldId, $value);
                }

                $values[$fieldId] = $value;
            }
        }
    }
}
