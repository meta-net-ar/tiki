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
            $value = implode(',', $requestData[$insertId]);
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

        $listedIds = array_values(array_filter(array_map(function ($v) {
            if (is_array($v)) {
                return $v['id'] ?? $v['uuid'] ?? null;
            }
            if (is_object($v)) {
                return $v->id ?? $v->uuid ?? null;
            }
            return null;
        }, (array) $videoList)));

        $extras = array_values(array_diff($videos, $listedIds));

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

        foreach ($videoIds as $id) {
            $params = array_merge($otherParams, ['id' => $id]);
            $out   .= \TikiLib::lib('parser')->invokePlugin('peertube', '', $params);
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
