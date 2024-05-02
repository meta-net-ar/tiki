<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tracker_Field_Factory
{
    private static $trackerFieldLocalCache;

    private $trackerDefinition;
    private $typeMap = [];
    private $infoMap = [];

    public function __construct($trackerDefinition = null)
    {
        $this->trackerDefinition = $trackerDefinition;

        $fieldMap = $this->buildTypeMap(
            [
                'lib/core/Tracker/Field' => 'Tracker_Field_',
            ]
        );
    }

    private function getPreCacheTypeMap()
    {
        if (! empty(self::$trackerFieldLocalCache)) {
            $this->typeMap = self::$trackerFieldLocalCache['type'];
            $this->infoMap = self::$trackerFieldLocalCache['info'];
            return true;
        }

        return false;
    }

    private function setPreCacheTypeMap($data)
    {
        self::$trackerFieldLocalCache = [
            'type' => $data['typeMap'],
            'info' => $data['infoMap']
        ];
    }

    private function buildTypeMap($paths)
    {
        global $prefs;
        $cacheKey = 'fieldtypes.' . $prefs['language'];

        if ($this->getPreCacheTypeMap()) {
            return;
        }

        $cachelib = TikiLib::lib('cache');
        if ($data = $cachelib->getSerialized($cacheKey)) {
            $this->typeMap = $data['typeMap'];
            $this->infoMap = $data['infoMap'];

            $this->setPreCacheTypeMap($data);
            return;
        }

        foreach ($paths as $path => $prefix) {
            foreach (glob("$path/[!_]*.php") as $file) {
                if (
                    $file === "$path/index.php" ||
                    strstr($file, "Interface") ||
                    strstr($file, "Abstract")
                ) {
                    continue;
                }
                $class = $prefix . substr($file, strlen($path) + 1, -4);
                $reflected = new ReflectionClass($class);

                if ($reflected->isInstantiable() && $reflected->implementsInterface('\Tracker\Field\FieldInterface')) {
                    $providedFields = call_user_func([$class, 'getManagedTypesInfo']);
                    foreach ($providedFields as $key => $info) {
                        $this->typeMap[$key] = $class;
                        $this->infoMap[$key] = $info;
                    }
                }
            }
        }

        uasort($this->infoMap, [$this, 'compareName']);

        $data = [
            'typeMap' => $this->typeMap,
            'infoMap' => $this->infoMap,
        ];

        if (defined('TIKI_PREFS_DEFINED')) {
            $cachelib->cacheItem($cacheKey, serialize($data));
            $this->setPreCacheTypeMap($data);
        }
    }

    public function compareName($a, $b)
    {
        return strcasecmp($a['name'], $b['name']);
    }

    public function getFieldTypes()
    {
        return $this->infoMap;
    }

    public function getFieldInfo($type)
    {
        if (isset($this->infoMap[$type])) {
            return $this->infoMap[$type];
        } else {
            return [];
        }
    }

    /**
     * Get a list of field types by their letter type and the corresponding class name
     * @Example 'q' => 'Tracker_Field_AutoIncrement', ...
     * @return array letterType => classname
     */
    public function getTypeMap(): array
    {
        return $this->typeMap;
    }

    /**
     * Return a concrete class instance to manipulate this field
     *
     * @param array $fieldInfo
     * @param array $itemData
     * @return \Tracker\Field\AbstractField or null if the field type isn't enabled in prefs
     */
    public function getHandler(array $fieldInfo, ?array $itemData = null): \Tracker\Field\AbstractField|null
    {
        if (! isset($fieldInfo['type'])) {
            throw new InvalidArgumentException("fieldInfo parameter is missing 'type' key");
        }
        $type = $fieldInfo['type'];

        if (isset($this->typeMap[$type])) {
            $info = $this->infoMap[$type];
            $class = $this->typeMap[$type];

            global $prefs;
            foreach ($info['prefs'] as $pref) {
                if ($prefs[$pref] != 'y') {
                    Feedback::error(tr(
                        'Tracker Field Factory Error: Pref "%0" required for field type "%1"',
                        $pref,
                        $class
                    ));
                    return null;
                }
            }

            $fieldInfo = array_merge($info, $fieldInfo);

            if (class_exists($class) && is_callable([$class, 'build'])) {
                //I couldn't find a case where this code path is followed.  benoitg - 2024-03-12
                return call_user_func([$class, 'build'], $type, $this->trackerDefinition, $fieldInfo, $itemData);
            } else {
                return new $class($fieldInfo, $itemData, $this->trackerDefinition);
            }
        } else {
            throw new Exception("type {$type} is missing in typeMap");
        }
    }
}
