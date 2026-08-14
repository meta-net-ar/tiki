<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Handler class for kaltura video integration
 *
 * Letter key: ~kaltura~
 *
 */
class Tracker_Field_Kaltura extends \Tracker\Field\AbstractItemField implements \Tracker\Field\SynchronizableInterface
{
    public static function getManagedTypesInfo(): array
    {
        return [
            'kaltura' => [
                'name' => tr('Kaltura Video'),
                'description' => tr('Display a series of attached Kaltura videos.'),
                'help' => 'Kaltura',
                'prefs' => ['trackerfield_kaltura', 'feature_kaltura', 'wikiplugin_kaltura'],
                'tags' => ['advanced'],
                'default' => 'n',
                'params' => [
                    'displayParams' => [
                        'name' => tr('Display parameters'),
                        'description' => tr('URL-encoded parameters used in the {kaltura} plugin, for example,.') . ' "width=800&height=600"',
                        'filter' => 'text',
                    ],
                    'displayParamsForLists' => [
                        'name' => tr('Display parameters for lists'),
                        'description' => tr('URL-encoded parameters used in the {kaltura} plugin, for example,.') . ' "width=240&height=80"',
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

            $flattened = [];
            array_walk_recursive($values, function ($v) use (&$flattened) {
                if (is_string($v) || is_numeric($v)) {
                    $flattened[] = trim((string)$v);
                }
            });

            $value = implode(',', array_unique(array_filter($flattened)));
        } elseif (! empty($requestData['old_' . $insertId])) {    // all entries removed
            $value = '';
        } else {
            $value = $this->getValue();
        }

        return [
            'value' => $value,
        ];
    }

    public function renderInput($context = [])
    {
        $kalturalib = TikiLib::lib('kalturauser');
        $movies = array_filter(explode(',', $this->getValue()));

        $movieList = $kalturalib->getMovieList($movies);
        $extra = array_diff(
            $movies,
            array_map(
                function ($movie) {
                    return $movie['id'];
                },
                $movieList
            )
        );
        return $this->renderTemplate(
            'trackerinput/kaltura.tpl',
            $context,
            [
                'movies' => $movieList,
                'extras' => $extra,
            ]
        );
    }

    public function renderOutput($context = [])
    {
        $isListMode = ($context['list_mode'] ?? 'n') === 'y';

        if ($isListMode) {
            $otherParams = $this->getOption('displayParamsForLists', []);
        } else {
            $otherParams = $this->getOption('displayParams', []);
        }

        if ($otherParams) {
            parse_str($otherParams, $otherParams);
        } else {
            $otherParams = [];
        }

        // Set default smaller dimensions for list mode if not specified
        if ($isListMode && empty($otherParams['width'])) {
            $otherParams['width'] = 200;
        }
        if ($isListMode && empty($otherParams['height'])) {
            $otherParams['height'] = 120;
        }

        include_once 'lib/wiki-plugins/wikiplugin_kaltura.php';

        $movieIds = array_filter(explode(',', $this->getValue()));
        $output = '';

        if (empty($movieIds)) {
            return $output;
        }

        foreach ($movieIds as $id) {
            $params = array_merge($otherParams, ['id' => $id]);
            $output .= '<div class="kaltura-video-item mb-3">';
            $output .= TikiLib::lib('parser')->invokePlugin('kaltura', '', $params);
            $output .= '</div>';
        }

        return $output;
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
