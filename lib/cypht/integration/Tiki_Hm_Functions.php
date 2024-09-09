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

use rambomst\PHPBounceHandler\BounceHandler;

class Tiki_Hm_Functions
{
    public static function processFilter($filter, $imap, $msg)
    {
        $filtered = [];
        $bouncelist = [];
        $is_tracker_reply = false;
        foreach ($filter['conditions'] as $cond) {
            $values = [];
            if (in_array($cond['condition'], ['to', 'from', 'subject', 'size'])) {
                $values[] = $msg[$cond['condition']];
            } elseif (in_array($cond['condition'], ['cc', 'bcc', 'custom'])) {
                $header = $cond['condition'];
                if ($header == 'custom') {
                    $header = $cond['extra_option_value'];
                }
                $msg_headers = $imap->get_message_headers($msg['uid']);
                foreach ($msg_headers as $key => $val) {
                    if (strtolower($key) == strtolower($header)) {
                        $values[] = $val;
                    }
                }
            } elseif ($cond['condition'] == 'body') {
                $values[] = $imap->get_message_content($msg['uid'], 0);
            } elseif ($cond['condition'] == 'to_or_cc') {
                $msg_headers = $imap->get_message_headers($msg['uid']);
                foreach ($msg_headers as $key => $val) {
                    if (strtolower($key) == 'to' || strtolower($key) == 'cc') {
                        $values[] = $val;
                    }
                }
            } elseif ($cond['condition'] == 'bounce') {
                $msg_headers = $imap->get_message_headers($msg['uid']);
                $msg_content = $imap->get_message_content($msg['uid'], 0);
                $bouncehandler = new BounceHandler();
                $bounce_output = $bouncehandler->parseEmail($msg_content);
                foreach ($bounce_output as $result) {
                    if (empty($result['action'])) {
                        continue;
                    }
                    $is_bounce = false;
                    switch ($result['action']) {
                        case 'failed':
                            $is_bounce = 'hard';
                            break;
                        case 'transient':
                        case 'autoreply':
                            $is_bounce = 'soft';
                            break;
                    }
                    if ($is_bounce) {
                        $address = $result['recipient'] ?? $msg['from'];
                        foreach (process_address_fld($address) as $addr) {
                            $bouncelist[] = [
                                'mailbox' => $addr['email'],
                                'headers' => $msg_headers,
                                'msg' => $msg_content,
                                'type' => $is_bounce,
                            ];
                        }
                    }
                    $values[] = $is_bounce;
                }
            } elseif ($cond['condition'] == 'replytotrackermessage') {
                $item = self::getTrackerMessage($imap, $msg);
                $is_tracker_reply = boolval($item);
                $values[] = 1;
            }
            $values = array_filter($values, function ($val) use ($cond) {
                $type = isset($cond['type']) ? $cond['type'] : 'tracker';
                $not = $type[0] == '!';
                if ($not) {
                    $type = substr($type, 1);
                }
                if ($type == 'Contains') {
                    $comparison = stristr($val, $cond['value']);
                } elseif ($type == 'Matches') {
                    $comparison = preg_match('/' . str_replace('*', '.*', str_replace('?', '.?', $cond['value'])) . '/i', $val);
                } elseif ($type == 'Regex') {
                    $comparison = preg_match('/' . $cond['value'] . '/i', $val);
                } elseif ($type == 'Over') {
                    $comparison = floatval($val) > floatval($cond['value']);
                } elseif ($type == 'Under') {
                    $comparison = floatval($val) < floatval($cond['value']);
                } elseif ($type == 'Soft') {
                    $comparison = $val == 'soft';
                } elseif ($type == 'Hard') {
                    $comparison = $val == 'hard';
                } elseif ($type == 'tracker') {
                    $comparison = $val == 1;
                } else {
                    $comparison = false;
                }
                return $not ? ! $comparison : $comparison;
            });
            $filtered[] = $values ? true : false;
        }
        if ($filter['operator'] == 'ALLOF') {
            $pass = count(array_filter($filtered)) == count($filtered);
        } else {
            $pass = count(array_filter($filtered)) > 0;
        }
        return compact('pass', 'bouncelist', 'is_tracker_reply');
    }

    public static function getTrackerMessage($imap, $msg)
    {
        $reply_id = $file = null;
        $res = [];
        $msg_headers = $imap->get_message_headers($msg['uid']);
        foreach ($msg_headers as $key => $val) {
            if (strtolower($key) == 'in-reply-to') {
                $reply_id = $val;
            }
        }
        if ($reply_id) {
            $file = TikiLib::lib('filegal')->get_file_by_filename($reply_id);
        }
        if ($file) {
            $trk = TikiLib::lib('trk');
            $query = 'SELECT * FROM tiki_tracker_item_fields WHERE value LIKE ?';
            $res = $trk->fetchAll($query, ['%sent\":%' . $file['fileId'] . '%']);
        }
        return $res;
    }

    public static function initCyphtForBackend($page = null)
    {
        global $user, $tikipath;

        static $config = null;
        static $session = null;
        static $cache = null;
        static $module_exec = null;
        static $request = null;

        if (defined('APP_PATH') && ! empty($config)) {
            throw new Exception(tr('Cannot initialize Cypht backend when it is already initialized'));
        }

        if (is_null($config)) {
            // init Cypht app
            $_SESSION['cypht'] = [];
            $_SESSION['cypht']['preference_name'] = 'cypht_user_config';
            require_once $tikipath . '/lib/cypht/integration/classes.php';
            $_SESSION['cypht']['request_key'] = Hm_Crypt::unique_id();
            $_SESSION['cypht']['username'] = $user;

            $config = new Tiki_Hm_Site_Config_File();
            $config->set('disable_empty_superglobals', true);
            $environment->define_default_constants($config);
            $session_config = new Hm_Session_Setup($config);
            $session = $session_config->setup_session();
            $cache_setup = new Hm_Cache_Setup($config, $session);
            $cache = $cache_setup->setup_cache();

            $module_exec = new Hm_Module_Exec($config);
            $request = new Hm_Request($module_exec->filters, $config);
        }

        if ($page) {
            $module_exec->load_module_sets($page);
            $module_exec->run_handler_modules($request, $session, $page);
        }

        return compact('config', 'session', 'cache', 'module_exec', 'request');
    }

    public static function lastTimestampCheck(&$config)
    {
        if (isset($config['tiki_last_timestamp'])) {
            $last_timestamp = $config['tiki_last_timestamp'];
        } else {
            $last_timestamp = strtotime('1 hour ago');
        }

        $config['tiki_last_timestamp'] = time();

        return $last_timestamp;
    }
}
