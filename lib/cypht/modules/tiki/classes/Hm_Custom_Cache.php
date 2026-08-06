<?php

/**
 * Custom cache class. To use this, you must set the ENABLE_CUSTOM_CACHE environment variable to true.
 */
class Hm_Custom_Cache extends Hm_Noop_Cache
{
    private $cachelib;

    public function __construct()
    {
        $this->cachelib = TikiLib::lib('cache');
    }

    /**
     * @param string $key name of value to cache
     * @param mixed $val value to cache
     * @param integer $lifetime how long to cache (if applicable for the backend)
     * @param boolean $crypt_key
     * @return boolean
     */
    public function set($key, $val, $lifetime, $crypt_key)
    {
        return $this->cachelib->cacheItem($key, Hm_transform::stringify($val));
    }

    /**
     * @param string $key name of value to fetch
     * @param mixed $default value to return if not found
     * @param boolean $session fetch from the session instead of the enabled cache
     * @return mixed
     */
    public function get($key, $default = false)
    {
        $data = $this->cachelib->getCached($key);
        return $data ? Hm_transform::unstringify($data) : $default;
    }

    /**
     * @param string $key name to delete
     * @param boolean $session fetch from the session instead of the enabled cache
     * @return boolean
     */
    public function del($key)
    {
        return $this->cachelib->invalidate($key);
    }
}
