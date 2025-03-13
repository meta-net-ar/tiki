<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Package;

use TikiLib;

/**
 * Retrieving information about installed packages is expensive,
 * this class will manage the cache of that information
 */
class PackageInformationCache
{
    protected const TIKI_CACHE_TYPE = 'packages';

    /**
     * @var bool Allows to control globally if cache should be enabled and/or disabled
     */
    protected static bool $cacheEnabled = true;

    /**
     * @var bool used to track if was initialized, this helps with lazy loading.
     */
    protected static bool $cacheInitialized = false;

    /**
     * @var array simple cache in memory
     */
    protected static array $cache = [];

    /**
     * @var bool Tracks if there were any changes to the object (object needs to be flush)
     */
    protected static bool $dirty = false;

    /**
     * Return a value from cache, if not found will return the (optional) default value
     * @param string|array $key
     * @param mixed $defaultValue The default value to return, if key does not exist, by default will be "null"
     *
     * @return mixed
     */
    public function get($key, $defaultValue = null)
    {
        if (! static::$cacheEnabled) {
            return $defaultValue;
        }

        $this->initialize(); // will initialize cache if not done already

        $key = $this->key($key);
        if (array_key_exists($key, static::$cache)) {
            return static::$cache[$key];
        }

        return $defaultValue;
    }

    /**
     * Set a key/value in the cache
     *
     * @param string|array $key
     * @param mixed $value
     *
     * @return void
     */
    public function set($key, $value)
    {
        if (! static::$cacheEnabled) {
            return;
        }

        $this->initialize(); // will initialize cache if not done already

        $key = $this->key($key);
        static::$cache[$key] = $value;

        static::$dirty = true;
    }

    /**
     * This function forces a conversion to string, also converts arrays into strings by concatenating with ":"
     * @param array|string $key The cache key
     *
     * @return string
     */
    public function key($key)
    {
        if (is_array($key)) {
            $key = implode(":", $key);
        }
        return (string)$key;
    }

    public function clear()
    {
        static::$cache = [];
        static::$dirty = true;
    }

    /**
     * Flush a "dirty" cache, basically push the cached values to Tiki Cache Library
     * @return void
     */
    public function flush()
    {
        if (! static::$dirty) {
            return;
        }

        /** @var \Cachelib $cachelib */
        $cachelib = TikiLib::lib('cache');

        $cachelib->cacheItem(
            $this->getTikiCacheKey(),
            json_encode(static::$cache),
            static::TIKI_CACHE_TYPE
        );
    }

    /**
     * While we want a constant cache key, would be good to introduce some entropy, so we use the file path
     * @return void
     */
    protected function getTikiCacheKey()
    {
        return __CLASS__ . ":" . __FILE__;
    }

    /**
     * Enables caching
     * @return void
     */
    public function enableCache()
    {
        static::$cacheEnabled = true;
    }

    /**
     * Disables caching
     * @return void
     */
    public function disableCache()
    {
        static::$cacheEnabled = false;
    }

    /**
     * Initialized the cache object
     * @return void
     */
    protected function initialize()
    {
        if (static::$cacheInitialized) {
            return;
        }

        // register a tiki shutdown function to flush cache the cache if there are changes.
        TikiLib::events()->bind(
            'tiki.process.shutdown',
            function () {
                $this->flush();
            }
        );

        // Track files that may change - use that to invalidate the cache.
        // Cast to int so that if filemtime fails (e.g. file does not exist) we can run max.
        $errorLevel = error_reporting();
        if (! file_exists(TIKI_PATH . DIRECTORY_SEPARATOR . TIKI_VENDOR_BUNDLED_TOPLEVEL_PATH . DIRECTORY_SEPARATOR . ComposerCli::COMPOSER_CONFIG)) {
            \Feedback::error(tra('Composer.json is missing from vendor_bundled.'));
        }
        if (! file_exists(TIKI_PATH . DIRECTORY_SEPARATOR . TIKI_VENDOR_BUNDLED_TOPLEVEL_PATH . DIRECTORY_SEPARATOR . ComposerCli::COMPOSER_LOCK)) {
            \Feedback::error(tra('Composer.lock is missing from vendor_bundled.'));
        }
        error_reporting(E_ALL & ~E_WARNING);
        $configModificationTime = (int)filemtime(TIKI_PATH . DIRECTORY_SEPARATOR . ComposerCli::COMPOSER_CONFIG);
        $lockModificationTime = (int)filemtime(TIKI_PATH . DIRECTORY_SEPARATOR . ComposerCli::COMPOSER_LOCK);
        $configModificationTimeBundled = (int)filemtime(TIKI_PATH . DIRECTORY_SEPARATOR . TIKI_VENDOR_BUNDLED_TOPLEVEL_PATH . DIRECTORY_SEPARATOR . ComposerCli::COMPOSER_CONFIG);
        $lockModificationTimeBundled = (int)filemtime(TIKI_PATH . DIRECTORY_SEPARATOR . TIKI_VENDOR_BUNDLED_TOPLEVEL_PATH . DIRECTORY_SEPARATOR . ComposerCli::COMPOSER_LOCK);
        $packagesModificationTime = (int)filemtime(__DIR__ . DIRECTORY_SEPARATOR . ComposerManager::CONFIG_PACKAGE_FILE);
        $lastModif = max($configModificationTime, $configModificationTimeBundled, $lockModificationTime, $lockModificationTimeBundled, $packagesModificationTime);
        error_reporting($errorLevel);
        /** @var \Cachelib $cachelib */
        $cachelib = TikiLib::lib('cache');

        $cache = $cachelib->getCached(
            $this->getTikiCacheKey(),
            static::TIKI_CACHE_TYPE,
            $lastModif
        );

        if ($cache) {
            static::$cache = json_decode($cache, true);
        } else {
            static::$cache = [];
        }

        static::$dirty = false;
        static::$cacheInitialized = true;
    }
}
