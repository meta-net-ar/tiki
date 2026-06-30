<?php

namespace Tiki\Lib\Trackers;

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
    /**
     * Human-readable labels and optional value formatters for each field key.
     *
     * Format: 'key' => ['label' => string, 'format' => string (optional)]
     *
     * Formatters: 'yesNo', 'fieldType', 'jsonPretty', 'nullable', 'trackerStatus', 'raw'
     */
    private const FIELD_LABELS = [
        // Field-level keys
        'name'                                       => ['label' => 'Field Name'],
        'permName'                                   => ['label' => 'Permanent Name'],
        'type'                                       => ['label' => 'Field Type',               'format' => 'fieldType'],
        'isMain'                                     => ['label' => 'Is Title Field',           'format' => 'yesNo'],
        'isSearchable'                               => ['label' => 'Searchable',               'format' => 'yesNo'],
        'isTblVisible'                               => ['label' => 'Visible in List',          'format' => 'yesNo'],
        'isPublic'                                   => ['label' => 'Public',                   'format' => 'yesNo'],
        'isHidden'                                   => ['label' => 'Hidden',                   'format' => 'yesNo'],
        'isMandatory'                                => ['label' => 'Mandatory',                'format' => 'yesNo'],
        'position'                                   => ['label' => 'Position'],
        'options'                                    => ['label' => 'Options',                  'format' => 'jsonPretty'],
        'description'                                => ['label' => 'Description'],
        'isMultilingual'                             => ['label' => 'Multilingual',             'format' => 'yesNo'],
        'itemChoices'                                => ['label' => 'Item Choices'],
        'errorMsg'                                   => ['label' => 'Error Message'],
        'visibleBy'                                  => ['label' => 'Visible By'],
        'editableBy'                                 => ['label' => 'Editable By'],
        'descriptionIsParsed'                        => ['label' => 'Description Is Wiki-Parsed', 'format' => 'yesNo'],
        'validation'                                 => ['label' => 'Validation Rule'],
        'validationParam'                            => ['label' => 'Validation Parameter'],
        'validationMessage'                          => ['label' => 'Validation Message'],
        'rules'                                      => ['label' => 'Field Rules',              'format' => 'jsonPretty'],
        'encryptionKeyId'                            => ['label' => 'Encryption Key',           'format' => 'nullable'],
        'excludeFromNotification'                    => ['label' => 'Exclude from Notifications', 'format' => 'yesNo'],
        'excludeFromTrackerItemLastModificationDate' => ['label' => 'Exclude from Last Modification Date', 'format' => 'yesNo'],
        'visibleInViewMode'                          => ['label' => 'Visible in View Mode',     'format' => 'yesNo'],
        'visibleInEditMode'                          => ['label' => 'Visible in Edit Mode',     'format' => 'yesNo'],
        'visibleInHistoryMode'                       => ['label' => 'Visible in History Mode',  'format' => 'yesNo'],
        // Tracker-level keys
        'name_tracker'                               => ['label' => 'Tracker Name'],
        'description_tracker'                        => ['label' => 'Description'],
        // Common tracker option keys (from tiki_tracker_options)
        'defaultStatus'                              => ['label' => 'Default Item Status',      'format' => 'trackerStatus'],
        'newItemStatus'                              => ['label' => 'New Item Status',           'format' => 'trackerStatus'],
        'modifyItemStatus'                           => ['label' => 'Modify Item Status',        'format' => 'trackerStatus'],
        'useComments'                                => ['label' => 'Allow Comments',            'format' => 'yesNo'],
        'showStatus'                                 => ['label' => 'Show Status',               'format' => 'yesNo'],
        'showStatusAdminOnly'                        => ['label' => 'Show Status (Admins Only)', 'format' => 'yesNo'],
        'outboundEmail'                              => ['label' => 'Notification Email'],
        'requireApproval'                            => ['label' => 'Require Approval',          'format' => 'yesNo'],
        'useRatings'                                 => ['label' => 'Use Ratings',               'format' => 'yesNo'],
        'showRatings'                                => ['label' => 'Show Ratings',              'format' => 'yesNo'],
        'writerCanModify'                            => ['label' => 'Item Author Can Modify',    'format' => 'yesNo'],
        'writerCanRemove'                            => ['label' => 'Item Author Can Remove',    'format' => 'yesNo'],
        'userCanSeeOwn'                              => ['label' => 'Users Can See Their Own Items', 'format' => 'yesNo'],
        'tabularSync'                                => ['label' => 'Tabular Sync'],
        'duplicateRules'                             => ['label' => 'Duplicate Rules',           'format' => 'duplicateRules'],
    ];

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
     * @param array $diff   Result of TrackerConfigHistory::computeDiff()
     * @param string $objectType 'tracker'|'trackerfield'
     * @return array
     */
    public static function renderDiff(array $diff, string $objectType = 'trackerfield'): array
    {
        $lines = [];

        foreach ($diff as $key => $change) {
            $meta  = self::FIELD_LABELS[$key] ?? ['label' => self::humaniseKey($key)];
            $label = tra($meta['label']);
            $fmt   = $meta['format'] ?? 'raw';

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
        $diffData    = $row['diff_data'] ?? [];
        $objectType  = $row['objectType'];

        return [
            'historyId'  => $row['historyId'],
            'objectType' => $objectType,
            'objectId'   => $row['objectId'],
            'action'     => $row['action'],
            'user'       => $row['user'],
            'lastModif'  => $row['lastModif'],
            'ip'         => $row['ip'],
            'version'    => $row['version'],
            'changes'    => self::renderDiff($diffData, $objectType),
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

    private static function format(string $fmt, $value): string
    {
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
