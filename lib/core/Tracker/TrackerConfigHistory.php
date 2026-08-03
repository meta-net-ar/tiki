<?php

namespace Tiki\Tracker;

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
     * System/identity columns excluded from snapshots per object type.
     * Every other column returned by trackerlib is treated as configurable.
     */
    private const EXCLUDED_KEYS = [
        // '_options': transport key used to carry the options array into log(); never a real tracked key.
        // 'options': legacy tiki_tracker_options row that old code stored as a single JSON blob.
        'tracker'      => ['trackerId', 'items', 'created', 'lastModif', '_options', 'options'],
        'trackerfield' => ['fieldId', 'trackerId'],
    ];

    private string $objectType;
    private int $objectId;
    private int $trackerId;

    public function __construct(string $objectType, int $objectId, int $trackerId)
    {
        $this->objectType = $objectType;
        $this->objectId   = $objectId;
        $this->trackerId  = $trackerId;
    }

    // -------------------------------------------------------------------------
    // Named constructors
    // -------------------------------------------------------------------------

    public static function forTracker(int $trackerId): self
    {
        return new self('tracker', $trackerId, $trackerId);
    }

    public static function forField(int $fieldId, int $trackerId): self
    {
        return new self('trackerfield', $fieldId, $trackerId);
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Build the current live state used as anchor for history replay.
     * For tracker type, tracker options are merged in automatically.
     */
    public function extractCurrentState(array $data): array
    {
        $state = $this->filterState($data);

        if ($this->objectType === 'tracker') {
            $options = TikiLib::lib('trk')->get_tracker_options($this->trackerId);
            if (is_array($options)) {
                $state = $this->filterState(array_merge($state, $options));
            }
        }

        return $state;
    }

    /**
     * Log a configuration change.
     *
     * @param string     $action   'Updated'|'Deleted'
     * @param array|null $oldState State before the change (null skips write)
     * @param array      $newState State after the change
     */
    public function log(string $action, ?array $oldState, array $newState): void
    {
        global $user;

        if ($oldState === null) {
            return;
        }

        $cleanOldState = $this->filterState($oldState);
        $cleanNewState = $this->filterState($newState);

        // Tracker options are passed via '_options' key by the caller.
        // Re-apply filterState after merge so EXCLUDED_KEYS also strips any legacy option keys
        // (e.g. a 'options' blob row that old Tiki code stored in tiki_tracker_options).
        if ($this->objectType === 'tracker') {
            if (isset($oldState['_options'])) {
                $cleanOldState = $this->filterState(array_merge($cleanOldState, $oldState['_options']));
            }
            if (isset($newState['_options'])) {
                $cleanNewState = $this->filterState(array_merge($cleanNewState, $newState['_options']));
            }
        }

        $delta = [];
        if ($action === 'Deleted') {
            $delta = $cleanOldState;
        } else {
            // Updated: build reverse-delta (store old value for every changed key)
            foreach ($cleanNewState as $key => $newValue) {
                $oldValue = $cleanOldState[$key] ?? null;
                if (self::normalizeValue($oldValue) !== self::normalizeValue($newValue)) {
                    $delta[$key] = $oldValue;
                }
            }
            foreach ($cleanOldState as $key => $oldValue) {
                if (! array_key_exists($key, $cleanNewState)) {
                    $delta[$key] = $oldValue;
                }
            }
            if (empty($delta)) {
                return;
            }
        }

        $ip = TikiLib::lib('tiki')->get_ip_address();
        $db = TikiDb::get();

        $maxVersion = (int) $db->getOne(
            'SELECT MAX(`version`) FROM `tiki_tracker_configs_history` WHERE `objectType` = ? AND `objectId` = ?',
            [$this->objectType, $this->objectId]
        );
        $newVersion = $maxVersion > 0 ? $maxVersion + 1 : 1;

        $db->query(
            'INSERT INTO `tiki_tracker_configs_history`
             (`objectType`, `objectId`, `action`, `user`, `lastModif`, `ip`, `version`, `data`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $this->objectType,
                $this->objectId,
                $action,
                $user ?: 'anonymous',
                time(),
                $ip,
                $newVersion,
                json_encode($delta, JSON_UNESCAPED_UNICODE)
            ]
        );
    }

    /**
     * Retrieve config history entries with reverse-delta replay applied.
     *
     * @param array|null $currentData Current live state as anchor for replay
     * @param int        $limit       Max entries per page
     * @param int        $offset      Pagination offset
     */
    public function getHistory(?array $currentData = null, int $limit = 50, int $offset = 0): array
    {
        $db = TikiDb::get();

        $allRows = $db->fetchAll(
            'SELECT * FROM `tiki_tracker_configs_history`
             WHERE `objectType` = ? AND `objectId` = ?
             ORDER BY `version` DESC
             LIMIT ' . (int) ($offset + $limit + 1),
            [$this->objectType, $this->objectId]
        );

        if (empty($allRows)) {
            return [];
        }

        $rollingState = $currentData ?? [];
        $pageRows     = [];

        foreach ($allRows as $i => $row) {
            $delta      = json_decode($row['data'] ?? '[]', true) ?? [];
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
                $pageRows[]       = $row;
            }

            if (count($pageRows) >= $limit) {
                break;
            }
        }

        return $pageRows;
    }

    /**
     * Count history entries for pagination.
     */
    public function count(): int
    {
        $db = TikiDb::get();
        return (int) $db->getOne(
            'SELECT COUNT(*) FROM `tiki_tracker_configs_history` WHERE `objectType` = ? AND `objectId` = ?',
            [$this->objectType, $this->objectId]
        );
    }

    // Private helpers

    private function filterState(array $data): array
    {
        $excluded = self::EXCLUDED_KEYS[$this->objectType] ?? [];
        return array_diff_key($data, array_flip($excluded));
    }

    private static function normalizeValue(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?? '';
        }
        return (string) ($value ?? '');
    }
}
