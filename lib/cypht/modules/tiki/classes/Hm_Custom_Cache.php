<?php
/**
 * Custom cache class. To use this, you must set the ENABLE_CUSTOM_CACHE environment variable to true.
 */
class Hm_Custom_Cache extends Hm_Noop_Cache {

    public function get($key, $default = false)
    {
        return $default;
    }

    public function set($key, $val, $lifetime, $crypt_key)
    {
        return parent::set($key, $val, $lifetime, $crypt_key);
    }

    public function del($key)
    {
        return parent::del($key);
    }
}