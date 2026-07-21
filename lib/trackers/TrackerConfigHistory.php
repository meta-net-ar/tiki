<?php

namespace Tiki\Lib\Trackers;

use TikiDb;
use TikiLib;

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Tracker Configuration History Service
 *
 * Records configuration changes whenever a tracker or tracker field is modified.
 * This is NOT item data history (that is handled by tiki_tracker_item_field_logs).
 * This specifically audits changes to tiki_trackers and tiki_tracker_fields rows.
 *
 * @package     Tiki
 * @subpackage  Trackers
 * @since       item-161718
 */
class TrackerConfigHistory
{
    /**
     * Columns to snapshot for tiki_tracker_fields rows.
     */
    public const FIELD_KEYS = [
        'name',
        'permName',
        'type',
        'isMain',
        'isSearchable',
        'isTblVisible',
        'isPublic',
        'isHidden',
        'isMandatory',
        'position',
        'options',
        'description',
        'isMultilingual',
        'itemChoices',
        'errorMsg',
        'visibleBy',
        'editableBy',
        'descriptionIsParsed',
        'validation',
        'validationParam',
        'validationMessage',
        'rules',
        'encryptionKeyId',
        'excludeFromNotification',
        'excludeFromTrackerItemLastModificationDate',
        'visibleInViewMode',
        'visibleInEditMode',
        'visibleInHistoryMode',
    ];

    /**
     * Columns to snapshot for tiki_trackers rows.
     * Options from tiki_tracker_options are merged in under their own keys.
     */
    public const TRACKER_KEYS = [
        'name',
        'description',
        'descriptionIsParsed',
    ];

    // -------------------------------------------------------------------------
    // Public helpers for the controller to build the current live state
    // -------------------------------------------------------------------------

    /**
     * Build the normalized state array for a tracker field row.
     */
    public static function extractFieldState(array $field): array
    {
        return self::extractKeys($field, self::FIELD_KEYS);
    }

    /**
     * Build the normalized state array for a tracker row, merging its options.
     */
    public static function extractTrackerState(array $trackerInfo, int $trackerId): array
    {
        $state = self::extractKeys($trackerInfo, self::TRACKER_KEYS);

        $options = TikiLib::lib('trk')->get_tracker_options($trackerId);
        if (is_array($options)) {
            $state = array_merge($state, $options);
        }

        return $state;
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Log a tracker-level configuration change.
     *
     * @param string     $action    'Created'|'Updated'|'Deleted'
     * @param int        $trackerId The tracker ID
     * @param array|null $before    State before save (null for Created)
     * @param array      $after     State after save
     */
    public static function logTrackerChange(string $action, int $trackerId, ?array $before, array $after): void
    {
        self::write('tracker', $trackerId, $trackerId, $action, $before, $after);
    }

    /**
     * Log a tracker field configuration change.
     *
     * @param string     $action    'Created'|'Updated'|'Deleted'
     * @param int        $fieldId   The field ID
     * @param int        $trackerId Parent tracker ID
     * @param array|null $before    State before save (null for Created)
     * @param array      $after     State after save
     */
    public static function logFieldChange(string $action, int $fieldId, int $trackerId, ?array $before, array $after): void
    {
        self::write('trackerfield', $fieldId, $trackerId, $action, $before, $after);
    }

    /**
     * Retrieve config history entries for a given object.
     */
    public static function getHistory(
        string $objectType,
        int $objectId,
        ?array $currentData = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $db = TikiDb::get();

        $allRows = $db->fetchAll(
            'SELECT * FROM `tiki_tracker_configs_history`
             WHERE `objectType` = ? AND `objectId` = ?
             ORDER BY `version` DESC
             LIMIT ' . (int) ($offset + $limit + 1),
            [$objectType, $objectId]
        );

        if (empty($allRows)) {
            return [];
        }

        $rollingState = $currentData ?? [];

        $pageRows = [];
        foreach ($allRows as $i => $row) {
            $delta = json_decode($row['data'] ?? '[]', true) ?? [];

            $stateAfter = $rollingState;

            $diff = [];
            foreach ($delta as $key => $oldValue) {
                $newValue = $stateAfter[$key] ?? null;
                if (self::normalizeValue($oldValue) !== self::normalizeValue($newValue)) {
                    $diff[$key] = ['from' => $oldValue, 'to' => $newValue];
                }
            }

            foreach ($delta as $key => $oldValue) {
                $rollingState[$key] = $oldValue;
            }

            if ($i >= $offset) {
                $row['diff_data'] = $diff;
                $pageRows[] = $row;
            }

            if (count($pageRows) >= $limit) {
                break;
            }
        }

        return $pageRows;
    }

    /**
     * Count history entries for a given object (for pagination).
     */
    public static function countHistory(string $objectType, int $objectId): int
    {
        $db = TikiDb::get();
        return (int) $db->getOne(
            'SELECT COUNT(*) FROM `tiki_tracker_configs_history` WHERE `objectType` = ? AND `objectId` = ?',
            [$objectType, $objectId]
        );
    }

    /**
     * Compute a diff array {key => {from, to}} between two state snapshots.
     * Public so the renderer can also call it on-demand.
     *
     * @param array|null $before
     * @param array      $after
     * @return array
     */
    public static function computeDiff(?array $before, array $after): array
    {
        if ($before === null) {
            return []; // Creation: no before to diff against
        }

        $diff = [];
        $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));

        foreach ($allKeys as $key) {
            $oldVal = $before[$key] ?? null;
            $newVal = $after[$key] ?? null;

            // Normalise for comparison: cast both to string for scalar fields
            if (self::normalizeValue($oldVal) !== self::normalizeValue($newVal)) {
                $diff[$key] = ['from' => $oldVal, 'to' => $newVal];
            }
        }

        return $diff;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Normalize a value to a string for diff comparison.
     * Arrays are JSON-encoded so they compare meaningfully instead of casting to "Array".
     */
    private static function normalizeValue(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?? '';
        }
        return (string) ($value ?? '');
    }

    private static function write(
        string $objectType,
        int $objectId,
        int $trackerId,
        string $action,
        ?array $before,
        array $after
    ): void {
        global $user;

        // Extract only the relevant keys for each object type
        $keys        = $objectType === 'tracker' ? self::TRACKER_KEYS : self::FIELD_KEYS;
        $beforeClean = $before !== null ? self::extractKeys($before, $keys) : null;
        $afterClean  = self::extractKeys($after, $keys);

        // Merge options sub-array into tracker snapshot if present
        if ($objectType === 'tracker') {
            if ($before !== null && isset($before['_options']) && is_array($before['_options'])) {
                $beforeClean = array_merge($beforeClean, $before['_options']);
            }
            if (isset($after['_options']) && is_array($after['_options'])) {
                $afterClean = array_merge($afterClean, $after['_options']);
            }
        }

        $delta = [];
        if ($action === 'Deleted') {
            $delta = $beforeClean ?? [];
        } elseif ($beforeClean !== null) {
            foreach ($afterClean as $key => $newValue) {
                $oldValue = $beforeClean[$key] ?? null;
                if (self::normalizeValue($oldValue) !== self::normalizeValue($newValue)) {
                    $delta[$key] = $oldValue;
                }
            }
            foreach ($beforeClean as $key => $oldValue) {
                if (! array_key_exists($key, $afterClean)) {
                    $delta[$key] = $oldValue;
                }
            }
        }

        if ($action === 'Updated' && empty($delta)) {
            return;
        }

        $ip = TikiLib::lib('tiki')->get_ip_address();
        $db = TikiDb::get();

        $maxVersion = (int) $db->getOne(
            'SELECT MAX(`version`) FROM `tiki_tracker_configs_history` WHERE `objectType` = ? AND `objectId` = ?',
            [$objectType, $objectId]
        );
        $newVersion = $maxVersion > 0 ? $maxVersion + 1 : 1;

        $db->query(
            'INSERT INTO `tiki_tracker_configs_history`
             (`objectType`, `objectId`, `action`, `user`, `lastModif`, `ip`, `version`, `data`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $objectType,
                $objectId,
                $action,
                $user ?? 'anonymous',
                time(),
                $ip,
                $newVersion,
                json_encode($delta, JSON_UNESCAPED_UNICODE)
            ]
        );
    }

    /**
     * Filter an array to only the specified keys.
     */
    private static function extractKeys(array $data, array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $result[$key] = $data[$key];
            }
        }
        return $result;
    }
}
