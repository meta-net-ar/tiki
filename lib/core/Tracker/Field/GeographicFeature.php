<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Handler class for geographic features (points, lines, polygons)
 *
 * Letter key: ~GF~
 *
 */
class Tracker_Field_GeographicFeature extends \Tracker\Field\AbstractField implements \Tracker\Field\SynchronizableInterface, \Tracker\Field\IndexableInterface, \Tracker\Field\ExportableInterface
{
    public static function getManagedTypesInfo(): array
    {
        return [
            'GF' => [
                'name' => tr('Geographic Feature'),
                'description' => tr('Store a geographic feature on a map, allowing paths (LineString) and boundaries (Polygon) to be drawn on a map and saved.'),
                'help' => 'Geographic-Feature-Tracker-Field',
                'prefs' => ['trackerfield_geographicfeature'],
                'tags' => ['advanced'],
                'default' => 'y',
                'params' => [
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = [])
    {
        if (isset($requestData[$this->getInsertId()])) {
            $value = $requestData[$this->getInsertId()];
        } else {
            $value = $this->getValue();
        }

        return [
            'value' => $value,
        ];
    }

    public function renderInput($context = [])
    {
        return tr('Feature cannot be set or modified through this interface.');
    }

    public function renderOutput($context = [])
    {
        return tr('Feature cannot be viewed.');
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

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        $value = $this->getValue();
        $baseKey = $this->getBaseKey();

        return [
            'geo_located' => $typeFactory->identifier('y'),
            'geo_feature' => $typeFactory->identifier($this->getValue()),
            'geo_feature_field' => $typeFactory->identifier($this->getConfiguration('permName')),
            $baseKey => $typeFactory->identifier($value),
        ];
    }

    public function getProvidedFields(): array
    {
        $baseKey = $this->getBaseKey();
        return ['geo_located', 'geo_feature', 'geo_feature_field', $baseKey];
    }

    public function getProvidedFieldTypes(): array
    {
        $baseKey = $this->getBaseKey();
        return [
            'geo_located' => 'identifier',
            'geo_feature' => 'identifier',
            'geo_feature_field' => 'identifier',
            $baseKey => 'identifier',
        ];
    }

    public function getGlobalFields(): array
    {
        return [];
    }

    public function getTabularSchema()
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());

        $permName = $this->getConfiguration('permName');

        $schema->addNew($permName, 'default')
            ->setLabel($this->getConfiguration('name'))
            ->setRenderTransform(
                function ($value) use ($permName) {
                    $json = json_encode(json_decode($value));
                    if ($json === false) {
                        Feedback::error(tr('Geographic Feature field "%0" cannot be JSON decoded', $permName));
                        $json = '';
                    } elseif ($json === 'null') {
                        $json = '';
                    }
                    return $json;
                }
            )
            ->setParseIntoTransform(
                function (&$info, $value) use ($permName) {
                    $json = json_encode(json_decode($value));
                    if ($json === false) {
                        Feedback::error(tr('Geographic Feature field "%0" cannot be JSON decoded', $permName));
                        $json = '';
                    } elseif ($json === 'null') {
                        $json = '';
                    }
                    $info['fields'][$permName] = $json;
                }
            );

        return $schema;
    }
}
