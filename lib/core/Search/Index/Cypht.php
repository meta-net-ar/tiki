<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Index;

use Hm_IMAP_List;
use Search_Type_Factory_Direct;
use Search_Query_Interface;
use TikiLib;
use Tiki_Hm_Functions;
use Search_Expr_Token as Token;
use Search_Expr_And as AndX;
use Search_Expr_Or as OrX;
use Search_Expr_Not as NotX;
use Search_Expr_Range as Range;
use Search_Expr_Initial as Initial;
use Search_Expr_ImplicitPhrase as ImplicitPhrase;
use Search_ResultSet;

class Cypht
{
    protected $flags = ['ALL'];
    protected $searchParams = [];
    protected $exclude_deleted = true;

    public function find(Search_Query_Interface $query, $resultStart, $resultCount)
    {
        global $prefs, $tikipath;

        if ($prefs['feature_webmail'] !== 'y') {
            throw new Exception(tr('Webmail feature not enabled.'));
        }

        $sources = preg_split('/\s*,\s*/', $query->getCyphtSearch());

        if (empty($sources)) {
            throw new Exception(tr('No Cypht sources specified.'));
        }

        if (TikiLib::lib('unifiedsearch')->rebuildInProgress()) {
            // prevent searching in external/Cypht sources during index rebuild due to conflicts in cypht initialization and possible abuse of imap servers
            return new Search_ResultSet([], 0, $resultStart, $resultCount);
        }

        $res = $query->getExpr()->walk($this);
        foreach ($res as $part) {
            if ($part && is_array($part[0])) {
                foreach ($part as $subpart) {
                    $this->searchParams[] = $subpart;
                }
                continue;
            }
            if ($part && $part[0] == 'FLAGS') {
                $this->flags = $part[1];
                continue;
            }
            if ($part) {
                $this->searchParams[] = $part;
            }
        }
        if (in_array('DELETED', $this->flags, true)) {
            $this->exclude_deleted = false;
        }

        require_once $tikipath . '/lib/cypht/integration/Tiki_Hm_Functions.php';
        list('config' => $config, 'session' => $session, 'cache' => $cache, 'module_exec' => $module_exec, 'request' => $request) = Tiki_Hm_Functions::initCyphtForBackend('advanced_search');

        $msg_list = [];
        foreach ($sources as $source) {
            $parts = preg_split('/\s*>\s*/', $source);
            $source = $parts[0];
            $folder = $parts[1] ?? null;

            if (strstr($source, '@')) {
                // imap source
                $imap_id = null;
                foreach (Hm_IMAP_List::getAll() as $server) {
                    if ($server['user'] == $source || $server['name'] == $source) {
                        $imap_id = $server['id'];
                        break;
                    }
                }
                if ($imap_id) {
                    $imap_cache = Hm_IMAP_List::get_cache($cache, $imap_id);
                    $imap = Hm_IMAP_List::connect($imap_id, $imap_cache);
                    if (! imap_authed($imap)) {
                        Feedback::error(tr('Could not authenticate against imap server: %0', $source));
                        continue;
                    }
                    $folders = [];
                    if ($folder) {
                        $folders[] = $folder;
                    } else {
                        $folders = array_keys($imap->get_mailbox_list());
                    }
                    foreach ($folders as $folder) {
                        if (! $imap->select_mailbox($folder)) {
                            Feedback:error(tr('Could not open imap folder %0 on server %1', $folder, $source));
                            continue;
                        }
                        $msgs = $imap->search($this->flags, false, $this->searchParams, [], $this->exclude_deleted);
                        if (! $msgs) {
                            continue;
                        }
                        $server_details = Hm_IMAP_List::dump($imap_id);
                        foreach ($imap->get_message_list($msgs) as $msg) {
                            if (array_key_exists('content-type', $msg) && stristr($msg['content-type'], 'multipart/mixed')) {
                                $msg['flags'] .= ' \Attachment';
                            }
                            $msg['server_id'] = $imap_id;
                            $msg['folder'] = bin2hex($folder);
                            $msg['server_name'] = $server_details['name'];

                            $msg_list[] = $this->formatMessage($msg, sprintf('imap_%s_%s', $msg['server_id'], $msg['folder']));
                        }
                    }
                }
            } else {
                // aggregate folder: combined, sent, etc.
                $imap_ids = array_keys(Hm_IMAP_List::dump());
                $path = strtolower($source);
                if ($path == 'all' || $path == 'everything') {
                    $path = 'combined_inbox';
                }
                switch ($path) {
                    case 'unread':
                        $limit = $module_exec->user_config->get('unread_per_source_setting', DEFAULT_PER_SOURCE);
                        $date = process_since_argument($module_exec->user_config->get('unread_since_setting', DEFAULT_SINCE));
                        $folders = array_fill(0, count($imap_ids), 'INBOX');
                        $search_type = 'UNSEEN';
                        break;
                    case 'flagged':
                        $limit = $module_exec->user_config->get('flagged_per_source_setting', DEFAULT_PER_SOURCE);
                        $date = process_since_argument($module_exec->user_config->get('flagged_since_setting', DEFAULT_SINCE));
                        $folders = array_fill(0, count($imap_ids), 'INBOX');
                        $search_type = 'FLAGGED';
                        break;
                    case 'combined_inbox':
                        $limit = $module_exec->user_config->get('all_per_source_setting', DEFAULT_PER_SOURCE);
                        $date = process_since_argument($module_exec->user_config->get('all_since_setting', DEFAULT_SINCE));
                        $folders = array_fill(0, count($imap_ids), 'INBOX');
                        $search_type = 'ALL';
                        break;
                    case 'email':
                        $limit = $module_exec->user_config->get('all_email_per_source_setting', DEFAULT_PER_SOURCE);
                        $date = process_since_argument($module_exec->user_config->get('all_email_since_setting', DEFAULT_SINCE));
                        $folders = array_fill(0, count($imap_ids), 'INBOX');
                        $search_type = 'ALL';
                        break;
                    case 'sent':
                    case 'junk':
                    case 'trash':
                    case 'drafts':
                        $limit = $module_exec->user_config->get($path . '_per_source_setting', DEFAULT_PER_SOURCE);
                        $date = process_since_argument($module_exec->user_config->get($path . '_since_setting', DEFAULT_SINCE));
                        $folders = [];
                        $realpath = $path == 'drafts' ? 'draft' : $path;
                        foreach ($imap_ids as $imap_id) {
                            $special = get_special_folders($module_exec, $imap_id);
                            if (! empty($special[$realpath])) {
                                $folders[] = $special[$realpath];
                            } else {
                                $folders[] = 'N/A';
                            }
                        }
                        break;
                    default:
                        throw new Exception(tr('Cypht source not recognized: %0', $source));
                }
                $flags = $this->flags;
                if (! in_array($search_type, $flags)) {
                    if ($flags === ['ALL'] && $search_type !== 'ALL') {
                        $flags = [$search_type];
                    } else {
                        $flags[] = $search_type;
                    }
                }
                $terms = $this->searchParams;
                $terms[] = ['SINCE', $date];
                list($status, $msgs) = merge_imap_search_results($imap_ids, $flags, $session, $cache, $folders, $limit, $terms);
                foreach ($msgs as $msg) {
                    $msg_list[] = $this->formatMessage($msg, $path);
                }
            }
        }
        usort($msg_list, function ($a, $b) {
            if (! array_key_exists('email_date', $a) || (! array_key_exists('email_date', $b))) {
                return 0;
            }
            return strtotime($b['email_date']) - strtotime($a['email_date']);
        });

        $resultSet = new Search_ResultSet(array_slice($msg_list, $resultStart, $resultCount), count($msg_list), $resultStart, $resultCount);
        return $resultSet;
    }

    public function scroll(Search_Query_Interface $query)
    {
        $perPage = 100;
        $hasMore = true;

        for ($from = 0; $hasMore; $from += $perPage) {
            $result = $this->find($query, $from, $perPage);
            foreach ($result as $row) {
                yield $row;
            }

            $hasMore = $result->hasMore();
        }
    }

    public function __invoke($node, $childNodes)
    {
        if ($node instanceof ImplicitPhrase) {
            $node = $node->getBasicOperator();
        }

        if (count($childNodes) === 0 && ($node instanceof AndX || $node instanceof OrX)) {
        } elseif (count($childNodes) === 1 && ($node instanceof AndX || $node instanceof OrX)) {
            return reset($childNodes);
        } elseif ($node instanceof OrX) {
            return array_filter($childNodes);
        } elseif ($node instanceof AndX) {
            return array_filter($childNodes);
        } elseif ($node instanceof NotX) {
            $child = reset($childNodes);
            if ($child && $child[0] == 'FLAGS') {
                foreach ($child[1] as &$flag) {
                    if (substr($flag, 0, 2) == 'UN') {
                        $flag = substr($flag, 2);
                    } else {
                        $flag = 'UN' . $flag;
                    }
                }
            } elseif ($child) {
                if (is_array($child[0])) {
                    foreach ($child[0] as &$grandchild) {
                        $grandchild[0] = 'NOT ' . $grandchild[0];
                    }
                } else {
                    $child[0] = 'NOT ' . $child[0];
                }
            }
            return $child;
        } elseif ($node instanceof Token || $node instanceof Initial) {
            $raw = $node->getValue(new Search_Type_Factory_Direct())->getValue();
            $field = $node->getField();
            if (substr($field, 0, 6) === 'email_') {
                switch (substr($field, 6)) {
                    case 'subject':
                        return ['SUBJECT', $raw];
                    case 'from':
                        return ['FROM', $raw];
                    case 'sender':
                        return ['SENDER', $raw];
                    case 'date':
                        return ['SENTON', date('j-M-Y', strtotime($raw))];
                    case 'flags':
                        if (! is_array($raw)) {
                            $raw = [$raw];
                        }
                        return ['FLAGS', array_map(function ($flag) {
                            return strtoupper($flag);
                        }, $raw)];
                    case 'body':
                        return ['BODY', $raw];
                    case 'html':
                    case 'plaintext':
                        return ['TEXT', $raw];
                }
            }
        } elseif ($node instanceof Range) {
            $from = $node->getToken('from')->getValue(new Search_Type_Factory_Direct())->getValue();
            $to = $node->getToken('to')->getValue(new Search_Type_Factory_Direct())->getValue();
            if ($node->getField() == 'email_date') {
                return [['SENTBEFORE', date('j-M-Y', $to)], ['SENTSINCE', date('j-M-Y', $from)]];
            }
        }
    }

    private function formatMessage($msg, $parent_path)
    {
        $flags = [];
        if (! stristr($msg['flags'], 'seen')) {
            $flags[] = 'unseen';
        }
        foreach (['attachment', 'deleted', 'flagged', 'answered', 'draft'] as $flag) {
            if (stristr($msg['flags'], $flag)) {
                $flags[] = $flag;
            }
        }
        $msg['flags'] = $flags;

        // mapped with Search_ContentSource_FileSource when it is an email, so we have common interface to access email fields in the index
        $content_placeholder = 'Cypht message can be viewed within web interface by opening this email.';
        return [
            'object_type' => 'email',
            'object_id' => $msg['uid'],
            'title' => $msg['message_id'],
            'email_subject' => $msg['subject'],
            'email_from' => $msg['from'],
            'email_sender' => $msg['sender'] ?? null,
            'email_recipient' => $msg['to'],
            'email_date' => $msg['date'],
            'email_flags' => $msg['flags'],
            'email_content_type' => $msg['content-type'],
            'email_body' => $content_placeholder,
            'email_plaintext' => $content_placeholder,
            'email_html' => $content_placeholder,
            'url' => 'tiki-webmail.php?page=message&uid=' . $msg['uid'] . '&list_path=' . sprintf('imap_%s_%s', $msg['server_id'], $msg['folder']) . '&list_parent=' . $parent_path,
        ];
    }
}
