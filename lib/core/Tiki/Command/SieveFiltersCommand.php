<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use rambomst\PHPBounceHandler\BounceHandler;
use Exception;
use TikiLib;
use Tiki_Hm_Site_Config_File;
use Hm_Session_Setup;
use Hm_Cache;
use Hm_Module_Exec;
use Hm_Request;
use Hm_IMAP_List;
use Hm_SMTP_List;
use Hm_Msgs;
use Hm_Handler_tiki_sieve_placeholder;
use Tiki_Hm_Functions;

/**
 * Runs periodically to execute defined Cypht/Sieve filters and block list
 * for mail servers not having access to Sieve backend. Also execute commands
 * not available in Sieve (e.g. move an email to trackers).
 *
 * @package Tiki\Command
 */

class SieveFiltersCommand extends Command
{
    protected static $defaultDescription = 'Execute defined Sieve filters in Cypht';
    protected function configure()
    {
        $this
            ->setName('sieve:filters')
            ->setHelp(
                'Run periodically to execute defined filters and block list in Cypht for mailboxes without access to Sieve backend.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $user, $tikipath, $tikiroot;

        $_SESSION['cypht'] = [];
        $_SESSION['cypht']['preference_name'] = 'cypht_user_config';
        require_once $tikipath . '/lib/cypht/integration/classes.php';
        require_once APP_PATH . 'modules/site/lib.php';

        $site_config = new Tiki_Hm_Site_Config_File();
        $session_config = new Hm_Session_Setup($site_config);
        $cypht_session = $session_config->setup_session();
        $cypht_cache = new Hm_Cache($site_config, $cypht_session);
        $module_exec = new Hm_Module_Exec($site_config);
        $module_exec->request = new Hm_Request($site_config->get('input_filters', []), $site_config);
        $module_exec->cache = $cypht_cache;
        $mods = $site_config->get_modules();
        $module_exec->load_module_set_files($mods, ['core', 'imap', 'smtp', 'profiles', 'tiki']);
        $hmod = new Hm_Handler_tiki_sieve_placeholder($module_exec, 'system', [], []);

        $userPreferences = TikiLib::lib('tiki')->table('tiki_user_preferences');
        $configs = $userPreferences->fetchAll([], [
            'prefName' => $userPreferences->like('cypht_user_config%'),
        ]);
        foreach ($configs as $user_config) {
            $config = json_decode($user_config['value'], true);

            if (! isset($config['sieve_scripts'])) {
                continue;
            }

            // TODO: consider JMAP mailboxes and others?
            foreach ($config['imap_servers'] as $idx => $mailbox) {
                if (! isset($config['sieve_scripts'][$mailbox['name']])) {
                    continue;
                }

                $output->writeln(tr('Checking account %0', $mailbox['name']));

                Hm_IMAP_List::add($mailbox, $idx);

                $smtp_servers = $config['smtp_servers'] ?? [];
                $smtp_index = 0;
                foreach ($smtp_servers as $server) {
                    Hm_SMTP_List::add($server, $smtp_index);
                    $smtp_index++;
                }

                $filters = [];
                $blocked = [];
                $last_timestamp = strtotime('1 hour ago');
                foreach ($config['sieve_scripts'][$mailbox['name']] as $name => $script) {
                    if (strstr($name, 'cyphtfilter')) {
                        $base64_obj = str_replace("# ", "", preg_split('#\r?\n#', $script, 0)[1]);
                        $conditions = json_decode(base64_decode($base64_obj), true);
                        $base64_obj = str_replace("# ", "", preg_split('#\r?\n#', $script, 0)[2]);
                        $actions = json_decode(base64_decode($base64_obj), true);
                        $operator = strstr($script, 'allof') ? 'ALLOF' : 'ANYOF';
                        $filters[] = compact('conditions', 'actions', 'operator');
                    } elseif (strstr($name, 'cypht')) {
                        // TODO: handle sieve scripts - we need parsing and interpretion functionality
                    } elseif ($name == 'blocked_senders' && ! empty($script)) {
                        $base64_obj = str_replace("# ", "", preg_split('#\r?\n#', $script, 0)[1]);
                        $blocked_list = json_decode(base64_decode($base64_obj));
                        $blocked = array_merge($blocked, $blocked_list);
                    } elseif ($name == 'tiki_sieve_last_timestamp') {
                        $last_timestamp = $script;
                    }
                }

                if (empty($filters) && empty($blocked)) {
                    continue;
                }

                $config['sieve_scripts'][$mailbox['name']]['tiki_sieve_last_timestamp'] = time();
                $userPreferences->update(['value' => json_encode($config)], ['user' => $user_config['user'], 'prefName' => $user_config['prefName']]);
                $default_block_behaviour = $config['sieve_block_default_behaviour'][$idx] ?? 'Discard';

                // 1. get INBOX mails received since $last_timestamp

                $since = date('j-M-Y', $last_timestamp);
                list($status, $msg_list) = merge_imap_search_results([$idx], 'ALL', $cypht_session, $cypht_cache, ['INBOX'], 1000, [['SINCE', $since]]);
                list($status, $tikiRulesMsgList) = merge_imap_search_results([$idx], 'ALL', $cypht_session, $cypht_cache, ['Tiki-Sieve-Rules-To-Be-Applied'], 1000, [['SINCE', $since]]);
                $msg_list = array_merge($msg_list, $tikiRulesMsgList);

                // Connection failed
                if (! is_array($msg_list)) {
                    continue;
                }

                $cache = Hm_IMAP_List::get_cache($cypht_cache, $idx);
                $imap = Hm_IMAP_List::connect($idx, $cache);

                foreach ($msg_list as $key => $msg) {
                    if (strtotime($msg['date']) < $last_timestamp) {
                        //continue;
                    }

                    // 2. check if sender is on blocked list: discard (remove mail) or reject (remove mail and respond with a message)
                    $addr_list = process_address_fld($msg['from']);
                    foreach ($addr_list as $addr) {
                        if (in_array($addr['email'], $blocked)) {
                            if ($default_block_behaviour == 'Reject') {
                                $body = "Email rejected by user configuration:\n\n";
                                $this->action_reject($imap, $msg, $body, $addr['email'], $config, $hmod, $output);
                            }
                            $this->action_discard($imap, $msg, $output);
                            // skip filtering current email as it is on the blocked list
                            continue 2;
                        }
                    }

                    // 3. check filters and if matching, apply actions
                    foreach ($filters as $filter) {
                        list('pass' => $pass, 'bouncelist' => $bouncelist, 'is_tracker_reply' => $is_tracker_reply) = Tiki_Hm_Functions::processFilter($filter, $imap, $msg);
                        if ($pass) {
                            foreach ($filter['actions'] as $action) {
                                if ($action['action'] == 'keep') {
                                    $output->writeln(tr('Kept msg uid %0', $msg['uid']));
                                    continue;
                                } elseif ($action['action'] == 'stop') {
                                    $output->writeln(tr('Filtering stopped for msg uid %0', $msg['uid']));
                                    continue 2;
                                } elseif ($action['action'] == 'copy' || $action['action'] == 'move') {
                                    if ($action['action'] == 'copy') {
                                        $output->writeln(tr('Copied msg uid %0 to %1', $msg['uid'], $action['value']));
                                    } else {
                                        $output->writeln(tr('Moved msg uid %0 to %1', $msg['uid'], $action['value']));
                                    }
                                    if (preg_match('/^imap_(\d+)_(.+)/', $action['value'], $matches)) {
                                        imap_move_different_server([$idx => [$msg['folder'] => [$msg['uid']]]], $action['action'], [1 => $matches[1], 2 => bin2hex($matches[2])], $cypht_cache);
                                    } else {
                                        imap_move_same_server([$idx => [$msg['folder'] => [$msg['uid']]]], $action['action'], $cypht_cache, [2 => bin2hex($action['value'])]);
                                    }
                                } elseif ($action['action'] == 'flag' || $action['action'] == 'addflag') {
                                    $msg_action = $flag = false;
                                    switch ($action['value']) {
                                        case 'Seen':
                                            $msg_action = 'READ';
                                            break;
                                        case 'Answered':
                                            $msg_action = 'ANSWERED';
                                            break;
                                        case 'Flagged':
                                            $msg_action = 'FLAG';
                                            break;
                                        case 'Deleted':
                                            $msg_action = 'DELETE';
                                            break;
                                        case 'Draft':
                                        case 'Recent':
                                            $msg_action = 'CUSTOM';
                                            $flag = $action['value'];
                                            break;
                                        default:
                                            // not implemented
                                    }
                                    if ($msg_action) {
                                        $output->writeln(tr('Applied message action %0 to msg uid %1', $msg_action, $msg['uid']));
                                        $imap->message_action($msg_action, [$msg['uid']], false, $flag);
                                    }
                                } elseif ($action['action'] == 'removeflag') {
                                    switch ($action['value']) {
                                        case 'Seen':
                                            $msg_action = 'UNREAD';
                                            break;
                                        case 'Answered':
                                            // not supported by Cypht
                                            $msg_action = false;
                                            break;
                                        case 'Flagged':
                                            $msg_action = 'UNFLAG';
                                            break;
                                        case 'Deleted':
                                            $msg_action = 'UNDELETE';
                                            break;
                                        case 'Draft':
                                        case 'Recent':
                                            // not supported by Cypht
                                            $msg_action = false;
                                            break;
                                        default:
                                            // not implemented
                                            $msg_action = false;
                                    }
                                    if ($msg_action) {
                                        $output->writeln(tr('Removed message action %0 from msg uid %1', $msg_action, $msg['uid']));
                                        $imap->message_action($msg_action, [$msg['uid']]);
                                    }
                                } elseif ($action['action'] == 'redirect') {
                                    // TODO: get IMAP bodystructure, get each part and build a mime msg string to send
                                    // reuse tiki_send_email_through_cypht and then delete the original message
                                    $output->writeln(tr('Redirected msg uid %0 to %1', $msg['uid'], $action['value']));
                                } elseif ($action['action'] == 'reject') {
                                    $body = "Email rejected by user configuration. Reason: " . $action['value'] . "\n\n";
                                    $this->action_reject($imap, $msg, $body, '', $config, $hmod, $output);
                                    $this->action_discard($imap, $msg, $output);
                                } elseif ($action['action'] == 'discard') {
                                    $this->action_discard($imap, $msg, $output);
                                } elseif ($action['action'] == 'autoreply') {
                                    $this->action_reply($imap, $msg, $action['extra_option_value'], $action['value'], $config, $hmod, $output);
                                } elseif ($action['action'] == 'bounce') {
                                    foreach ($bouncelist as $bounce) {
                                        try {
                                            TikiLib::lib('tsbounces')->insert($bounce['mailbox'], $bounce['headers'], $bounce['msg'], $bounce['type']);
                                            $output->writeln(tr("Added recipient %0 for msg %1 to bounce list as %2 bounce.", $bounce['mailbox'], $msg['uid'], $bounce['type']));
                                        } catch (Exception $e) {
                                            $output->writeln(tr("Error adding bounce for msg %0, recipient %1: %2", $msg['uid'], $bounce['mailbox'], $e->getMessage()));
                                        }
                                    }
                                } elseif ($action['action'] == 'movetooriginatingtrackerinbox') {
                                    if ($is_tracker_reply) {
                                        $this->action_move_to_tracker($imap, $msg, $output, $item[0]);
                                    }
                                } elseif (in_array($action['action'], ['copytotracker', 'movetotracker'])) {
                                    $action_name = $action['action'] == 'copytotracker' ? 'copy' : 'move';
                                    $res = (array) json_decode(str_replace("'", '"', $action['value']));
                                    $this->action_move_to_tracker($imap, $msg, $output, $res, $action_name);
                                }
                            }
                        }
                    }
                }

                Hm_IMAP_List::del($idx);
                while ($smtp_index >= 0) {
                    Hm_SMTP_List::del(--$smtp_index);
                }
            }
        }
        return Command::SUCCESS;
    }

    protected function action_reject($imap, $msg, $body, $to, $config, $hmod, $output)
    {
        // quote original plain text
        $msg_text = $imap->get_message_content($msg['uid'], 0);
        $body .= implode("\n", array_map(function ($line) {
            return "> " . $line;
        }, explode("\n", $msg_text)));

        $msg_headers = $imap->get_message_headers($msg['uid']);
        $profiles = $config['profiles'];
        $recip = get_primary_recipient($profiles, $msg_headers, Hm_SMTP_List::dump(), []);
        $in_reply_to = reply_to_id($msg_headers, 'reply');

        if (! $to) {
            $to = $recip;
        }

        $result = tiki_send_email_through_cypht($to, '', 'Re: ' . $msg['subject'], $body, $in_reply_to, null, $profiles, $hmod, $recip);
        if ($result) {
            $output->writeln(tr('Rejected msg uid %0 with a reply', $msg['uid']));
        } else {
            foreach (Hm_Msgs::get() as $msg) {
                $output->writeln($msg);
            }
            Hm_Msgs::flush();
        }
    }

    protected function action_discard($imap, $msg, $output)
    {
        if (imap_authed($imap)) {
            if ($imap->select_mailbox(hex2bin($msg['folder']))) {
                if ($imap->message_action('DELETE', [$msg['uid']])) {
                    $imap->message_action('EXPUNGE', [$msg['uid']]);
                    $output->writeln(tr('Removed msg uid %0', $msg['uid']));
                }
            }
        }
    }

    protected function action_reply($imap, $msg, $subject, $body, $config, $hmod, $output)
    {
        // quote original plain text
        $msg_text = $imap->get_message_content($msg['uid'], 0);
        $body .= implode("\n", array_map(function ($line) {
            return "> " . $line;
        }, explode("\n", $msg_text)));

        if (! $subject) {
            $subject = 'Re: ' . $msg['subject'];
        }

        $msg_headers = $imap->get_message_headers($msg['uid']);
        $profiles = $config['profiles'];
        $recip = get_primary_recipient($profiles, $msg_headers, Hm_SMTP_List::dump(), []);
        $in_reply_to = reply_to_id($msg_headers, 'reply');

        $result = tiki_send_email_through_cypht($recip, '', $subject, $body, $in_reply_to, null, $profiles, $hmod, $recip);
        if ($result) {
            $output->writeln(tr('Replied to msg uid %0', $msg['uid']));
        } else {
            foreach (Hm_Msgs::get() as $msg) {
                $output->writeln($msg);
            }
            Hm_Msgs::flush();
        }
    }

    protected function action_move_to_tracker($imap, $msg, $output, $res, $action = 'move')
    {
        $msg_headers = $imap->get_message_headers($msg['uid']);

        $trk = TikiLib::lib('trk');
        $item = $trk->get_item_info($res['itemId']);
        $field = $trk->get_field_info($res['fieldId']);

        if ($item && $field) {
            $msg_content = $imap->get_message_content($msg['uid'], 0);
            $msg_content = str_replace("\r\n", "\n", $msg_content);
            $msg_content = str_replace("\n", "\r\n", $msg_content);
            $msg_content = rtrim($msg_content) . "\r\n";

            $field['value'] = [
                'new' => [
                    'name' => ! empty($msg_headers['Message-ID']) ? $msg_headers['Message-ID'] : $msg_headers['Subject'],
                    'size' => strlen($msg_content),
                    'type' => 'message/rfc822',
                    'content' => $msg_content
                ],
                'folder' => isset($res['folder']) ? $res['folder'] : 'inbox',
            ];

            $trk->replace_item($item['trackerId'], $item['itemId'], [
                'data' => [$field]
            ]);

            if ($action == 'move') {
                $output->writeln(tr("Moved msg uid %0 to tracker %1, field %2, item %3", $msg['uid'], $item['trackerId'], $field['fieldId'], $item['itemId']));
                $this->action_discard($imap, $msg, $output);
            } else {
                $output->writeln(tr("Copied msg uid %0 to tracker %1, field %2, item %3", $msg['uid'], $item['trackerId'], $field['fieldId'], $item['itemId']));
            }
        }
    }
}
