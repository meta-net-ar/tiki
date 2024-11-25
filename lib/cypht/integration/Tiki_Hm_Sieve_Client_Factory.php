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
    public function init($user_config = null, $imap_account = null)
    {
        if (($imap_account && ! empty($imap_account['sieve_config_host'])) && $imap_account['sieve_config_host'] !== 'localhost') {
            list($sieve_host, $sieve_port) = parse_sieve_config_host($imap_account['sieve_config_host']);
            $client = new PhpSieveManager\ManageSieve\Client($sieve_host, $sieve_port);
            $client->connect($imap_account['user'], $imap_account['pass'], @$imap_account['sieve_tls'], "", "PLAIN");
        } else {
            $client = new Tiki_Hm_Sieve_Custom_Client($user_config, $imap_account['name'] ?? '');
        }
        return $client;
    }
}
