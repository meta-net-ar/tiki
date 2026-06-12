<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Initialize sieve client (per imap mailbox) or generic Tiki-stored rules
 */
class Tiki_Hm_Sieve_Client_Factory
{
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
