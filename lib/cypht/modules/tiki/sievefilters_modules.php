<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Tiki groupmail modules
 * @package modules
 * @subpackage tiki
 */

if (! defined('DEBUG_MODE')) {
    die();
}

/**
 * Cron job setup button
 * @subpackage tiki/output
 */
class Hm_Output_tiki_filters_cron extends Hm_Output_Module
{
    protected function output()
    {
        $factory = get_sieve_client_factory($this->get('site_config'));
        $has_tiki_storage = array_filter($this->get('user_config')->get('imap_servers', []), function ($mailbox) use ($factory) {
            try {
                $client = $factory->init($this->get('user_config'), $mailbox);
                if (method_exists($client, 'isTikiStorage') && $client->isTikiStorage()) {
                    return true;
                }
                return false;
            } catch (Exception $e) {
                Hm_Msgs::add("ERRSieve: {$e->getMessage()}");
                return false;
            }
        });
        if (! $has_tiki_storage) {
            return '';
        }

        $scheduled_jobs = TikiLib::lib('scheduler')->get_scheduler(null, null, ['run_only_once' => 0, 'params' => new TikiDb_Expr('$$ LIKE "%sieve:filters%"', [])]);
        if ($scheduled_jobs) {
            return '';
        }

        return '<div class="settings_subtitle">' . tr('Some of the filters defined here require scheduled job to process. Define a SieveFiltersTask here:')
            . ' <a href="tiki-admin_schedulers.php">' . tr('Scheduler') . '</a>'
            . '</div>';
    }
}

/**
 * Placeholder handler module used by sieve filters command
 * @subpackage tiki/output
 */
class Hm_Handler_tiki_sieve_placeholder extends Hm_Handler_Module
{
    public function process()
    {
        // noop
    }
}

/**
 * Adds a sieve config host if storage is tiki
 * @subpackage tiki/output
 */
class Hm_Handler_tiki_add_sieve_config_host extends Hm_Handler_Module
{
    public function process()
    {
        $factory = get_sieve_client_factory($this->config);
        $imap_servers = $this->user_config->get('imap_servers', []);
        foreach ($imap_servers as $imap_server) {
            if (isset($imap_server['sieve_config_host'])) {
                continue;
            }
            try {
                $client = $factory->init($this->user_config, $imap_server);
                if (method_exists($client, 'isTikiStorage') && $client->isTikiStorage()) {
                    $imap_server['sieve_config_host'] = 'localhost';
                    Hm_IMAP_List::edit($imap_server['id'], $imap_server);
                }
            } catch (Exception $e) {
                // Nothing to do
            }
        }
    }
}


/**
 * @subpackage sievefilters/handler
 */
class Hm_Handler_tiki_sieve_get_mailboxes_script extends Hm_Handler_Module
{
    public $user_config;
    public $request;
    public function process()
    {
        list($success, $form) = $this->process_form(['imap_account']);
        if (! $success) {
            return;
        }
        $mailboxes = [];

        $servers = $this->user_config->get('imap_servers', []);

        $names = array_column($servers, 'name');
        $server_id = array_search($this->request->post['imap_account'], $names);

        if (isset($servers[$server_id]['sieve_config_host'])) {
            // Handled by sieve
            $search_servers = [$server_id => $servers[$server_id]];
        } else {
            // Handled with tiki
            $search_servers = $servers;
        }

        foreach ($search_servers as $imap_server_id => $m) {
            $cache = Hm_IMAP_List::get_cache($this->cache, $imap_server_id);
            $imap = Hm_IMAP_List::connect($imap_server_id, $cache);
            if (imap_authed($imap)) {
                foreach ($imap->get_mailbox_list() as $mailbox) {
                    if ($server_id != $imap_server_id) {
                        $mailboxes[$m['name']][] = 'imap_' . $imap_server_id . '_' . $mailbox['name'];
                    } else {
                        $mailboxes[$m['name']][] = $mailbox['name'];
                    }
                }
            }
        }
        $this->out('mailboxes', json_encode($mailboxes));
    }
}


class Hm_Output_tiki_sieve_get_mailboxes_output extends Hm_Output_Module
{
    public function output()
    {
        $mailboxes = $this->get('mailboxes', '');
        $this->out('mailboxes', $mailboxes);
    }
}
