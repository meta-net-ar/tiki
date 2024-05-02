<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Class Memcachelib
 *
 * Simple central point for configuring and using memcache support.
 *
 * This utility library is not a complete wrapper for PHP memcache functions,
 * and only provides a minimal set currently in use in SUMO.
 */
class Memcachelib
{
    private $memcache = false;
    public $options;
    public $key_prefix;
    private $functional = false;

    /**
     * Memcachelib constructor.
     * @param bool $memcached_servers
     * @param bool $memcached_options
     */
    public function __construct(array $memcached_servers = [], array $memcached_options = [])
    {
        global $prefs, $tikidomainslash;
        $enabledInPrefs = self::isEnabled();

        if ($enabledInPrefs && ! class_exists('Memcached')) {
            trigger_error("Memcached class does not exist", E_USER_WARNING);
            return;
        }

        if ($enabledInPrefs && ! $memcached_servers) {
            if (is_array($prefs['memcache_servers'])) {
                $memcached_servers = $prefs['memcache_servers'];
            } else {
                $memcached_servers = unserialize($prefs['memcache_servers']);
            }
            if (! $memcached_servers) {
                trigger_error("Enabled in prefs and no memcached servers provided", E_USER_WARNING);
                return;
            }
        }

        if (! $memcached_servers) {
            return;
        }

        if (! $memcached_options) {
            $memcached_options = [
            'expiration' => (int) $prefs['memcache_expiration'],
            'key_prefix' => $prefs['memcache_prefix'],
            ];
        }
        $localphp = "db/{$tikidomainslash}local.php";

        if (is_readable($localphp)) {
            // Should be defined by unserializing $prefs['memcache_options']
            // and $prefs['memcache_servers']. Currently happens in
            // /webroot/tiki-setup_base.php
            // preferences are overwritten in local.php (if defined)
            require($localphp);
        }

        $memcached_options['flags'] = 0;

        $this->options  = $memcached_options;
        $this->memcache = new Memcached();

        $this->memcache->setOptions([
            //50ms is already pretty long for a memcache server.  If it's that slow to respond, may as well not use it.
            Memcached::OPT_CONNECT_TIMEOUT => 50,
            Memcached::OPT_SERVER_FAILURE_LIMIT => 1
            ]);
        foreach ($memcached_servers as $server) {
            if ($server['host'] == 'localhost') {
                $server['host'] = '127.0.0.1';
            }

            $this->memcache->addServer(
                $server['host'],
                (int) $server['port'],
                isset($server['weight']) ? (int)$server['weight'] : 1
            );
        }

        //This is just to check connection
        $setSuccessfull = $this->memcache->set("dummykey", "dummyvalue");

        if (! $setSuccessfull) {
            $resultCode = $this->memcache->getResultCode();
            $resultmMsg = $this->memcache->getResultMessage();

            $infoToString = function ($serverInfo): string {
                return "{$serverInfo['host']}:{$serverInfo['port']}";
            };
            $servers = implode(', ', array_map($infoToString, $this->memcache->getServerList()));
            $msg = "Memcache: Set returned {$resultCode}: {$resultmMsg}; Unable to use the memcache servers configured: {$servers}";
            if (! is_callable('xdebug_info')) { //If we were to call this with xdebug enabled, the notice would appear at the top of the screen, and we couldn't login to change the memcache configuration because the headers are already sent.
                trigger_error($msg, E_USER_WARNING);
            }
            return;
        }

        $this->functional = true;
        $this->key_prefix = $this->getOption('key_prefix', '');
    }

    /**
     * Get an option, with default.
     *
     * @param string $name    of the option
     * @param mixed  $default value
     *
     * @return mixed  value of the option, or default.
     */
    public function getOption($name, $default = null)
    {
        return $this->options[$name] ?? $default;
    }

    /**
     * Return whether this thing is usable.
     *
     * @return boolean
     */
    public function isFunctional(): bool
    {
        return $this->functional;
    }

    /** Return if this cache is enabled in preferences (doesn't mean it's functional) */
    public static function isEnabled(): bool
    {
        global $prefs;
        return isset($prefs['memcache_enabled']) && $prefs['memcache_enabled'] == 'y';
    }

    /**
     * Get a key from memcache
     *
     * @param mixed $key     , passed through buildKey() before use
     * @param mixed $default value returned if result from memcache is NULL
     *
     * @return mixed Value from memcache, or the default
     */
    public function get($key, $default = null)
    {
        $key = $this->buildKey($key);
        $val = $this->memcache->get($key);
        return ($val !== null) ? $val : $default;
    }

    /**
     * Get multiple keys from memcache at once.
     *
     * This differs from native memcache get() behavior in that all keys
     * passed in will result in a corresponding value returned.  If the
     * key was not found in the cache, the returned value will be NULL.
     *
     * @param array $keys , each will be passed through buildKey() before use
     *
     * @return array Values, in order of keys passed.
     */
    public function getMulti(array $keys): array
    {
        // Run each key passed in through the buildKey() method.
        $keys_built = [];
        foreach ($keys as $key) {
            $keys_built[] = $this->buildKey($key);
        }

        // Fetch the assoc array of keys/values available in memcache.
        $values_in = $this->memcache->getMulti($keys_built);

        // Construct a list of values corresponding to the keys passed in.
        $values_out = [];
        foreach ($keys_built as $kb) {
            $values_out[] = (isset($values_in[$kb])) ? $values_in[$kb] : null;
        }

        return $values_out;
    }

    /**
     * Set a key in memcache
     *
     * @param mixed $key        , passed through buildKey() before use
     * @param mixed $value
     * @param bool  $flags      Optional memcache flags
     * @param bool  $expiration Optional expiration time
     *
     * @return bool Has the operation succeded
     */
    public function set($key, $value, $flags = false, $expiration = false): bool
    {
        $key = $this->buildKey($key);
        $expiration = $expiration ?? $this->getOption('expiration', 0);

        return $this->memcache && $this->memcache->set($key, $value, $expiration);
    }

    /**
     * Delete a key in memcache
     *
     * @param $key , passed through buildKey() before use
     *
     * @return bool Has the operation succeded
     */
    public function delete($key): bool
    {
        $key = $this->buildKey($key);
        return $this->memcache && $this->memcache->delete($key);
    }

    /**
     * Flush the memcache cache
     */
    public function flush(): bool
    {
        return $this->memcache && $this->memcache->flush();
    }

    /**
     * Build a cache key from a given parameter
     *
     * @param mixed $key a string, or an object to be turned into a key
     * @param bool  $use_md5
     *
     * @return string               the cache key
     */
    public function buildKey($key, $use_md5 = false): string
    {
        if (is_string($key)) {
            return (strpos($key, $this->key_prefix) !== 0) ?
            $this->key_prefix . $key : $key;
        }

        if (is_array($key)) {
            $keys = array_keys($key);
            sort($keys);

            $parts = [];
            foreach ($keys as $name) {
                $val = $key[$name];
                if ($val !== null) {
                    $parts[] = $name . '=' . $val;
                }
            }

            $str_key = join(':', $parts);
            return $this->key_prefix .
            ( $use_md5 ? md5($str_key) : '[' . $str_key . ']' );
        }
    }
}
