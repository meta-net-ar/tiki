<?php

namespace Tracker\Field;

use Tracker\Field\AbstractItemField;
use Tracker\Field\SynchronizableInterface;

class TrackerFieldPeerTube extends AbstractItemField implements SynchronizableInterface
{
    public static function getManagedTypesInfo(): array
    {
        return [
            'peertube' => [
                'name' => tr('PeerTube Video'),
                'description' => tr('Display a series of attached PeerTube videos.'),
                'help' => 'PeerTube',
                'prefs' => ['trackerfield_peertube', 'feature_peertube', 'wikiplugin_peertube'],
                'tags' => ['advanced'],
                'default' => 'n',
                'params' => [
                    'displayParams' => [
                        'name' => tr('Display parameters'),
                        'description' => tr('URL-encoded parameters for the {peertube} plugin, e.g., width=800&height=600'),
                        'filter' => 'text',
                    ],
                    'displayParamsForLists' => [
                        'name' => tr('Display parameters for lists'),
                        'description' => tr('URL-encoded parameters for the {peertube} plugin, e.g., width=240&height=80'),
                        'filter' => 'text',
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = []): array
    {
        $insertId = $this->getInsertId();

        if (isset($requestData[$insertId])) {
            // Ensure we have an array (single checkbox might be a string)
            $values = is_array($requestData[$insertId])
                ? $requestData[$insertId]
                : [$requestData[$insertId]];
            $value = implode(',', array_unique(array_filter($values)));
        } elseif (! empty($requestData['old_' . $insertId])) { // all entries removed
            $value = '';
        } else {
            $value = (string) $this->getValue();
        }

        return ['value' => $value];
    }

    public function renderInput($context = [])
    {
        $peertubelib = \TikiLib::lib('peertubeuser');
        if (! $peertubelib) {
            return tr('PeerTube library not available.');
        }

        $value  = (string) $this->getValue();
        $videos = array_values(array_filter(array_map('trim', explode(',', $value))));

        try {
            $videoList = $peertubelib->getVideoList($videos);
        } catch (\Exception $e) {
            $videoList = [];
        }

        // Collect all possible identifiers for the returned videos to avoid false positives in extras
        $knownIds = [];
        foreach ((array) $videoList as $v) {
            if (is_array($v)) {
                if (isset($v['id'])) {
                    $knownIds[] = $v['id'];
                }
                if (isset($v['uuid'])) {
                    $knownIds[] = $v['uuid'];
                }
                if (isset($v['shortUUID'])) {
                    $knownIds[] = $v['shortUUID'];
                }
            } elseif (is_object($v)) {
                if (isset($v->id)) {
                    $knownIds[] = $v->id;
                }
                if (isset($v->uuid)) {
                    $knownIds[] = $v->uuid;
                }
                if (isset($v->shortUUID)) {
                    $knownIds[] = $v->shortUUID;
                }
            }
        }

        $extras = array_values(array_diff($videos, $knownIds));

        return $this->renderTemplate(
            'trackerinput/peertube.tpl',
            $context,
            [
                'videos' => $videoList,
                'extras' => $extras,
            ]
        );
    }

    public function renderOutput($context = [])
    {
        $otherParams = ($context['list_mode'] ?? 'n') === 'y'
            ? $this->getOption('displayParamsForLists', [])
            : $this->getOption('displayParams', []);

        if ($otherParams) {
            parse_str($otherParams, $otherParams);
        } else {
            $otherParams = [];
        }

        include_once 'lib/wiki-plugins/wikiplugin_peertube.php';

        $videoIds = array_filter(array_map('trim', explode(',', (string) $this->getValue())));
        $out = '';

        if (empty($videoIds)) {
            return $out;
        }

        global $prefs;
        $base = rtrim($prefs['peertube_service_url'] ?? '', '/');

        if (empty($base)) {
            return $out;
        }

        foreach ($videoIds as $id) {
            // Convert video ID/UUID to PeerTube URL format: base/w/uuid
            $videoUrl = $base . '/w/' . $id;
            $params = array_merge($otherParams, ['url' => $videoUrl]);
            $out .= '<div class="peertube-video-item mb-3">';
            $out .= \TikiLib::lib('parser')->invokePlugin('peertube', '', $params);
            $out .= '</div>';
        }

        return $out;
    }

    public function importRemote($value)
    {
        return $value;
    }
    public function exportRemote($value)
    {
        return $value;
    }
    public function importRemoteField(array $info, array $syncInfo)
    {
        return $info;
    }
}

class_alias(TrackerFieldPeerTube::class, 'Tracker_Field_PeerTube');
