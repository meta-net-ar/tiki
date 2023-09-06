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
            Hm_Msgs::add('ERRTracker field not found');
            return;
        }

        $item = $trk->get_item_info($itemId);
        if (! $item) {
            Hm_Msgs::add('ERRTracker item not found');
            return;
        }
        $item[$field['fieldId']] = $trk->get_item_value(null, $item['itemId'], $field['fieldId']);

        $handler = $trk->get_field_handler($field, $item);
        $data = $handler->getFieldData();

        if (! isset($data['emails']) || ! is_array($data['emails'])) {
            Hm_Msgs::add('ERRTracker field storage is broken or you are using the wrong field type');
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
                $email['show_archive'] = $handler->getOption('useFolders') && $folder != 'archive';
                break;
            }
        }

        if (! $email) {
            Hm_Msgs::add('ERREmail not found in related tracker item');
            return;
        }

        if (empty($email['message_raw'])) {
            Hm_Msgs::add('ERREmail could not be parsed');
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
        $result = [$part_num => [
        'type' => $content_type[0],
        'subtype' => $content_type[1],
        'attributes' => $attributes,
        "id" => $part->getContentId(),
        'description' => false,
        'encoding' => $part->getContentTransferEncoding(),
        'size' => strlen($part->getContent()),
        'lines' => $part->isTextPart() ? substr_count($part->getContent(), "\n") : false,
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
        $cache = Hm_IMAP_List::get_cache($hm_cache, $dest_path[1]);
        $dest_imap = Hm_IMAP_List::connect($dest_path[1], $cache);
        if ($dest_imap) {
            $file = Tiki\FileGallery\File::id($email['fileId']);
            $msg = $file->getContents();
            if ($dest_imap->append_start(hex2bin($dest_path[2]), strlen($msg), true)) {
                $dest_imap->append_feed($msg . "\r\n");
                if ($dest_imap->append_end()) {
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
            Hm_Msgs::add('ERRCould not use the configured SMTP server');
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
        if (! smtp_authed($smtp)) {
            Hm_Msgs::add("ERRFailed to authenticate to the SMTP server");
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
            Hm_Msgs::add("ERRNo valid receipts found");
            return false;
        }

        // send the message
        $err_msg = $smtp->send_message($from, $recipients, $mime->get_mime_msg());
        if ($err_msg) {
            Hm_Msgs::add(sprintf("ERR%s", $err_msg));
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
    function tiki_move_to_tracker_dropdown($mod)
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
                    $folder_list[] = "<div><a href='#' class='object_selector_trigger' data-tracker='{$field['trackerId']}' data-field='{$field['fieldId']}' data-folder='" . htmlspecialchars($folder) . "'>{$folderName}</a></div>";
                }
                $field_list[] = "<a href='#' class='tiki_folder_trigger'>{$tracker['name']} - {$field['name']}</a>"
                    . "<div class='tiki_folder_container' style='display: none'>" . implode("\n", $folder_list) . "</div>";
            } else {
                $field_list[] = "<a href='#' class='object_selector_trigger' data-tracker='{$field['trackerId']}' data-field='{$field['fieldId']}' data-folder='inbox'>{$tracker['name']} - {$field['name']}</a>";
            }
        }
        $res = "<a class=\"hlink\" id=\"move_to_trackers\" href=\"#\">" . $mod->trans('Trackers') . "</a>";
        $res .= "<div class='move_to_trackers'><div class='move_to_title'>Move to trackers...<span><a class='close_move_to_trackers' href='#'>X</a></span></div>" . implode("<br>\n", $field_list) . "</div>";

        return $res;
    }
}

/**
 * @subpackage tiki/functions
 * @return string array message and headers
 */
if (! hm_exists('get_message_data')) {
    function get_message_data($imap, $msg_id)
    {
        $msg = $imap->get_message_content($msg_id, 0);
        $msg = str_replace("\r\n", "\n", $msg);
        $msg = str_replace("\n", "\r\n", $msg);
        $msg = rtrim($msg) . "\r\n";

        $headers = $imap->get_message_headers($msg_id);
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
    function bind_tracker_item_update_event($imap, $form, $msg_ids)
    {
        TikiLib::events()->bind('tiki.trackeritem.update', function ($args) {
            $imap = $args['imap'];
            $form = $args['form'];
            $old = $args['old_values'][$form['tracker_field_id']];
            $new = $args['values'][$form['tracker_field_id']];
            if (substr_count($old, ',') != substr_count($new, ',')) {
                $imap->message_action('DELETE', $args['msg_ids']);
                $imap->message_action('EXPUNGE', $args['msg_ids']);
            }
        }, ['imap' => $imap, 'form' => $form, 'msg_ids' => $msg_ids]);
    }
}
