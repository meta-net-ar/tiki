<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

require_once APP_PATH.'modules/sievefilters/hm-sieve.php';

/**
 * Custom sieve client factory class. To use this, you must set the ENABLE_CUSTOM_SIEVE_FACTORY environment variable to true.
 */
class Hm_Custom_Sieve_Client_Factory extends Hm_Sieve_Client_Factory {
    public function init($user_config = null, $imap_account = null, $is_nux_supported = false)
    {
        if (($imap_account && ! empty($imap_account['sieve_config_host'])) && $imap_account['sieve_config_host'] !== 'localhost') {
            $factory = new Hm_Sieve_Client_Factory();
            $client = $factory->init($user_config, $imap_account, $is_nux_supported);
        } else {
            $client = new Tiki_Hm_Sieve_Custom_Client($user_config, $imap_account['name'] ?? '');
        }
        return $client;
    }
}

/**
 * Pretent storing sieve filters and scripts without Sieve client.
 * Use Tiki user preferences for storage to make Sieve filters UI
 * work without Sieve backend support.
 */
class Tiki_Hm_Sieve_Custom_Client
{
    protected $user_config;
    protected $imap_account_name;

    public function __construct($user_config, $imap_account_name)
    {
        $this->user_config = $user_config;
        $this->imap_account_name = $imap_account_name;
    }

    public function connect($username, $password, $tls = false, $authz_id = "", $auth_mechanism = null)
    {
        // noop
    }

    public function logout()
    {
        // noop
    }

    public function close()
    {
        // noop
    }

    public function listScripts()
    {
        $scripts = $this->user_config->get('sieve_scripts', []);
        if (isset($scripts[$this->imap_account_name])) {
            return array_keys($scripts[$this->imap_account_name]);
        }
        return [];
    }

    public function getScript($name)
    {
        $scripts = $this->user_config->get('sieve_scripts', []);
        return $scripts[$this->imap_account_name][$name] ?? '';
    }

    public function putScript($name, $content)
    {
        $scripts = $this->user_config->get('sieve_scripts', []);
        $scripts[$this->imap_account_name][$name] = $content;
        $this->user_config->set('sieve_scripts', $scripts);
    }

    public function activateScript($name)
    {
        // noop
    }

    public function getCapabilities()
    {
        return [];
    }

    public function getErrorMessage()
    {
        // noop
    }

    public function removeScripts($name)
    {

        $scripts = $this->user_config->get('sieve_scripts', []);
        unset($scripts[$this->imap_account_name][$name]);
        $this->user_config->set('sieve_scripts', $scripts);
    }

    public function isTikiStorage()
    {
        return true;
    }

    public function getExtensions()
    {
        return [];
    }
}
