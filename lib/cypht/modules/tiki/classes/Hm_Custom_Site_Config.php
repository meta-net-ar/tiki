<?php
/**
 * Custom site configuration class. To use this, you must set the SITE_CONFIG_TYPE environment variable to 'custom'.
 */
class Hm_Custom_Site_Config extends Hm_Config {
    public function get($name, $default = false)
    {
        return parent::get($name, $default);
    }

    public function set($name, $value)
    {
        return parent::set($name, $value);
    }

    public function load($source, $key)
    {
        throw new \Exception('Not implemented');
    }
}