<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
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
}
