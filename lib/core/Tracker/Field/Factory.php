<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/** Helper class to instancate Tracker Item Field objects */
class Tracker_Field_Factory
{
    private $trackerDefinition;
    private static $typeMap = [];
    private static $infoMap = [];

    public function __construct($trackerDefinition = null)
    {
        $this->trackerDefinition = $trackerDefinition;
    }

    private static function buildTypeMap()
    {
        if (! self::$typeMap) {
            global $prefs;
            $paths = [
            'lib/core/Tracker/Field' => 'Tracker_Field_',
            ];
            $cacheKey = 'fieldtypes.' . $prefs['language'];

            $cachelib = TikiLib::lib('cache');
            if ($data = $cachelib->getSerialized($cacheKey)) {
                self::$typeMap = $data['typeMap'];
                self::$infoMap = $data['infoMap'];
                return;
            }

            foreach ($paths as $path => $prefix) {
                foreach (glob("$path/[!_]*.php") as $file) {
                    if (
                        $file === "$path/index.php" ||
                        str_contains($file, "Interface") ||
                        str_contains($file, "Abstract") ||
                        str_contains($file, "TrackerField")
                    ) {
                        continue;
                    }
                    $class = $prefix . substr($file, strlen($path) + 1, -4);
                    $reflected = new ReflectionClass($class);

                    if ($reflected->isInstantiable() && $reflected->implementsInterface('\Tracker\Field\ItemFieldInterface')) {
                        $providedFields = call_user_func([$class, 'getManagedTypesInfo']);
                        foreach ($providedFields as $key => $info) {
                            self::$typeMap[$key] = $class;
                            self::$infoMap[$key] = $info;
                        }
                    }
                }
            }

            uasort(self::$infoMap, self::compareName(...));

            $data = [
            'typeMap' => self::$typeMap,
            'infoMap' => self::$infoMap,
            ];

            if (defined('TIKI_PREFS_DEFINED')) {
                $cachelib->cacheItem($cacheKey, serialize($data));
            }
        }
    }

    public static function compareName($a, $b)
    {
        return strcasecmp($a['name'], $b['name']);
    }

    public static function getFieldTypes()
    {
        self::buildTypeMap();
        return self::$infoMap;
    }

    /**
     * Get the raw information array for this field type
     *
     * @param string $type The letter corresponding to the type of field
     * @return array Same data as the getManagedTypesInfo() of the corresponding class for that key
     */
    public static function getFieldInfo(string $type): array
    {
        self::buildTypeMap();
        if (isset(self::$infoMap[$type])) {
            return self::$infoMap[$type];
        } else {
            return [];
        }
    }

    /**
     * Get a list of field types by their letter type and the corresponding class name
     * @Example 'q' => 'Tracker_Field_AutoIncrement', ...
     * @return array letterType => classname
     */
    private static function getTypeMap(): array
    {
        self::buildTypeMap();
        return self::$typeMap;
    }

    public static function getTrackerItemFieldClassFromType(string $type): string
    {
        self::buildTypeMap();
        return self::$typeMap[$type];
    }

    /**
     * Return a concrete class instance to manipulate this field
     *
     * @param array $fieldInfo
     * @param array $itemData
     * @return \Tracker\Field\AbstractItemField or null if the field type isn't enabled in prefs
     */
    public function getHandler(array $fieldInfo, ?array $itemData = null): \Tracker\Field\AbstractItemField|null
    {
        if (empty(self::$typeMap)) {
            self::buildTypeMap();
        }

        if (! isset($fieldInfo['type'])) {
            throw new InvalidArgumentException("fieldInfo parameter is missing 'type' key");
        }
        $type = $fieldInfo['type'];

        if (isset(self::$typeMap[$type])) {
            $info = self::$infoMap[$type];
            $class = self::$typeMap[$type];

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
                //This will return a new class for every invocation, even if it's the same item, leaking memory .  This needs a LRU cache - benoitg - 2024-08-27
                return new $class($fieldInfo, $itemData, $this->trackerDefinition);
            }
        } else {
            Feedback::error(tr('Tracker Field Factory Error: Unknown field type "%0"', $type));
            return null;
        }
    }
}
