<?php

namespace Tiki\Tracker;

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Translates raw TrackerConfigHistory diff data into human-readable sentences and labels.
 *
 * @package     Tiki
 * @subpackage  Trackers
 * @since       item-161718
 */
class TrackerConfigHistoryRenderer
{
    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Render a diff array into a list of human-readable change records.
     *
     * Each record:
     *   'key'      => DB column name
     *   'label'    => Human-readable label
     *   'from'     => Formatted old value (string)
     *   'to'       => Formatted new value (string)
     *   'sentence' => Plain-English sentence e.g. "Changed Mandatory from No to Yes"
     *
     * @param array $diff Result of TrackerConfigHistory::getHistory() diff_data
     * @return array
     */
    public static function renderDiff(array $diff): array
    {
        $lines = [];

        foreach ($diff as $key => $change) {
            $label = tra(self::humaniseKey($key));
            $fmt   = self::inferFormat($key, $change['from'] ?? $change['to']);

            $from = self::format($fmt, $change['from']);
            $to   = self::format($fmt, $change['to']);

            // Skip if the visual representation is exactly the same (e.g. both empty, or just whitespace differences)
            if (trim(strip_tags($from)) === trim(strip_tags($to))) {
                continue;
            }

            $lines[] = [
                'key'      => $key,
                'label'    => $label,
                'from'     => $from,
                'to'       => $to,
                'sentence' => tr('Changed %0 from "%1" to "%2"', $label, $from, $to),
            ];
        }

        return $lines;
    }

    /**
     * Render a single history row for display.
     * Returns everything needed for a template to show one audit entry.
     */
    public static function renderRow(array $row): array
    {
        $diffData = $row['diff_data'] ?? [];

        return [
            'historyId'  => $row['historyId'],
            'objectType' => $row['objectType'],
            'objectId'   => $row['objectId'],
            'action'     => $row['action'],
            'user'       => $row['user'],
            'lastModif'  => $row['lastModif'],
            'ip'         => $row['ip'],
            'version'    => $row['version'],
            'changes'    => self::renderDiff($diffData),
            'hasChanges' => ! empty($diffData),
        ];
    }

    /**
     * Batch-render a list of history rows.
     */
    public static function renderRows(array $rows): array
    {
        return array_map([self::class, 'renderRow'], $rows);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private static function inferFormat(string $key, $value): string
    {
        return match ($key) {
            'type'                                                            => 'fieldType',
            'options', 'rules'                                                => 'jsonPretty',
            'defaultStatus', 'newItemStatus', 'modifyItemStatus',
            'modItemStatus'                                                   => 'trackerStatus',
            'duplicateRules'                                                  => 'duplicateRules',
            'encryptionKeyId'                                                 => 'nullable',
            default => ($value === 'y' || $value === 'n') ? 'yesNo' : 'raw',
        };
    }

    private static function format(string $fmt, $value): string
    {
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        if ($value === null || trim((string)$value) === '') {
            return tra('(empty)');
        }

        return match ($fmt) {
            'yesNo'      => ($value === 'y' || $value === 1 || $value === true)
                              ? tra('Yes') : tra('No'),
            'fieldType'  => \Tracker_Field_Factory::getFieldInfo((string) $value)['name'] ?? (string) $value,
            'jsonPretty' => self::prettyJson($value),
            'nullable'   => ($value === null || $value === '') ? tra('None') : (string) $value,
            'duplicateRules' => self::formatDuplicateRules($value),
            'trackerStatus' => match ((string) $value) {
                'o'  => tra('Open'),
                'p'  => tra('Pending'),
                'c'  => tra('Closed'),
                ''   => tra('(any)'),
                default => (string) $value,
            },
            default => (string) $value,
        };
    }

    private static function prettyJson($value): string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
        }
        return (string) $value;
    }

    private static function formatDuplicateRules($value): string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                if (empty($decoded)) {
                    return tra('(none)');
                }
                $lines = [];
                foreach ($decoded as $fieldId => $action) {
                    $lines[] = tra("Field #") . $fieldId . " ➔ " . $action;
                }
                return implode("\n", $lines);
            }
        }
        return (string) $value;
    }

    /**
     * Convert camelCase or snake_case key to a readable label as a fallback.
     */
    private static function humaniseKey(string $key): string
    {
        // camelCase → words
        $spaced = preg_replace('/([A-Z])/', ' $1', $key);
        // snake_case → words
        $spaced = str_replace('_', ' ', $spaced);
        return ucfirst(trim($spaced));
    }
}
