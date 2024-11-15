<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Formatter_ValueFormatter
{
    private $valueSet;
    private static $pageTitle = '';
    private static $pageDescription = '';

    public function __construct($valueSet)
    {
        $this->valueSet = $valueSet;
    }

    public function getPlainValues()
    {
        return $this->valueSet;
    }

    public function __call($format, $arguments)
    {
        $name = array_shift($arguments);
        if (! $arguments = array_shift($arguments)) {
            $arguments = [];
        }

        if (isset($arguments['pagetitle']) && $arguments['pagetitle'] !== 'n' && empty(self::$pageTitle)) {
            self::$pageTitle = $this->valueSet[$name];
            TikiLib::lib('smarty')->assign('title', self::$pageTitle);
        }

        if (isset($arguments['pagedescription']) && $arguments['pagedescription'] !== 'n' && empty(self::$pageDescription)) {
            self::$pageDescription = $this->valueSet[$name];
            TikiLib::lib('smarty')->assign('description', self::$pageDescription);
        }

        $value = null;
        if (strstr($name, '.')) {
            $parts = explode('.', $name);
            $value = $this->valueSet;
            while ($part = array_shift($parts)) {
                if (! isset($value[$part])) {
                    break;
                }
                $value = $value[$part];
            }
            if (! empty($parts)) {
                $value = null;
            }
        }

        if (is_null($value) && isset($this->valueSet[$name])) {
            $value = $this->valueSet[$name];
        }

        // let wikiplugin or trackerrender formatters get the actual value if it is not stored in the db/index (e.g. might be calculated, static text, retrieved from a plugin)
        if ($format !== 'wikiplugin' && $format !== 'trackerrender' && is_null($value)) {
            return tr("No value for '%0'", $name);
        }

        $class = 'Search_Formatter_ValueFormatter_' . ucfirst($format);
        if (class_exists($class)) {
            global $prefs;
            $cachelib = TikiLib::lib('cache');
            $cacheName = $format . ':' . $name . ':' . $prefs['language'] . ':' . serialize($this->valueSet[$name]) . ':' . serialize($arguments);
            $cacheType = 'search_valueformatter';

            if (in_array($format, $prefs['unified_cached_formatters']) && $cachelib->isCached($cacheName, $cacheType)) {
                return $cachelib->getCached($cacheName, $cacheType);
            } else {
                $formatter = new $class($arguments);

                $ret = $formatter->render($name, $value, $this->valueSet);
                if (in_array($format, $prefs['unified_cached_formatters']) && $formatter->canCache()) {
                    $cachelib->cacheItem($cacheName, $ret, $cacheType);
                }
                return ($ret);
            }
        } else {
            return tr("Unknown formatting rule '%0' for '%1'", $format, $name);
        }
    }
}
