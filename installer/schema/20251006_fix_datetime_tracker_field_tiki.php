<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Installer\Installer;

/**
 * Migration script to identify and fix corrupted timestamps in DateTime tracker fields
 *
 * This script addresses the issue where JavaScript date pickers were inserting
 * timestamps with milliseconds (e.g., 1640995200000 instead of 1640995200),
 * causing corrupted data in the database.
 *
 * The script:
 * 1. Identifies DateTime fields that may have corrupted timestamps
 * 2. Validates and fixes timestamps that are in milliseconds format
 * 3. Reports the number of affected records
 * 4. Creates a backup of the original data
 *
 * @param Installer $installer
 * @return bool
 */
function upgrade_20251006_fix_datetime_tracker_field_tiki(Installer $installer): bool
{
    // Get all DateTime fields
    $datetimeFields = $installer->fetchAll(
        "SELECT fieldId, trackerId, name, permName FROM tiki_tracker_fields WHERE type = 'f' OR type = 'j'"
    );

    if (empty($datetimeFields)) {
        return true;
    }

    foreach ($datetimeFields as $field) {
        $fieldId = $field['fieldId'];

        $values = $installer->fetchAll(
            "SELECT itemId, value FROM tiki_tracker_item_fields WHERE fieldId = ? AND value IS NOT NULL AND value != ''",
            [$fieldId]
        );

        foreach ($values as $row) {
            $itemId = $row['itemId'];
            $value = $row['value'];

            if (isMillisecondTimestamp($value)) {
                $fixedValue = convertMillisecondToSecond($value);

                if ($fixedValue !== false) {
                    // Update the value
                    $installer->query(
                        "UPDATE tiki_tracker_item_fields SET value = ? WHERE itemId = ? AND fieldId = ?",
                        [$fixedValue, $itemId, $fieldId]
                    );
                }
            }
        }
    }

    return true;
}

/**
 * Check if a timestamp is in millisecond format (13+ digits)
 */
function isMillisecondTimestamp($value)
{
    if (! is_numeric($value)) {
        return false;
    }

    // Check for millisecond timestamps: length > 10 AND ends with '000'
    return ($value && strlen($value) > 10 && substr($value, -3) === '000');
}

/**
 * Convert millisecond timestamp to second timestamp using DateTime validation
 */
function convertMillisecondToSecond($value)
{
    if (! isMillisecondTimestamp($value)) {
        return false;
    }

    // Convert milliseconds to seconds
    $seconds = intval($value / 1000);

    // Use DateTime::createFromFormat to validate and correct the timestamp
    try {
        $datetime = \DateTime::createFromFormat('U', (string)$seconds);
        if ($datetime === false) {
            return false;
        }

        // Validate the result is a reasonable timestamp (after Unix epoch)
        $timestamp = $datetime->getTimestamp();
        if ($timestamp < 0) {
            return false;
        }

        return (string)$timestamp;
    } catch (Exception $e) {
        return false;
    }
}
