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
 * Records before/after diffs whenever a tracker or tracker field configuration is modified.
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
     *
     * @param string $objectType 'tracker'|'trackerfield'
     * @param int    $objectId   trackerId or fieldId
     * @param int    $limit
     * @param int    $offset
     * @return array
     */
    public static function getHistory(string $objectType, int $objectId, int $limit = 50, int $offset = 0): array
    {
        $db = TikiDb::get();
        // Fetch one extra row so we can compute the diff for the last item in the page
        $rows = $db->fetchAll(
            'SELECT * FROM `tiki_tracker_config_histories`
             WHERE `objectType` = ? AND `objectId` = ?
             ORDER BY `lastModif` DESC
             LIMIT ' . ((int) $limit + 1) . ' OFFSET ' . (int) $offset,
            [$objectType, $objectId]
        );

        $result = [];
        for ($i = 0; $i < count($rows); $i++) {
            if ($i == $limit) {
                break; // We only fetched this to compute the diff for the $i-1 element
            }
            $currentRow = $rows[$i];
            $currentData = json_decode($currentRow['data'] ?? '[]', true) ?? [];

            $previousData = null;
            if ($currentRow['version'] > 1) {
                if (isset($rows[$i + 1])) {
                    $previousData = json_decode($rows[$i + 1]['data'] ?? '[]', true) ?? [];
                } else {
                    $prevJson = $db->getOne(
                        'SELECT `data` FROM `tiki_tracker_config_histories` WHERE `objectType` = ? AND `objectId` = ? AND `version` = ?',
                        [$objectType, $objectId, $currentRow['version'] - 1]
                    );
                    if ($prevJson) {
                        $previousData = json_decode($prevJson, true) ?? [];
                    } else {
                        $previousData = [];
                    }
                }
            }

            $diff = self::computeDiff($previousData, $currentData);
            $currentRow['diff_data'] = $diff;
            $result[] = $currentRow;
        }

        return $result;
    }

    /**
     * Count history entries for a given object (for pagination).
     */
    public static function countHistory(string $objectType, int $objectId): int
    {
        $db = TikiDb::get();
        return (int) $db->getOne(
            'SELECT COUNT(*) FROM `tiki_tracker_config_histories` WHERE `objectType` = ? AND `objectId` = ?',
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
            if ((string) $oldVal !== (string) $newVal) {
                $diff[$key] = ['from' => $oldVal, 'to' => $newVal];
            }
        }

        return $diff;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

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
        $keys       = $objectType === 'tracker' ? self::TRACKER_KEYS : self::FIELD_KEYS;
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

        $diff = self::computeDiff($beforeClean, $afterClean);

        // Skip no-op saves: if this is an update with no actual changes, don't log
        if ($action === 'Updated' && empty($diff)) {
            return;
        }

        $ip = TikiLib::lib('tiki')->get_ip_address();
        $db = TikiDb::get();

        $maxVersion = (int) $db->getOne(
            'SELECT MAX(`version`) FROM `tiki_tracker_config_histories` WHERE `objectType` = ? AND `objectId` = ?',
            [$objectType, $objectId]
        );
        $newVersion = $maxVersion > 0 ? $maxVersion + 1 : 1;

        $db->query(
            'INSERT INTO `tiki_tracker_config_histories`
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
                json_encode($afterClean, JSON_UNESCAPED_UNICODE)
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
