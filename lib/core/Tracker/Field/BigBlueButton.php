<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Handler class for BigBlueButton Recordings
 *
 * Letter key: ~bbb~
 *
 * This field stores BigBlueButton recording URLs (complete URLs only) with cached metadata.
 * It supports both locally configured BBB servers and external BBB URLs.
 */
class Tracker_Field_BigBlueButton extends \Tracker\Field\AbstractItemField implements \Tracker\Field\FilterableInterface
{
    public static function getManagedTypesInfo(): array
    {
        return [
            'bbb' => [
                'name' => tr('BigBlueButton Recordings'),
                'description' => tr('Store and display BigBlueButton recording URLs with access to individual playback formats (slides, chat, audio, etc.). Supports both local BBB servers and external recording URLs.'),
                'help' => 'BigBlueButton-Tracker-Field',
                'prefs' => ['trackerfield_bigbluebutton'],
                'tags' => ['advanced'],
                'default' => 'n',
                'supported_changes' => ['t', 'a', 'u'],
                'params' => [
                    'input_mode' => [
                        'name' => tr('Input Mode'),
                        'description' => tr('How users input recording URLs'),
                        'filter' => 'alpha',
                        'options' => [
                            'local' => tr('Select from local BBB server'),
                            'url' => tr('Manual URL input'),
                            'both' => tr('Both options available'),
                        ],
                        'default' => 'local',
                        'legacy_index' => 0,
                    ],
                    'show_playback_formats' => [
                        'name' => tr('Show Playback Formats'),
                        'description' => tr('Display all available playback formats (slides, chat, notes, podcast, etc.)'),
                        'filter' => 'alpha',
                        'options' => [
                            'n' => tr('No'),
                            'y' => tr('Yes'),
                        ],
                        'default' => 'y',
                        'legacy_index' => 1,
                    ],
                    'show_metadata' => [
                        'name' => tr('Show Metadata'),
                        'description' => tr('Display recording metadata (meeting name, date, duration, participants)'),
                        'filter' => 'alpha',
                        'options' => [
                            'n' => tr('No'),
                            'y' => tr('Yes'),
                        ],
                        'default' => 'y',
                        'legacy_index' => 2,
                    ],
                    'allow_multiple' => [
                        'name' => tr('Allow Multiple URLs'),
                        'description' => tr('Allow storing multiple recording URLs'),
                        'filter' => 'alpha',
                        'options' => [
                            'n' => tr('No'),
                            'y' => tr('Yes'),
                        ],
                        'default' => 'n',
                        'legacy_index' => 3,
                    ],
                    'cache_duration' => [
                        'name' => tr('Cache Duration (seconds)'),
                        'description' => tr('How long to cache recording metadata (0 = always refresh, 3600 = 1 hour)'),
                        'filter' => 'int',
                        'default' => 3600,
                        'legacy_index' => 4,
                    ],
                    'meeting_filter' => [
                        'name' => tr('Meeting Filter (local only)'),
                        'description' => tr('Filter local recordings by specific meeting ID'),
                        'filter' => 'text',
                        'legacy_index' => 5,
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = []): array
    {
        $ins_id = $this->getInsertId();
        $value = '';

        if (isset($requestData[$ins_id])) {
            $value = $requestData[$ins_id];
        } elseif (isset($requestData['ins_' . $ins_id])) {
            $value = $requestData['ins_' . $ins_id];
        } elseif (isset($requestData[$ins_id . '_url'])) {
            // Manual URL input
            $value = $requestData[$ins_id . '_url'];
        } else {
            $value = $this->getValue();
        }

        return [
            'value' => $value,
        ];
    }

    public function renderInput($context = [])
    {
        $smarty = TikiLib::lib('smarty');

        $inputMode = $this->getOption('input_mode', 'local');
        $currentValue = $this->getValue();
        $recordings = [];

        // For local mode, fetch recordings from configured BBB server
        if ($inputMode === 'local' || $inputMode === 'both') {
            $recordings = $this->getLocalRecordings();
        }

        // Parse current value (could be URL or JSON with cached data)
        $currentData = $this->parseStoredValue($currentValue);


        $smarty->assign('allow_multiple', $this->getOption('allow_multiple', 'n'));
        $smarty->assign('show_playback_formats', $this->getOption('show_playback_formats', 'y'));
        $smarty->assign('show_metadata', $this->getOption('show_metadata', 'y'));
        $smarty->assign('meeting_filter', $this->getOption('meeting_filter', ''));
        $smarty->assign('input_mode', $inputMode);
        $smarty->assign('recordings', $recordings);
        $smarty->assign('current_value', $currentValue);
        $smarty->assign('current_data', $currentData);
        $smarty->assign('ins_id', $this->getInsertId());

        return $smarty->fetch('trackerinput/bigbluebutton.tpl');
    }

    public function renderOutput($context = [])
    {
        $smarty = TikiLib::lib('smarty');

        $value = $this->getValue();
        $recordingData = null;

        if (! empty($value)) {
            // Try to get recording data with metadata (from cache or fresh fetch)
            $recordingData = $this->getRecordingData($value);
        }

        $smarty->assign('show_playback_formats', $this->getOption('show_playback_formats', 'y'));
        $smarty->assign('show_metadata', $this->getOption('show_metadata', 'y'));
        $smarty->assign('recording_data', $recordingData);
        $smarty->assign('value', $value);

        return $smarty->fetch('trackeroutput/bigbluebutton.tpl');
    }

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        $value = $this->getValue();
        $baseKey = $this->getBaseKey();

        // Parse stored value to get cached metadata or URL
        $data = $this->parseStoredValue($value);

        $indexableContent = '';

        // If we have cached metadata, use it for indexing (avoid server calls during indexing)
        if (isset($data['metadata'])) {
            $meta = $data['metadata'];
            $indexableContent = implode(' ', [
                $data['url'] ?? '',
                $meta['meetingName'] ?? '',
                $meta['meetingID'] ?? '',
                isset($meta['participants']) && is_array($meta['participants']) ? implode(' ', $meta['participants']) : '',
            ]);
        } else {
            // No cached metadata, just index the URL
            $indexableContent = $data['url'] ?? $value;
        }

        return [
            $baseKey => $typeFactory->plaintext($indexableContent),
            "{$baseKey}_url" => $typeFactory->identifier($data['url'] ?? $value),
        ];
    }

    public function getProvidedFields(): array
    {
        $baseKey = $this->getBaseKey();
        return [$baseKey, "{$baseKey}_url"];
    }

    public function getGlobalFields(): array
    {
        return [];
    }

    public function getTabularSchema(): Tracker\Tabular\Schema
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());
        $permName = $this->getFieldDefinition()['permName'] ?? '';

        $schema->addNew($permName, 'default')
            ->setLabel($this->getFieldDefinition()['name'] ?? '')
            ->setRenderTransform(function ($value) {
                return $value;
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = $value;
            });

        return $schema;
    }

    public function getFilterCollection(): Tracker\Filter\Collection
    {
        $filters = parent::getFilterCollection();
        $permName = $this->getFieldDefinition()['permName'] ?? '';

        $filters->addNew($permName, 'manual')
            ->setLabel($this->getFieldDefinition()['name'] ?? '')
            ->setControl(new Tracker\Filter\Control\TextField("tf_{$permName}"));

        return $filters;
    }

    public function handleSave($value, $oldValue): array
    {
        // If value is a simple URL (not JSON), enhance it with metadata
        if (! empty($value) && ! $this->isJsonData($value)) {
            // Validate that it's a URL
            if (! filter_var($value, FILTER_VALIDATE_URL)) {
                // Not a valid URL, reject
                return [
                    'value' => '',
                ];
            }

            // Try to fetch and cache metadata
            $recordingData = $this->fetchRecordingMetadata($value);
            if ($recordingData) {
                // Store as JSON with cached metadata
                $value = json_encode([
                    'url' => $recordingData['url'] ?? $value,
                    'metadata' => $recordingData['metadata'] ?? [],
                    'cached_at' => gmdate('c'),
                ]);
            }
        }

        return [
            'value' => $value,
        ];
    }

    /**
     * Get recordings from locally configured BBB server
     * @return array Array of recording data
     */
    protected function getLocalRecordings()
    {
        global $prefs;

        if ($prefs['bigbluebutton_feature'] !== 'y') {
            return [];
        }

        // Security check: Verify user has permission to view recordings
        $perms = Perms::get(['type' => 'bigbluebutton', 'object' => 'global']);
        if (! $perms->bigbluebutton_view_rec) {
            return [];
        }

        try {
            $bigbluebuttonlib = TikiLib::lib('bigbluebutton');
            $meetingFilter = $this->getOption('meeting_filter', '');

            $allRecordings = [];
            $recordings = $bigbluebuttonlib->getRecordings($meetingFilter);

            if (is_array($recordings)) {
                foreach ($recordings as $recording) {
                    if ($this->hasAccessToRecording($recording)) {
                        $allRecordings[] = $recording;
                    }
                }
            }

            // Sort by start time (newest first)
            usort($allRecordings, function ($a, $b) {
                return ($b['startTime'] ?? 0) - ($a['startTime'] ?? 0);
            });

            return $allRecordings;
        } catch (Exception $e) {
            error_log('BigBlueButton Tracker Field Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Parse stored value (could be plain URL or JSON with cached metadata)
     * @param string $value Stored value
     * @return array Parsed data with 'url' and optional 'metadata' and 'cached_at'
     */
    protected function parseStoredValue($value)
    {
        if (empty($value)) {
            return ['url' => ''];
        }

        // Try to decode as JSON
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Plain URL value
        return ['url' => $value];
    }

    /**
     * Check if value is JSON data
     * @param string $value
     * @return bool
     */
    protected function isJsonData($value)
    {
        if (empty($value)) {
            return false;
        }
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Get recording data with metadata (from cache or fresh fetch)
     * @param string $value Stored value
     * @return array|null Recording data or null if unavailable
     */
    protected function getRecordingData($value)
    {
        $data = $this->parseStoredValue($value);

        if (empty($data['url'])) {
            return null;
        }

        // Check if we have cached metadata and if it's still valid
        $cacheDuration = (int) $this->getOption('cache_duration', 3600);
        $cacheValid = false;

        if (isset($data['cached_at']) && isset($data['metadata'])) {
            $cachedTime = strtotime($data['cached_at']);
            if ($cacheDuration === 0 || (TikiLib::lib('tiki')->now - $cachedTime) < $cacheDuration) {
                $cacheValid = true;
            }
        }

        // If cache is valid, return cached data
        if ($cacheValid) {
            return $data;
        }

        // Cache invalid or missing, try to fetch fresh metadata
        $freshData = $this->fetchRecordingMetadata($data['url']);
        if ($freshData) {
            return $freshData;
        }

        // If fetch fails but we have cached data, return it anyway (graceful degradation)
        if (isset($data['metadata'])) {
            return $data;
        }

        // No metadata available, return just the URL
        return ['url' => $data['url']];
    }

    /**
     * Fetch recording metadata from BBB URL
     * @param string $url BBB recording URL
     * @return array|null Recording data with metadata or null if unavailable
     */
    protected function fetchRecordingMetadata($url)
    {
        try {
            // Validate URL
            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                return null;
            }

            // Extract recordID from URL
            $recordId = $this->extractRecordIdFromUrl($url);

            // For external URLs, we can't fetch full metadata without server access
            // Return basic structure with extracted information
            return [
                'url' => $url,
                'metadata' => [
                    'recordID' => $recordId,
                    'playback' => $this->extractPlaybackFromUrl($url),
                ],
            ];
        } catch (Exception $e) {
            error_log('BBB Metadata Fetch Error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Extract recordID from BBB URL
     * @param string $url
     * @return string
     */
    protected function extractRecordIdFromUrl($url)
    {
        // BBB URLs typically follow pattern: .../playback/.../[recordID]
        if (preg_match('#/playback/[^/]+/[^/]+/([a-f0-9-]+)#i', $url, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Extract playback formats from BBB URL
     * @param string $url
     * @return array
     */
    protected function extractPlaybackFromUrl($url)
    {
        // Try to detect format from URL path
        $formats = [];

        if (preg_match('#/playback/([^/]+)/#', $url, $matches)) {
            $format = $matches[1];
            $formats[$format] = $url;
        } else {
            $formats['presentation'] = $url;
        }

        return $formats;
    }

    /**
     * Check if user has access to a specific recording
     * @param array $recording
     * @return bool
     */
    private function hasAccessToRecording($recording)
    {
        // Admin users can access all recordings
        if (Perms::get()->admin) {
            return true;
        }

        // Check if user has view_rec permission
        $perms = Perms::get(['type' => 'bigbluebutton', 'object' => 'global']);
        if (! $perms->bigbluebutton_view_rec) {
            return false;
        }

        // Check if recording is published
        if (isset($recording['published']) && $recording['published'] !== 'true') {
            return false;
        }

        return true;
    }
}
