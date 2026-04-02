<?php

/**
 * Tiki modules
 * @package modules
 * @subpackage tiki
 */

if (! defined('DEBUG_MODE')) {
    die();
}


/**
 * Retrive a Tiki-stored mail message and convert to a parsed mime message
 * @subpackage tiki/functions
 * @param string $list_path the Cypht list path
 * @param string $msg_uid message uid
 * @return array
 */
if (! hm_exists('tiki_parse_message')) {
    function tiki_parse_message($list_path, $msg_uid)
    {
        $trk = TikiLib::lib('trk');
        $path = str_replace('tracker_folder_', '', $list_path);
        list ($itemId, $fieldId) = explode('_', $path);

        $field = $trk->get_field_info($fieldId);
        if (! $field) {
            Hm_Msgs::add(tr('Tracker field not found'), 'danger');
            return;
        }

        $item = $trk->get_item_info($itemId);
        if (! $item) {
            Hm_Msgs::add(tr('Tracker item not found'), 'danger');
            return;
        }
        $item[$field['fieldId']] = $trk->get_item_value(null, $item['itemId'], $field['fieldId']);

        $handler = $trk->get_field_handler($field, $item);
        $data = $handler->getFieldData();

        if (! isset($data['emails']) || ! is_array($data['emails'])) {
            Hm_Msgs::add(tr('Tracker field storage is broken or you are using the wrong field type'), 'danger');
            return;
        }

        $email = false;
        foreach ($data['emails'] as $folder => $emails) {
            $prev = $next = false;
            foreach ($emails as $eml) {
                if ($eml['fileId'] == $msg_uid) {
                    $email = $eml;
                } else {
                    if (! $email) {
                        $prev = $eml;
                    } elseif (! $next) {
                        $next = $eml;
                    }
                }
            }
            if ($email) {
                if ($handler->getOption('useFolders')) {
                    $email['show_archive'] = $folder != 'archive';
                    $email['is_tiki_tracker_trash_folder'] = $folder == 'trash';
                }
                break;
            }
        }

        if (! $email) {
            Hm_Msgs::add(tr('Email not found in related tracker item'), 'warning');
            return;
        }

        if (empty($email['message_raw'])) {
            Hm_Msgs::add(tr('Email could not be parsed'), 'warning');
            return;
        }

        $email['prev'] = $prev;
        $email['next'] = $next;

        return $email;
    }
}

/**
 * Convert MimePart message parts to IMAP-compatible BODYSTRUCTURE
 * @subpackage tiki/functions
 * @param ZBateson\MailMimeParser\Message\Part\MimePart $part the mime message part
 * @param string $part_num the mime message part number
 * @return array
 */
if (! hm_exists('tiki_mime_part_to_bodystructure')) {
    function tiki_mime_part_to_bodystructure($part, $part_num = '0')
    {
        $content_type = explode('/', $part->getContentType());
        $header = $part->getHeader('Content-Type');
        $attributes = [];
        if ($header) {
            foreach (['boundary', 'charset', 'name'] as $param) {
                if ($header->hasParameter($param)) {
                    $attributes[$param] = $header->getValueFor($param);
                }
            }
        }
        $header = $part->getHeader('Content-Disposition');
        $file_attributes = [];
        if ($header) {
            $file_attributes[$header->getValue()] = [];
            if ($header->getValueFor('filename')) {
                $file_attributes[$header->getValue()][] = 'filename';
                $file_attributes[$header->getValue()][] = $header->getValueFor('filename');
            }
        }
        $msgContent = (string) $part->getContent();
        $result = [$part_num => [
        'type' => $content_type[0],
        'subtype' => $content_type[1],
        'attributes' => $attributes,
        "id" => $part->getContentId(),
        'description' => false,
        'encoding' => $part->getContentTransferEncoding(),
        'size' => strlen($msgContent),
        'lines' => $part->isTextPart() ? substr_count($msgContent, "\n") : false,
        'md5' => false,
        'disposition' => $part->getContentDisposition(false),
        'file_attributes' => $file_attributes,
        'language' => false,
        'location' => false,
        ]];
        if ($part->getChildCount() > 0) {
            $result[$part_num]['subs'] = [];
            foreach ($part->getChildParts() as $i => $subpart) {
                $subpart_num = $part_num . '.' . ($i + 1);
                $result[$part_num]['subs'] = array_merge($result[$part_num]['subs'], tiki_mime_part_to_bodystructure($subpart, $subpart_num));
            }
        }
        return $result;
    }
}

/**
 * Retrieve mime part based off part number
 * @subpackage tiki/functions
 * @param ZBateson\MailMimeParser\Message\Part\MimePart $part the mime message part
 * @param string $part_num the mime message part number
 * @return ZBateson\MailMimeParser\Message\Part\MimePart
 */
if (! hm_exists('tiki_get_mime_part')) {
    function tiki_get_mime_part($part, $part_num = '0')
    {
        $part_num = explode('.', $part_num);
        array_shift($part_num);
        if (empty($part_num)) {
            return $part;
        }
        $part_num = array_values($part_num);
        foreach ($part->getChildParts() as $i => $subpart) {
            if ($part_num[0] - 1 == $i) {
                return tiki_get_mime_part($subpart, implode('.', $part_num));
            }
        }
        return null;
    }
}


/**
 * Replace inline images in an HTML message part
 * @subpackage tiki/functions
 * @param ZBateson\MailMimeParser\Message\Part\MimePart $message the mime message part
 * @param string $txt HTML
 */
if (! hm_exists('tiki_add_attached_images')) {
    function tiki_add_attached_images($message, $txt)
    {
        if (preg_match_all("/src=('|\"|)cid:([^\s'\"]+)/", $txt, $matches)) {
            $cids = array_pop($matches);
            foreach ($cids as $id) {
                $part = $message->getPartByContentId($id);
                if (! $part || substr($part->getContentType(), 0, 5) != 'image') {
                    continue;
                }
                $txt = str_replace('cid:' . $id, 'data:' . $part->getContentType() . ';base64,' . base64_encode($part->getContent()), $txt);
            }
        }
        return $txt;
    }
}

/**
 * Copy/Move messages from Tiki to an IMAP server
 * @subpackage tiki/functions
 * @param array $email Tiki-stored message to move
 * @param string $action action type, copy or move
 * @param array $dest_path imap id and folder to copy/move to
 * @param object $hm_cache cache interface
 * @return boolean result
 */
if (! hm_exists('tiki_move_to_imap_server')) {
    function tiki_move_to_imap_server($email, $action, $dest_path, $hm_cache)
    {
        $mailbox = Hm_IMAP_List::get_connected_mailbox($dest_path[1], $hm_cache);
        if ($mailbox) {
            $file = Tiki\FileGallery\File::id($email['fileId']);
            $msg = $file->getContents();
            if ($mailbox->store_message(hex2bin($dest_path[2]), $msg)) {
                if ($action == 'move') {
                    $trk = TikiLib::lib('trk');
                    $field = $trk->get_field_info($email['fieldId']);
                    if (! $field) {
                        return false;
                    }
                    $field['value'] = [
                        'delete' => $email['fileId']
                    ];
                    $trk->replace_item($email['trackerId'], $email['itemId'], [
                        'data' => [$field]
                    ]);
                }
                return true;
            }
        }
        return false;
    }
}

/**
 * Toggle a flag from a Tiki-stored message
 * @subpackage tiki/functions
 * @param array $fileId Tiki-stored message file ID
 * @param string $flag the flag to toggle
 * @return string the current flag state
 */
if (! hm_exists('tiki_toggle_flag_message')) {
    function tiki_toggle_flag_message($fileId, $flag)
    {
        $file = Tiki\FileGallery\File::id($fileId);
        if (preg_match("/Flags: (.*?)\r\n/", $file->getContents(), $matches)) {
            $flags = $matches[1];
        } else {
            $flags = '';
        }
        if (stristr($flags, $flag)) {
            return tiki_flag_message($fileId, 'remove', $flag);
        } else {
            return tiki_flag_message($fileId, 'add', $flag);
        }
    }
}


/**
 * Add or remove a flag from a Tiki-stored message
 * @subpackage tiki/functions
 * @param array $fileId Tiki-stored message file ID
 * @param string $action action type, add or remove
 * @param string $flag the flag to add or remove
 * @return string the current flag state
 */
if (! hm_exists('tiki_flag_message')) {
    function tiki_flag_message($fileId, $action, $flag)
    {
        $file = Tiki\FileGallery\File::id($fileId);
        if (preg_match("/Flags: (.*?)\r\n/", $file->getContents(), $matches)) {
            $flags = $matches[1];
        } else {
            $flags = '';
        }
        $state = $flag;
        if ($action == 'remove') {
            $flags = preg_replace('/\\\?' . ucfirst($flag) . '/', '', $flags);
            $state = 'un' . $flag;
        } elseif (! stristr($flags, $flag)) {
            $flags .= ' \\' . ucfirst($flag);
            $state = $flag;
        }
        $flags = preg_replace("/\s{2,}/", ' ', trim($flags));
        $raw = preg_replace("/Flags:.*?\r\n/", "Flags: $flags\r\n", $file->getContents(), -1, $cnt);
        if ($cnt == 0) {
            $raw = "Flags: $flags\r\n" . $raw;
        }
        $file->replaceQuick($raw);
        return $state;
    }
}

if (! hm_exists('tiki_send_email_through_cypht')) {
    function tiki_send_email_through_cypht($to, $cc, $subject, $body, $in_reply_to, $file, $profiles, $hmod, $recipient = null)
    {
        // Ensure IMAP dependency is available
        if (! class_exists('Hm_SMTP_List')) {
            Hm_Msgs::add('The smtp module is required to send emails. Please enable it through the configuration file.', 'danger');
            return false;
        }

        // retrieve smtp server connected with an existing imap message via profiles
        $smtp_id = 0;
        $compose_smtp_id = null;
        if ($recipient) {
            $profile_index = $default_profile_index = null;
            foreach ($profiles as $index => $profile) {
                if ($profile['address'] == $recipient) {
                    $profile_index = $index;
                }
                if (! empty($profile['default'])) {
                    $default_profile_index = $index;
                }
            }
            if (is_null($profile_index)) {
                $profile_index = $default_profile_index;
            }
            if (! is_null($profile_index)) {
                $smtp_id = $profiles[$profile_index]['smtp_id'];
                $compose_smtp_id = $smtp_id . '.' . ($profile_index + 1);
            }
        }

        // smtp server details
        $smtp_details = Hm_SMTP_List::dump($smtp_id, true);
        if (! $smtp_details) {
            Hm_Msgs::add(tr('Could not use the configured SMTP server'), 'danger');
            return false;
        }

        // profile details
        list($imap_server, $from_name, $reply_to, $from) = get_outbound_msg_profile_detail(['compose_smtp_id' => $compose_smtp_id], $profiles, $smtp_details, $hmod);
        // xoauth2 check
        smtp_refresh_oauth2_token_on_send($smtp_details, $hmod, $smtp_id);

        // adjust from and reply to addresses
        list($from, $reply_to) = outbound_address_check($hmod, $from, $reply_to);

        // try to connect
        $smtp = Hm_SMTP_List::connect($smtp_id, false);
        if (! $smtp->authed()) {
            Hm_Msgs::add(tr("Failed to authenticate to the SMTP server"), "danger");
            return false;
        }

        // build message
        $mime = new Hm_MIME_Msg($to, $subject, $body, $from, 0, $cc, '', $in_reply_to, $from_name, $reply_to);

        if ($file) {
            $mime->add_attachments([$file]);
        }

        // get smtp recipients
        $recipients = $mime->get_recipient_addresses();
        if (empty($recipients)) {
            Hm_Msgs::add(tr("No valid receipts found"), "warning");
            return false;
        }

        // send the message
        $err_msg = $smtp->send_message($from, $recipients, $mime->get_mime_msg());
        if ($err_msg) {
            Hm_Msgs::add(sprintf("%s", $err_msg), "danger");
            return false;
        }

        return true;
    }
}

/**
 * @subpackage tiki/functions
 * @param object $mod the module calling this function
 * @return string trackers with email folder fields dropdown
 */
if (! hm_exists('tiki_move_to_tracker_dropdown')) {
    function tiki_move_to_tracker_dropdown($mod, $title = "Trackers", $dropdown_title = 'Move to trackers...', $class = 'move_to_trackers', $message_view = false)
    {
        $trk = TikiLib::lib('trk');
        $fields = $trk->get_fields_by_type('EF');
        if (! $fields) {
            return;
        }
        $field_list = [];
        foreach ($fields as $field) {
            $tracker = $trk->get_tracker($field['trackerId']);
            $handler = $trk->get_field_handler($field);
            if ($handler->getOption('useFolders')) {
                $folder_list = [];
                foreach ($handler->getFolders() as $folder => $folderName) {
                    $folder_list[] = "<div><a href='#' class='object_selector_trigger dropdown-item' data-tracker='{$field['trackerId']}' data-field='{$field['fieldId']}' data-folder='" . htmlspecialchars($folder) . "'>{$folderName}</a></div>";
                }
                $field_list[] = "<a href='#' class='tiki_folder_trigger dropdown-item'>{$tracker['name']} - {$field['name']}</a>"
                    . "<div class='tiki_folder_container' style='display: none'>" . implode("\n", $folder_list) . "</div>";
            } else {
                $field_list[] = "<a href='#' class='object_selector_trigger dropdown-item' data-tracker='{$field['trackerId']}' data-field='{$field['fieldId']}' data-folder='inbox'>{$tracker['name']} - {$field['name']}</a>";
            }
        }
        $res = "<div class=\"" . ($class != 'move_to_trackers' ? 'dropdown ' : '') . "d-inline-block\">";
        if ($class != 'move_to_trackers') {
            $res .= "<a class=\"hlink text-decoration-none btn btn-sm btn-outline-secondary dropdown-toggle" . (! $message_view ? ' btn btn-sm btn-light border text-black-50' : '') . "\" id=\"{$class}\" href=\"#\" data-bs-toggle='dropdown' aria-haspopup='true' aria-expanded='true' data-bs-auto-close='outside'>" . $mod->trans($title) . "</a>";
            $res .= "<div class='" . $class . " dropdown-menu' aria-labelledby='$class'><div class='move_to_title'>" . $mod->trans($dropdown_title) . "</div>" . implode("<br>\n", $field_list) . "</div>";
        } else {
            $res .= "<a class=\"hlink text-decoration-none btn btn-sm btn-outline-secondary" . (! $message_view ? ' btn btn-sm btn-light border text-black-50' : '') . "\" id=\"{$class}\" href=\"#\" >" . $mod->trans($dropdown_title) . "</a>";
        }
        $res .= "</div>";

        return $res;
    }
}

/**
 * @subpackage tiki/functions
 * @return string Restore message button
 */
if (! hm_exists('tiki_restore_message')) {
    function tiki_restore_message($mod)
    {
        $res = "<div class='d-inline-block' id='restore_message' style='display: none'><a href='#' title='" . $mod->trans("Restore message to inbox") . "' class='restore_message hlink text-decoration-none btn btn-sm btn-outline-secondary' >" . $mod->trans("Restore") . "</a></div>";
        return $res;
    }
}

/**
 * @subpackage tiki/functions
 * @return string array message and headers
 */
if (! hm_exists('get_message_data')) {
    function get_message_data($mailbox, $folder, $msg_id)
    {
        $msg = $mailbox->get_message_content($folder, $msg_id, 0);
        $msg = str_replace("\r\n", "\n", $msg);
        $msg = str_replace("\n", "\r\n", $msg);
        $msg = rtrim($msg) . "\r\n";

        $headers = $mailbox->get_message_headers($folder, $msg_id);
        if (! empty($headers['Flags'])) {
            $msg = "Flags: " . $headers['Flags'] . "\r\n" . $msg;
        }

        return [$msg, $headers];
    }
}

/**
 * @subpackage tiki/functions
 * @return string ensure file was saved before removing it from remote mailbox
 */
if (! hm_exists('bind_tracker_item_update_event')) {
    function bind_tracker_item_update_event($mailbox, $folder, $form, $msg_ids)
    {
        TikiLib::events()->bind('tiki.trackeritem.update', function ($args) {
            $mailbox = $args['mailbox'];
            $folder = $args['folder'];
            $form = $args['form'];
            $old = $args['old_values'][$form['tracker_field_id']];
            $new = $args['values'][$form['tracker_field_id']];
            if ($old != $new) {
                // Check system preference for email-to-tracker mode
                global $prefs;
                $email_mode = $prefs['email_to_tracker_mode'] ?? 'move';

                if ($email_mode == 'move') {
                    foreach ($args['msg_ids'] as $msg_id) {
                        $mailbox->delete_message($folder, $msg_id, false);
                    }
                }
            }
        }, ['mailbox' => $mailbox, 'folder' => $folder, 'form' => $form, 'msg_ids' => $msg_ids]);
    }
}

/**
 * @subpackage tiki/functions
 * @return string ensure file was saved before removing it from remote mailbox
 */
if (! hm_exists('append_to_msg_headers')) {
    function append_to_msg_headers($headers, $link)
    {
        $headers = preg_replace('#</div><span id="extra-header-buttons"></span>#s', $link . "\\0", $headers, 1);

        return $headers;
    }
}

function find_relevant_tracker_items($keywords, $multivalueField = '', $searchArgs = [])
{
    global $prefs;

    $lib = TikiLib::lib('unifiedsearch');
    $query = new Search_Query();
    $lib->initQuery($query);
    $query->filterType('trackeritem');

    $query->setOrder($searchArgs['sort_mode'] ?? 'score_desc');
    $query->setRange(0, $searchArgs['maxRecords'] ?? $prefs['maxRecords']);

    if (! empty($searchArgs['tracker_id'])) {
        $result = TikiLib::lib('trk')->list_tracker_fields($searchArgs['tracker_id']);
        $fields = $result['data'];
        if (! empty($searchArgs['field_id'])) {
            $fields = array_filter($fields, function ($field) use ($searchArgs) {
                return $field['fieldId'] == $searchArgs['field_id'];
            });
        }
        $query->filterIdentifier($searchArgs['tracker_id'], 'tracker_id');
    } else {
        $fields = TikiLib::lib('trk')->get_fields_by_type('EF');
        $trackerIds = array_unique(array_map(function ($field) {
            return $field['trackerId'];
        }, $fields));
        $query->filterContent(implode(' OR ', $trackerIds), 'tracker_id');
    }

    if ($multivalueField) {
        $filterField = implode(',', array_map(function ($f) use ($multivalueField) {
                return "tracker_field_{$f['permName']}_{$multivalueField}";
        }, $fields));
        $query->filterMultivalue($filterField, $keywords);
    } else {
        $query->filterContent($keywords);
    }

    $resultSet = $query->search($lib->getIndex());

    $resultSet->applyTransform(function ($item) use ($fields) {
        $filtered = array_filter($fields, function ($f) use ($item) {
            return $f['trackerId'] == $item['tracker_id'];
        });
        if ($field = reset($filtered)) {
            $item['field_id'] = $field['fieldId'];
        }
        return [
            'object_id' => $item['object_id'],
            'tracker_id' => $item['tracker_id'],
            'field_id' => $item['field_id'] ?? 0,
            'title' => $item['title'],
        ];
    });

    return $resultSet->jsonSerialize()['result'];
}

function tiki_fetch_tracker_messages($folder, $limit, $since, $keyword = '')
{
    $trk = TikiLib::lib('trk');
    $efFields = $trk->get_fields_by_type('EF');
    $messages = [];

    if (! $efFields) {
        return [];
    }

    $fieldsById = [];
    $fieldIds = [];
    foreach ($efFields as $field) {
        $fieldId = (int) $field['fieldId'];
        $fieldsById[$fieldId] = $field;
        $fieldIds[] = $fieldId;
    }

    if (! $fieldIds) {
        return [];
    }

    // Prefilter to only email-folder fields that actually contain data on open tracker items.
    $placeholders = implode(',', array_fill(0, count($fieldIds), '?'));
    $query = "SELECT ttif.`itemId`, ttif.`fieldId`, ttif.`value`"
        . " FROM `tiki_tracker_item_fields` ttif"
        . " INNER JOIN `tiki_tracker_items` tti ON (tti.`itemId` = ttif.`itemId`)"
        . " WHERE ttif.`fieldId` IN ($placeholders)"
        . " AND ttif.`value` IS NOT NULL AND ttif.`value` != ''"
        . " AND tti.`status` = ?";
    $result = $trk->query($query, array_merge($fieldIds, ['o']));

    $needle = mb_strtolower(trim((string) $keyword));
    $sinceTs = strtotime($since);
    $itemInfoCache = [];
    while ($row = $result->fetchRow()) {
        $itemId = (int) $row['itemId'];
        $fieldId = (int) $row['fieldId'];
        $rawValue = $row['value'];

        if (! isset($fieldsById[$fieldId])) {
            continue;
        }
        if (! $rawValue) {
            continue;
        }

        if (! array_key_exists($itemId, $itemInfoCache)) {
            $itemInfoCache[$itemId] = $trk->get_item_info($itemId);
        }

        if (! $itemInfoCache[$itemId]) {
            continue;
        }

        $field = $fieldsById[$fieldId];
        $trackerId = (int) $field['trackerId'];
        $itemInfo = $itemInfoCache[$itemId];
        $itemInfo[$fieldId] = $rawValue;
        $handler = $trk->get_field_handler($field, $itemInfo);
        if (! $handler) {
            continue;
        }

        $data = $handler->getFieldData();
        $emailsByFolder = $data['emails'];

        $foldersToProcess = $folder ? [$folder] : array_keys($emailsByFolder);

        foreach ($foldersToProcess as $folderName) {
            $folderEmails = $emailsByFolder[$folderName] ?? [];
            foreach ($folderEmails as $email) {
                // If a keyword is provided, filter to only keep if it's found in from/to/subject
                if ($needle !== '') {
                    $fields = [
                        $email['from'] ?? '',
                        $email['to'] ?? '',
                        $email['subject'] ?? '',
                    ];

                    $haystack = mb_strtolower(implode(' ', $fields));
                    $needleNormalized = mb_strtolower($needle);

                    if (mb_strpos($haystack, $needleNormalized) === false) {
                        continue;
                    }
                }

                $rawDate = $email['date'] ?? false;
                $ts = is_numeric($rawDate) ? (int)$rawDate : strtotime($rawDate);

                // Filter to only keep if it's newer than $since
                if ($ts !== false && $ts >= $sinceTs) {
                    $email['trackerId'] = $trackerId;
                    $email['itemId'] = $itemId;
                    $email['fieldId'] = $fieldId;
                    $email['folder'] = $folderName;

                    // Transform to string date format as for imap messages for correct output
                    $email['date'] = date('r', $ts);
                    $messages[] = $email;

                    // New item added, if limit reached, break all loops
                    if (count($messages) >= $limit) {
                        break 3;
                    }
                }
            }
        }
    }

    usort($messages, function ($a, $b) {
        return strtotime($b['date'] ?? '') <=> strtotime($a['date'] ?? '');
    });

    return array_slice($messages, 0, (int) $limit);
}

/**
 * Format tracker folder emails for display in the message list
 * @subpackage tiki/functions
 * @param array $msg_list list of tracker emails
 * @param object $output_module Hm_Output_Module
 * @param string $parent_list parent list path (e.g., 'trackers' or specific tracker folder)
 * @param string $style 'email' or 'news' layout style
 * @return array formatted message rows
 */
function format_tracker_message_list($msg_list, $output_module, $parent_list = false, $style = 'email')
{
    $res = [];
    if ($msg_list === [false] || empty($msg_list)) {
        return $msg_list;
    }

    $show_icons = $output_module->get('msg_list_icons');
    $list_page = $output_module->get('list_page', 0);
    $list_sort = $output_module->get('list_sort', $output_module->get('default_sort_order'));
    $list_filter = $output_module->get('list_filter');
    $list_keyword = $output_module->get('list_keyword');

    foreach ($msg_list as $msg) {
        $row_class = 'email tracker';
        $icon = 'env_open';

        // Unique ID for this email: tracker_fileId_itemId_fieldId_trackerId
        $id = sprintf("tracker_%s_%s_%s_%s", $msg['fileId'], $msg['itemId'], $msg['fieldId'], $msg['trackerId']);

        // Set default subject
        if (! trim($msg['subject'] ?? '')) {
            $msg['subject'] = '[No Subject]';
        }
        $subject = $msg['subject'];
        $preview_msg = $msg['preview_msg'] ?? "";
        $type_msg = $msg['type_msg'] ?? "";

        // Determine if this is from sent folder
        $folder = $msg['folder'] ?? 'inbox';
        if ($folder == 'sent') {
            $icon = 'sent';
            $from = $msg['to'] ?? '';
        } else {
            $from = $msg['from'] ?? '';
        }

        // Format the from field
        $from = is_array($from) ? implode(', ', $from) : $from;
        $from = format_imap_from_fld($from);
        $nofrom = '';
        if (! trim($from)) {
            $from = '[No From]';
            $nofrom = ' nofrom';
        }

        // Determine date display
        if (isset($msg['date']) && ! empty($msg['date'])) {
            $date = translate_time_str(human_readable_interval($msg['date']), $output_module);
            $timestamp = strtotime($msg['date']);
        } else {
            $date = translate_time_str(human_readable_interval('now'), $output_module);
            $timestamp = time();
        }

        $flags = [];

        // Check for seen flag
        if (! isset($msg['flags']) || ! array_key_exists('seen', $msg['flags'])) {
            $flags[] = 'unseen';
            if ($icon != 'sent') {
                $icon = 'env_closed';
            }
        } else {
            $row_class .= ' seen';
        }

        // Check for other common flags
        foreach (['attachment', 'deleted', 'flagged', 'answered', 'draft'] as $flag) {
            if (isset($msg['flags']) && array_key_exists($flag, $msg['flags'])) {
                $flags[] = $flag;
            }
        }

        // Build row classes
        $source = sprintf('%s - %s', $msg['tracker_name'] ?? 'Tracker', $msg['field_name'] ?? 'Email Field');
        $row_class .= ' ' . str_replace(' ', '_', $source);
        $row_class .= ' ' . implode(' ', $flags);

        if ($folder && $folder != 'inbox') {
            $source .= ' - ' . ucfirst($folder);
        }

        // Build message URL
        $url = '?page=message&uid=' . $msg['fileId'] . '&list_path=' .
                sprintf('tracker_folder_%s_%s', $msg['itemId'], $msg['fieldId']) .
                '&list_parent=' . sprintf('tracker_%s', $msg['trackerId']);

        if ($list_page) {
            $url .= '&list_page=' . $output_module->html_safe($list_page);
        }
        if ($list_sort) {
            $url .= '&sort=' . $output_module->html_safe($list_sort);
        }
        if ($list_filter) {
            $url .= '&filter=' . $output_module->html_safe($list_filter);
        }
        if ($list_keyword) {
            $url .= '&keyword=' . $output_module->html_safe($list_keyword);
        }

        if (! $show_icons) {
            $icon = false;
        }

        // Extract message ID and in-reply-to headers
        $msgId = $msg['message_id'] ?? '';
        $inReplyTo = $msg['in_reply_to'] ?? '';

        if ($msgId) {
            $msgId = str_replace(['<', '>'], '', trim($msgId));
        }
        if ($inReplyTo) {
            $inReplyTo = str_replace(['<', '>'], '', trim($inReplyTo));
        }

        // Build message row based on style
        if ($style == 'news') {
            $res[$id] = message_list_row(
                [
                    ['checkbox_callback', $id],
                    ['icon_callback', $flags],
                    ['subject_callback', $subject, $url, $flags, $icon, $preview_msg, $type_msg],
                    ['safe_output_callback', 'source', $source],
                    ['safe_output_callback', 'from' . $nofrom, $from, null, str_replace([$from, '<', '>'], '', $msg['from'] ?? '')],
                    ['date_callback', $date, $timestamp, false],
                    ['dates_holders_callback', $msg['date'] ?? '', $msg['date'] ?? ''],
                ],
                $id,
                $style,
                $output_module,
                $row_class,
                $msgId,
                $inReplyTo
            );
        } else {
            $res[$id] = message_list_row(
                [
                    ['checkbox_callback', $id],
                    ['safe_output_callback', 'source', $source, $icon],
                    ['safe_output_callback', 'from' . $nofrom, $from, null, str_replace([$from, '<', '>'], '', $msg['from'] ?? '')],
                    ['subject_callback', $subject, $url, $flags, null, $preview_msg, $type_msg],
                    ['date_callback', $date, $timestamp, false],
                    ['icon_callback', $flags],
                    ['dates_holders_callback', $msg['date'] ?? '', $msg['date'] ?? ''],
                ],
                $id,
                $style,
                $output_module,
                $row_class,
                $msgId,
                $inReplyTo
            );
        }
    }

    return $res;
}

/**
 * Prepare tracker emails for display using format_tracker_message_list
 * @subpackage tiki/functions
 * @param array $msgs tracker message list
 * @param object $mod Hm_Output_Module
 * @param string $type list path type
 * @return void
 */
function prepare_tracker_message_list($msgs, $mod, $type)
{
    $style = $mod->get('news_list_style') ? 'news' : 'email';
    if ($mod->get('is_mobile')) {
        $style = 'news';
    }
    $res = format_tracker_message_list($msgs, $mod, $type, $style);
    $mod->out('formatted_message_list', $res);
}
