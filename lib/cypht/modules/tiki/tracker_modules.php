<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Tiki tracker modules
 * @package modules
 * @subpackage tiki
 */
if (! defined('DEBUG_MODE')) {
    die();
}

/**
 * Prepare message page title
 * @subpackage tiki/handler
 */
class Hm_Handler_tracker_message_list_type extends Hm_Handler_Module
{
    public $request;
    public function process()
    {
        $path = $this->request->get['list_path'];
        if (! str_contains($path, 'tracker_folder_')) {
            return;
        }
        $title = ['Tracker Folder'];
        $trackerId = str_replace('tracker_', '', $this->request->get['list_parent']);
        $path = str_replace('tracker_folder_', '', $this->request->get['list_path']);
        list ($itemId, $fieldId) = explode('_', $path);
        $definition = Tracker_Definition::get($trackerId);
        if ($definition) {
            $title[] = $definition->getConfiguration('name');
            $field = $definition->getField($fieldId);
            if ($field) {
                $title[] = $field['name'];
            }
            if ($itemId) {
                $title[] = TikiLib::lib('trk')->get_isMain_value($trackerId, $itemId);
            }
        }
        $this->out('mailbox_list_title', $title);
    }
}

/**
 * Check for tracker item link redirect on message list page
 * @subpackage tiki/handler
 */
class Hm_Handler_check_path_redirect extends Hm_Handler_Module
{
    public $request;
    public function process()
    {
        $path = $this->request->get['list_path'];
        if (preg_match("/tracker_folder_(\d+)_(\d+)/", $path, $m)) {
            $url = smarty_modifier_sefurl($m[1], 'trackeritem');
            Hm_Dispatch::page_redirect($url);
        }
    }
}

/**
 * Check for tracker item link redirect after compose msg is sent
 * @subpackage tiki/handler
 */
class Hm_Handler_check_path_redirect_after_sent extends Hm_Handler_Module
{
    public $request;
    public function process()
    {
        if (! $this->get('msg_sent')) {
            return;
        }
        $path = $this->request->post['compose_msg_path'];
        if (preg_match("/tracker_folder_(\d+)_(\d+)/", $path, $m)) {
            $url = smarty_modifier_sefurl($m[1], 'trackeritem');
            Hm_Dispatch::page_redirect($url);
        }
    }
}

/**
 * Move an email to a tracker Email Folder field
 * @subpackage tiki/handler
 */
class Hm_Handler_move_to_tracker extends Hm_Handler_Module
{
    public $session;
    public function process()
    {
        list($success, $form) = $this->process_form(['tracker_field_id', 'tracker_item_id', 'imap_msg_ids', 'list_path', 'folder']);
        if (! $success) {
            return;
        }

        $msg_ids = explode(',', $form['imap_msg_ids']);
        $errors = 0;
        $msgs = [];
        $headers_array = [];

        if (preg_match("/^imap_(\w+)_(.+)/", $form['list_path'], $matches)) {
            $imap_server_id = $matches[1];
            $folder = hex2bin($matches[2]);
            $mailbox = Hm_IMAP_List::get_connected_mailbox($imap_server_id, $this->cache);
            if (! $mailbox->authed()) {
                Hm_Msgs::add(tr('Could not authenticate with mail server'), 'danger');
                return;
            }

            foreach ($msg_ids as $msg_id) {
                list($msgs[], $headers_array[]) = get_message_data($mailbox, $folder, $msg_id);
            }

            bind_tracker_item_update_event($mailbox, $folder, $form, $msg_ids);
        } elseif (preg_match("/^tracker_folder_/", $form['list_path'], $matches)) {
            $email = tiki_parse_message($form['list_path'], $msg_ids[0]);
            if (! $email) {
                Hm_Msgs::add(tr('Message could not be loaded'), 'danger');
                return;
            }
            if (isset($form['folder']) && $form['folder'] != 'archive') {
                tiki_flag_message($email['fileId'], 'remove', 'archive');
            }
            if (isset($form['folder']) && $form['folder'] != 'trash') {
                tiki_flag_message($email['fileId'], 'remove', 'deleted');
            }
            $file = Tiki\FileGallery\File::id($email['fileId']);
            $msg = $file->getContents();

            $headers = [
                'Message-ID' => $email['message_id'],
                'Subject' => $email['subject'],
            ];

            $msgs[] = $msg;
            $headers_array[] = $headers;

            // ensure file was saved before removing it from Tiki
            TikiLib::events()->bind('tiki.trackeritem.update', function ($args) {
                $email = $args['email'];
                $trk = TikiLib::lib('trk');
                $field = $trk->get_field_info($email['fieldId']);
                if (! $field) {
                    return;
                }
                static $called = false;
                if (! $called) {
                    $called = true;
                    $field['value'] = [
                        'delete' => $email['fileId'],
                        'skip_trash' => true
                    ];
                    $trk->replace_item($email['trackerId'], $email['itemId'], [
                        'data' => [$field]
                    ]);
                }
            }, ['email' => $email]);
        } elseif (in_array($form['list_path'], ['sent', 'unread', 'combined_inbox', 'flagged', 'search'])) {
            $imaps = [];
            $ids = [];
            foreach ($msg_ids as $msg_id) {
                $full_path = explode('_', $msg_id);
                $imap_server_id = $full_path[1];
                $folder = hex2bin($full_path[3]);

                if (! in_array($imap_server_id, array_keys($imaps))) {
                    $mailbox = Hm_IMAP_List::get_connected_mailbox($imap_server_id, $this->cache);

                    if (! $mailbox->authed()) {
                        $errors++;
                        continue;
                    }

                    $imaps[$imap_server_id] = $mailbox;
                }

                $mailbox = $imaps[$imap_server_id];

                list($msgs[], $headers_array[]) = get_message_data($mailbox, $folder, $full_path[2]);
                $ids[] = $full_path[2];
            }
            if (count($ids)) {
                bind_tracker_item_update_event($mailbox, $folder, $form, $ids);
            }
        } else {
            Hm_Msgs::add(tr('Message from this source could not be moved'), 'danger');
            return;
        }

        $trk = TikiLib::lib('trk');
        $item = $trk->get_item_info($form['tracker_item_id']);

        if (! $item) {
            Hm_Msgs::add(tr('Tracker item not found'), 'danger');
            return;
        }

        $field = $trk->get_field_info($form['tracker_field_id']);
        if (! $field) {
            Hm_Msgs::add(tr('Tracker field not found'), 'danger');
            return;
        }

        $new_values = [];
        foreach ($msgs as $key => $msg) {
            $headers = $headers_array[$key];
            $content = '';
            if (isset($this->request->post['auto_move']) && $this->request->post['auto_move']) {
                $content .= "X-Auto-Move-Reply: 1\r\n";
            }
            if ($this->session->get('page_id')) {
                $content .= "X-Tiki-Source: " . $this->session->get('page_id') . "\r\n";
            }
            $content .= $msg;

            $new_values[] = [
                'name' => ! empty($headers['Message-ID']) ? $headers['Message-ID'] : $headers['Subject'],
                'size' => strlen($content),
                'type' => 'message/rfc822',
                'content' => $content
            ];
        }

        $field['value'] = [
            'new' => $new_values,
            'folder' => $form['folder'] ?? 'inbox'
        ];

        $trk->replace_item($item['trackerId'], $item['itemId'], [
            'data' => [$field]
        ]);

        $total_msg_ids = count($msg_ids);
        if ($errors > 0 && $errors < $total_msg_ids) {
            Hm_Msgs::add(tr('Some messages moved'));
        } elseif ($total_msg_ids == $errors) {
            Hm_Msgs::add(tr('Unable to move/copy selected messages'), 'danger');
            return;
        } else {
            Hm_Msgs::add(tr('Messages moved'));
        }
    }
}

class Hm_Handler_auto_move_reply_to_tracker extends Hm_Handler_Module
{
    public function process()
    {
        if (! array_key_exists('in_reply_to', $this->request->post) || ! $this->request->post['in_reply_to']) {
            return;
        }

        $inReplyTo = $this->request->post['in_reply_to'];
        $items = find_relevant_tracker_items($inReplyTo, 'message_ids');
        $item = reset($items);

        if (! $item) {
            return;
        }

        $trackerDefinition = Tracker_Definition::get($item['parent_id']);
        $itemdata = Tracker_Item::fromId($item['object_id'])->getData();
        $emailFieldHandler = $trackerDefinition->getFieldFactory()->getHandler($trackerDefinition->getField($item['field_id']), $itemdata);
        $values = $emailFieldHandler->getFieldData();

        if (in_array($inReplyTo, $values['autoMoveReplies'])) {
            $this->out('auto_move', true);
            $this->out('item_id', $item['object_id']);
            $this->out('field_id', $item['field_id']);
        } else {
            $this->out('auto_move', false);
        }
    }
}

/**
 * Marks a Tiki-stored message as answered
 * @subpackage tiki/handler
 */
class Hm_Handler_tiki_mark_as_answered extends Hm_Handler_Module
{
    public $request;
    public function process()
    {
        if (! $this->get('msg_sent')) {
            return;
        }

        $path = $this->request->post['compose_msg_path'];
        if (! str_contains($path, 'tracker_folder_')) {
            return;
        }

        if (! empty($this->request->post['compose_msg_uid'])) {
            $uid = $this->request->post['compose_msg_uid'];
            Hm_Msgs::add('Uid - ' . $uid);
            tiki_flag_message($uid, 'add', 'answered');
        }
    }
}

/**
 * Prevents saving the message to IMAP if message was sent from tracker
 * @subpackage tiki/handler
 */
class Hm_Handler_tiki_presave_sent extends Hm_Handler_Module
{
    public function process()
    {
        if (! $this->get('save_sent_msg')) {
            return;
        }

        $path = $this->request->post['compose_msg_path'];
        if (str_contains($path, 'tracker_folder_')) {
            $this->out('save_sent_server', false);
        }
    }
}

/**
 * Save a sent message to EmailFolder field
 * @subpackage tiki/handler
 */
class Hm_Handler_tiki_save_sent extends Hm_Handler_Module
{
    public $request;
    public function process()
    {
        if (! $this->get('save_sent_msg')) {
            return;
        }
        $mime = $this->get('save_sent_msg');
        $msg = "Flags: \Seen\r\n" . $mime->get_mime_msg();
        $headers = $mime->get_headers();

        $path = $this->request->post['compose_msg_path'];
        if (! str_contains($path, 'tracker_folder_')) {
            return;
        }
        $path = str_replace('tracker_folder_', '', $path);
        list ($itemId, $fieldId) = explode('_', $path);

        $trk = TikiLib::lib('trk');
        $item = $trk->get_item_info($itemId);
        if (! $item) {
            Hm_Msgs::add(tr('Tracker item not found'), 'danger');
            return;
        }
        $field = $trk->get_field_info($fieldId);
        if (! $field) {
            Hm_Msgs::add(tr('Tracker field not found'), 'danger');
            return;
        }
        $field['value'] = [
            'folder' => 'sent',
            'new' => [
                'name' => ! empty($headers['Message-Id']) ? $headers['Message-Id'] : $headers['Subject'],
                'size' => strlen($msg),
                'type' => 'message/rfc822',
                'content' => $msg
            ]
        ];
        $trk->replace_item($item['trackerId'], $item['itemId'], [
            'data' => [$field]
        ]);
    }
}

/**
 * Delete a draft message from EmailFolder field when successfully sent
 * @subpackage tiki/handler
 */
class Hm_Handler_tiki_delete_draft extends Hm_Handler_Module
{
    public $request;
    public function process()
    {
        if (! $this->get('msg_sent')) {
            return;
        }

        $path = $this->request->post['compose_msg_path'];
        if (! str_contains($path, 'tracker_folder_')) {
            return;
        }

        $draftId = $this->request->post['draft_id'];
        if (! $draftId) {
            return;
        }

        $path = str_replace('tracker_folder_', '', $path);
        list ($itemId, $fieldId) = explode('_', $path);

        $trk = TikiLib::lib('trk');
        $item = $trk->get_item_info($itemId);
        if (! $item) {
            Hm_Msgs::add(tr('Could not delete the saved draft. (Tracker item was not found)'), 'danger');
            return;
        }
        $field = $trk->get_field_info($fieldId);
        if (! $field) {
            Hm_Msgs::add(tr('Could not delete the saved draft. (Tracker field not found)'), 'danger');
            return;
        }
        $field['value'] = [
            'folder' => 'draft',
            'delete' => $draftId,
            'skip_trash' => true
        ];
        $trk->replace_item($item['trackerId'], $item['itemId'], [
            'data' => [$field]
        ]);
    }
}

/**
 * Prevent saving a draft message to IMAP if it originated from a tracker
 * @subpackage tiki/handler
 */
class Hm_Handler_tiki_presave_draft extends Hm_Handler_Module
{
    public function process()
    {
        if (! array_key_exists('list_path', $this->request->get)) {
            return;
        }
        $path = $this->request->get['list_path'];
        if (str_contains($path, 'tracker_folder_')) {
            $path = str_replace('tracker_folder_', '', $path);
            list ($itemId, $fieldId) = explode('_', $path);

            $trk = TikiLib::lib('trk');

            $item = $trk->get_item_info($itemId);
            if (! $item) {
                Hm_Msgs::add(tr('Could not save draft to tracker item because the item was not found. id: ') . $itemId, 'warning');
                return;
            }

            $field = $trk->get_field_info($fieldId);
            if (! $field) {
                Hm_Msgs::add(tr('Could not save draft to tracker item because the field was not found.'), 'warning');
                return;
            }

            $fieldHandler = Tracker_Definition::get($item['trackerId'])->getFieldFactory()->getHandler($field);

            if (! $fieldHandler->getTrackerFieldInstance()->getOption('useFolders')) {
                Hm_Msgs::add(tr('Could not save draft to tracker item because folders are not configured for the current field.'), 'warning');
                return;
            }
            $this->out('save_draft_to_imap', false);
        }
    }
}

/**
 * Save a draft message to EmailFolder field
 * @subpackage tiki/handler
 */
class Hm_Handler_tiki_save_draft extends Hm_Handler_Module
{
    public function process()
    {
        $mime = $this->get('draft_mime');
        if (! $mime) {
            return;
        }

        $path = $this->request->get['list_path'];
        if (! str_contains($path, 'tracker_folder_')) {
            return;
        }
        $path = str_replace('tracker_folder_', '', $path);
        list ($itemId, $fieldId) = explode('_', $path);

        $trk = TikiLib::lib('trk');
        $item = $trk->get_item_info($itemId);
        if (! $item) {
            Hm_Msgs::add(tr('Tracker item not found'), 'danger');
            return;
        }
        $field = $trk->get_field_info($fieldId);
        if (! $field) {
            Hm_Msgs::add(tr('Tracker field not found'), 'danger');
            return;
        }

        $fieldHandler = Tracker_Definition::get($item['trackerId'])->getFieldFactory()->getHandler($field);
        if (! $fieldHandler->getTrackerFieldInstance()->getOption('useFolders')) {
            Hm_Msgs::add(tr('Configure folders for this tracker field to save drafts'), 'danger');
            return;
        }

        $fileId = $this->request->post['draft_id'];

        $headers = $mime->get_headers();
        $msg = "Flags: \Draft\r\n" . $mime->get_mime_msg();

        $fileValue = [
            'name' => ! empty($headers['Message-Id']) ? $headers['Message-Id'] : $headers['Subject'],
            'size' => strlen($msg),
            'type' => 'message/rfc822',
            'content' => $msg
        ];

        if ($fileId) {
            $fileValue['fileId'] = $fileId;
            $action = 'replace';
        } else {
            $action = 'new';
        }

        $field['value'] = [
            'folder' => 'draft',
            $action => $fileValue
        ];

        TikiLib::events()->bind('tiki.trackeritem.update', function ($args) use ($fieldId) {
            $drafts = json_decode($args['values'][$fieldId])->draft;
            $currentDraft = end($drafts);
            $this->out('draft_id', $currentDraft);
        });

        $trk->replace_item($item['trackerId'], $item['itemId'], [
            'data' => [$field]
        ]);

        if ($this->request->post['draft_notice']) {
            Hm_Msgs::add(tr('Draft saved'));
        }
    }
}

class Hm_Handler_tiki_compose_from_draft extends Hm_Handler_Module
{
    public function process()
    {
        $draftId = $this->request->get['draft_id'] ?? '';
        $path = $this->request->get['list_path'] ?? '';
        if (! str_contains($path, 'tracker_folder_') || ! $draftId) {
            return;
        }

        $path = str_replace('tracker_folder_', '', $path);
        $fieldId = explode('_', $path)[1];

        $trk = TikiLib::lib('trk');
        $field = $trk->get_field_info($fieldId);

        if (! $field) {
            Hm_Msgs::add(tr('Tracker field not found'), 'danger');
            return;
        }

        $fieldHandler = $trk->get_field_handler($field);
        $parsed_fields = $fieldHandler->getEmailFromFile($draftId);

        $draft = [];
        $draft['draft_to'] = $parsed_fields['recipient'];
        $draft['draft_subject'] = $parsed_fields['subject'];
        $draft['draft_body'] = $parsed_fields['body'];
        $draft['draft_cc'] = ($parsed_fields['message_raw'])->getHeader('Cc');
        $draft['draft_bcc'] = ($parsed_fields['message_raw'])->getHeader('Bcc');
        $this->out('compose_draft', $draft);
    }
}

/**
 * Archive a replied message
 * @subpackage tiki/handler
 */
class Hm_Handler_tiki_archive_replied extends Hm_Handler_Module
{
    public $request;
    public function process()
    {
        if (empty($this->request->post['tiki_archive_replied'])) {
            return;
        }

        $path = $this->request->post['compose_msg_path'];
        if (! str_contains($path, 'tracker_folder_')) {
            return;
        }
        $msg_uid = $this->request->post['compose_msg_uid'];
        if (! $msg_uid) {
            return;
        }

        tiki_flag_message($msg_uid, 'add', 'archive');
        tiki_flag_message($msg_uid, 'remove', 'deleted');

        $path = str_replace('tracker_folder_', '', $path);
        list ($itemId, $fieldId) = explode('_', $path);

        $trk = TikiLib::lib('trk');
        $item = $trk->get_item_info($itemId);
        if (! $item) {
            Hm_Msgs::add(tr('Tracker item not found'), 'danger');
            return;
        }

        $field = $trk->get_field_info($fieldId);
        if (! $field) {
            Hm_Msgs::add(tr('Tracker field not found'), 'danger');
            return;
        }

        $field['value'] = [
            'archive' => $msg_uid
        ];
        $trk->replace_item($item['trackerId'], $item['itemId'], [
            'data' => [$field]
        ]);
    }
}

/**
 * Delete a message
 * @subpackage tiki/handler
 */
class Hm_Handler_tiki_delete_message extends Hm_Handler_Module
{
    /**
     * Remove from the related EmailFolder field
     */
    public function process()
    {
        list($success, $form) = $this->process_form(['imap_msg_uid', 'list_path']);
        if ($success) {
            tiki_flag_message($form['imap_msg_uid'], 'add', 'deleted');
            tiki_flag_message($form['imap_msg_uid'], 'remove', 'archive');

            $path = str_replace('tracker_folder_', '', $form['list_path']);
            list ($itemId, $fieldId) = explode('_', $path);
            $trk = TikiLib::lib('trk');
            $item = $trk->get_item_info($itemId);
            if (! $item) {
                Hm_Msgs::add(tr('Tracker item not found'), 'danger');
                $this->out('delete_error', true);
                return;
            }
            $field = $trk->get_field_info($fieldId);
            if (! $field) {
                Hm_Msgs::add(tr('Tracker field not found'), 'danger');
                $this->out('delete_error', true);
                return;
            }
            $field['value'] = [
                'delete' => $form['imap_msg_uid']
            ];
            $trk->replace_item($item['trackerId'], $item['itemId'], [
                'data' => [$field]
            ]);
            Hm_Msgs::add(tr('Message deleted'));
            $this->out('delete_error', false);
        }
    }
}

/**
 * Archive a message
 * @subpackage tiki/handler
 */
class Hm_Handler_tiki_archive_message extends Hm_Handler_Module
{
    /**
     * Move from the related EmailFolder field to Archive folder
     */
    public function process()
    {
        list($success, $form) = $this->process_form(['imap_msg_uid', 'list_path']);
        if ($success) {
            tiki_flag_message($form['imap_msg_uid'], 'add', 'archive');
            tiki_flag_message($form['imap_msg_uid'], 'remove', 'deleted');

            $path = str_replace('tracker_folder_', '', $form['list_path']);
            list ($itemId, $fieldId) = explode('_', $path);
            $trk = TikiLib::lib('trk');
            $item = $trk->get_item_info($itemId);
            if (! $item) {
                Hm_Msgs::add(tr('Tracker item not found'), 'danger');
                $this->out('archive_error', true);
                return;
            }
            $field = $trk->get_field_info($fieldId);
            if (! $field) {
                Hm_Msgs::add(tr('Tracker field not found'), 'danger');
                $this->out('archive_error', true);
                return;
            }
            $field['value'] = [
                'archive' => $form['imap_msg_uid']
            ];
            $trk->replace_item($item['trackerId'], $item['itemId'], [
                'data' => [$field]
            ]);
            Hm_Msgs::add(tr('Message archived'));
            $this->out('archive_error', false);
        }
    }
}

/**
 * Flag a message
 * @subpackage tiki/handler
 */
class Hm_Handler_flag_tiki_message extends Hm_Handler_Module
{
    /**
     * Use IMAP to flag the selected message uid
     */
    public function process()
    {
        list($success, $form) = $this->process_form(['imap_msg_uid', 'list_path']);
        if ($success) {
            $email = tiki_parse_message($form['list_path'], $form['imap_msg_uid']);
            if (! $email) {
                return;
            }
            $flag_state = tiki_toggle_flag_message($email['fileId'], 'flagged');
            $this->out('flag_state', $flag_state);
            $this->out('show_archive', $email['show_archive']);
        }
    }
}

/**
 * Perform an action against a Tiki stored message
 * @subpackage imap/handler
 */
class Hm_Handler_tiki_message_action extends Hm_Handler_Module
{
    public function process()
    {
        list($success, $form) = $this->process_form(['action_type', 'imap_msg_uid', 'list_path']);
        if ($success) {
            $email = tiki_parse_message($form['list_path'], $form['imap_msg_uid']);
            if (! $email) {
                return;
            }
            switch ($form['action_type']) {
                case 'unread':
                    tiki_flag_message($email['fileId'], 'remove', 'seen');
                    break;
            }
        }
    }
}

/**
 * Get message content from Tiki tracker EmailField storage and prepare for imap module display
 * @subpackage tiki/handler
 */
class Hm_Handler_tiki_message_content extends Hm_Handler_Module
{
    public $request;
    public $config;
    public $user_config;
    public $session;
    public function process()
    {
        list($success, $form) = $this->process_form(['imap_msg_uid']);
        if (! $success) {
            return;
        }

        // Only process tracker-stored messages; skip regular IMAP messages
        $list_path = $this->request->post['list_path'] ?? '';
        if (strpos($list_path, 'tracker_folder_') !== 0) {
            return;
        }

        $this->out('header_allow_images', $this->config->get('allow_external_image_sources'));
        $this->out('images_whitelist', explode(',', $this->user_config->get('images_whitelist_setting')));

        $this->out('msg_text_uid', $form['imap_msg_uid']);
        $this->out('msg_list_path', $list_path);
        $part_num = false;
        if (isset($this->request->post['imap_msg_part']) && preg_match("/[0-9\.]+/", $this->request->post['imap_msg_part'])) {
            $part_num = $this->request->post['imap_msg_part'];
        }
        if (array_key_exists('imap_allow_images', $this->request->post) && $this->request->post['imap_allow_images']) {
            $this->out('imap_allow_images', true);
        }
        $this->out('header_allow_images', $this->config->get('allow_external_image_sources'));

        $email = tiki_parse_message($list_path, $form['imap_msg_uid']);
        if (! $email) {
            return;
        }

        tiki_flag_message($email['fileId'], 'add', 'seen');

        $message = $email['message_raw'];

        $msg_headers = [];
        foreach ($message->getAllHeaders() as $header) {
            if (function_exists('mb_decode_mimeheader')) {
                $msg_headers[$header->getName()] = mb_decode_mimeheader($header->getRawValue());
            } else {
                $msg_headers[$header->getName()] = $header->getRawValue();
            }
        }
        $msg_struct = tiki_mime_part_to_bodystructure($message);

        if ($part_num !== false) {
            $part = tiki_get_mime_part($message, $part_num);
        } else {
            if (! $this->user_config->get('text_only_setting', false)) {
                $part = $message->getHtmlPart();
                if (! $part) {
                    $part = $message->getTextPart();
                }
            } else {
                $part = $message->getTextPart();
            }
        }
        if ($part_num !== false && $part_num == 0) {
            $file = Tiki\FileGallery\File::id($email['fileId']);
            $msg_text = $file->getContents();
        } else {
            $msg_text = $part->getContent();
        }
        $list = explode('/', $part->getContentType());
        $msg_struct_current = [
            'type' => $list[0],
            'subtype' => $list[1],
        ];
        if ($msg_struct_current['subtype'] == 'html') {
            $msg_text = tiki_add_attached_images($message, $msg_text);
        }

        $this->out('msg_struct', $msg_struct);
        $this->out('list_headers', get_list_headers($msg_headers));
        $this->out('msg_headers', $msg_headers);
        $this->out('imap_msg_part', "$part_num");
        $this->out('use_message_part_icons', $this->user_config->get('msg_part_icons_setting', false));
        $this->out('simple_msg_part_view', $this->user_config->get('simple_msg_parts_setting', false));
        if ($msg_struct_current) {
            $this->out('msg_struct_current', $msg_struct_current);
        }
        $this->out('msg_text', $msg_text);
        $this->out('msg_download_args', sprintf("page=message&amp;uid=%s&amp;list_path=%s&amp;tiki_download_message=1", $form['imap_msg_uid'], $this->request->post['list_path']));
        $this->out('msg_show_args', sprintf("page=message&amp;uid=%s&amp;list_path=%s&amp;tiki_show_message=1", $form['imap_msg_uid'], $this->request->post['list_path']));
        if ($email['prev']) {
            $this->out('msg_prev_link', $this->build_page_url('message', ['uid' => $email['prev']['fileId'], 'list_path' => sprintf("tracker_folder_%s_%s", $email['prev']['itemId'], $email['prev']['fieldId']), 'list_parent' => sprintf("tracker_%s", $email['prev']['trackerId'])]));
            $this->out('msg_prev_subject', $email['prev']['subject']);
        }
        if ($email['next']) {
            $this->out('msg_next_link', $this->build_page_url('message', ['uid' => $email['next']['fileId'], 'list_path' => sprintf("tracker_folder_%s_%s", $email['next']['itemId'], $email['next']['fieldId']), 'list_parent' => sprintf("tracker_%s", $email['next']['trackerId'])]));
            $this->out('msg_next_subject', $email['next']['subject']);
        }
        $this->out('show_archive', $email['show_archive']);

        if (preg_match("/tracker_folder_(\d+)_(\d+)/", $this->request->post['list_path'], $m)) {
            $trklib = TikiLib::lib('trk');
            $uid = $this->request->get['uid'];
            $value = $trklib->get_item_value(null, $m[1], $m[2]);
            $value = json_decode($value, true);

            foreach ($value as $folder => $emailIds) {
                if (in_array($uid, $emailIds)) {
                    $this->out('msg_current_folder', $folder);
                    break;
                }
            }

            if ($email['is_tiki_tracker_trash_folder']) {
                $this->out('show_restore', true);
            }
        }

        clear_existing_reply_details($this->session);
        if ($part_num == 0) {
            $msg_struct_current['type'] = 'text';
            $msg_struct_current['subtype'] = 'plain';
        }
        $this->session->set(
            sprintf('reply_details_%s_%s', $this->request->post['list_path'], $form['imap_msg_uid']),
            ['ts' => time(), 'msg_struct' => $msg_struct_current, 'msg_text' => $msg_text, 'msg_headers' => $msg_headers]
        );
    }
}

/**
 * Retrieve a message part and show in the browser (mainly images)
 * @subpackage tiki/handler
 */
class Hm_Handler_tiki_download_message extends Hm_Handler_Module
{
    public $request;
    public function process()
    {
        $show = $download = false;
        if (array_key_exists('tiki_show_message', $this->request->get) && $this->request->get['tiki_show_message']) {
            $show = true;
        }
        if (array_key_exists('tiki_download_message', $this->request->get) && $this->request->get['tiki_download_message']) {
            $download = true;
        }
        if ($show || $download) {
            $uid = null;
            $list_path = null;
            $msg_id = null;
            if (array_key_exists('uid', $this->request->get) && $this->request->get['uid']) {
                $uid = $this->request->get['uid'];
            }
            if (array_key_exists('list_path', $this->request->get) && $this->request->get['list_path']) {
                $list_path = $this->request->get['list_path'];
            }
            if (array_key_exists('imap_msg_part', $this->request->get) && preg_match("/^[0-9\.]+$/", $this->request->get['imap_msg_part'])) {
                $msg_id = $this->request->get['imap_msg_part'];
            }
            if ($list_path !== null && $uid !== null && $msg_id !== null) {
                $email = tiki_parse_message($list_path, $uid);
                if (! $email) {
                    return;
                }

                $message = $email['message_raw'];
                $part = tiki_get_mime_part($message, $msg_id);

                if ($download) {
                    $struct = tiki_mime_part_to_bodystructure($part, "0");
                    $name = get_imap_part_name($struct[0], $uid, preg_replace("/^0\./", "", $msg_id));
                    header('Content-Disposition: attachment; filename="' . $name . '"');
                }

                header('Content-Type: ' . $part->getContentType());
                header('Content-Transfer-Encoding: binary');
                ob_end_clean();
                echo $part->getContent();
                Hm_Functions::cease();
            }
            Hm_Msgs::add(tr('An Error occurred trying to download the message'), 'danger');
        }
    }
}

/**
 * Process a move/copy action
 * @subpackage tiki/handler
 */
class Hm_Handler_tiki_process_move extends Hm_Handler_Module
{
    public $cache;
    public function process()
    {
        list($success, $form) = $this->process_form(['imap_move_to', 'imap_move_action', 'imap_move_ids']);
        if ($success) {
            $moved = 0;
            $dest_path = explode('_', $form['imap_move_to']);
            $msg_ids = explode(',', $form['imap_move_ids']);
            foreach ($msg_ids as $msg_id) {
                list($list_path, $uid) = explode('#', $msg_id);
                $email = tiki_parse_message($list_path, $uid);
                if (! $email) {
                    continue;
                }
                $result = tiki_move_to_imap_server($email, $form['imap_move_action'], $dest_path, $this->cache);
                if ($result) {
                    $moved++;
                }
            }
            if ($moved == 0) {
                Hm_Msgs::add(tr('Unable to move/copy selected messages'), 'danger');
            } elseif ($form['imap_move_action'] == 'move') {
                Hm_Msgs::add($moved == 1 ? tr('Message moved') : $moved . ' ' . tr('messages moved'));
            } else {
                Hm_Msgs::add($moved == 1 ? tr('Message copied') : $moved . ' ' . tr('messages copied'));
            }
            $this->out('move_count', $moved);
        }
    }
}

/**
 * Tries to execute tiki sieve filters on unread messages
 * @subpackage tiki/handler
 */
class Hm_Handler_tiki_process_imap_unread extends Hm_Handler_Module
{
    public function process()
    {
        if (! $this->user_config->get('tiki_run_sieve_filters_setting', false)) {
            return;
        }

        $unreadMessages = $this->get('imap_unread_data');
        if (! $unreadMessages) {
            return;
        }

        $userPreferences = TikiLib::lib('tiki')->table('tiki_user_preferences');
        $configs = $userPreferences->fetchAll([], [
            'prefName' => $userPreferences->like('cypht_user_config%'),
        ]);
        foreach ($configs as $user_config) {
            $config = json_decode($user_config['value'], true);
            if (! isset($config['sieve_scripts'])) {
                continue;
            }

            foreach ($config['imap_servers'] as $idx => $mailbox) {
                if (! isset($config['sieve_scripts'][$mailbox['name']])) {
                    continue;
                }
                if (! isset($config['sieve_scripts'][$mailbox['name']])) {
                    continue;
                }
                $actions = [];
                foreach ($config['sieve_scripts'][$mailbox['name']] as $name => $script) {
                    if (str_contains($name, 'cyphtfilter')) {
                        $base64_obj = str_replace("# ", "", preg_split('#\r?\n#', $script, 0)[1]);
                        $conditions = json_decode(base64_decode($base64_obj), true);
                        $base64_obj = str_replace("# ", "", preg_split('#\r?\n#', $script, 0)[2]);
                        $actions = json_decode(base64_decode($base64_obj), true);
                        $operator = str_contains($script, 'allof') ? 'ALLOF' : 'ANYOF';
                        $filters[] = compact('conditions', 'actions', 'operator');
                    }
                }
                if (empty($filters)) {
                    continue;
                }
                $actions = array_filter($actions, function ($action) {
                    return in_array($action['action'], [
                        'movetotracker',
                        'movetooriginatingtrackerinbox'
                    ]);
                });
                if (! $actions) {
                    continue;
                }
                foreach ($unreadMessages as $key => $msg) {
                    if ($msg['server_id'] == $idx) {
                        // Delete the message
                        $mailbox = Hm_IMAP_List::get_connected_mailbox($msg['server_id'], $this->cache);
                        if ($mailbox->authed()) {
                            foreach ($filters as $filter) {
                                $filterData = Tiki_Hm_Functions::processFilter($filter, $mailbox, $msg);
                                if ($filterData['pass']) {
                                    $tempFolder = 'Tiki-Sieve-Rules-To-Be-Applied';
                                    if (! count($mailbox->folder_exists($tempFolder))) {
                                        $mailbox->create_folder($tempFolder);
                                    }
                                    // Move the message to temp folder
                                    $mailbox->message_action($tempFolder, 'MOVE', [$msg['uid']], $tempFolder);
                                }
                            }
                        }
                    }
                    unset($unreadMessages[$key]);
                }
            }
        }
    }
}


class Hm_Handler_tiki_restore_message extends Hm_Handler_Module
{
    /**
     * Restore message from archive or trash to inbox folder
     */
    public function process()
    {
        list($success, $form) = $this->process_form(['imap_msg_uid', 'list_path']);
        if ($success) {
            // Remove archive and deleted flags to restore to inbox
            tiki_flag_message($form['imap_msg_uid'], 'remove', 'archive');
            tiki_flag_message($form['imap_msg_uid'], 'remove', 'deleted');

            $path = str_replace('tracker_folder_', '', $form['list_path']);
            list ($itemId, $fieldId) = explode('_', $path);

            $trk = TikiLib::lib('trk');
            $item = $trk->get_item_info($itemId);
            if (! $item) {
                Hm_Msgs::add('Tracker item not found', 'danger');
                $this->out('restore_error', true);
                return;
            }

            $field = $trk->get_field_info($fieldId);
            if (! $field) {
                Hm_Msgs::add('Tracker field not found', 'danger');
                $this->out('restore_error', true);
                return;
            }

            // Move message back to inbox by updating the field value
            $field['value'] = [
                'restore' => $form['imap_msg_uid']
            ];

            $trk->replace_item($item['trackerId'], $item['itemId'], [
                'data' => [$field]
            ]);

            Hm_Msgs::add('Message restored to inbox');
            $this->out('restore_error', false);
        }
    }
}


/**
 * Add Move to trackers button
 * @subpackage tiki/output
 */
class Hm_Output_add_move_to_trackers extends Hm_Output_Module
{
    protected function output()
    {
        $res = tiki_move_to_tracker_dropdown($this, 'Trackers', 'Move to Tracker', 'move_to_trackers', true);
        $this->concat('extra_header_buttons', $res);
    }
}

class Hm_Output_tiki_get_create_item_trackers_output extends Hm_Output_Module
{
    public function output()
    {
        $res = tiki_move_to_tracker_dropdown($this, 'Create item', 'Select Tracker', 'item_to_trackers', true);
        $this->concat('extra_header_buttons', $res);
    }
}

/**
 * Add Restore Message button in tracker folders
 * @subpackage tiki/output
 */
class Hm_Output_add_restore_message extends Hm_Output_Module
{
    protected function output()
    {
        $res = tiki_restore_message($this);
        $this->concat('extra_header_buttons', $res);
    }
}

/**
 * Add move/copy dialog to the message list controls
 * @subpackage imap/output
 */
class Hm_Output_add_multiple_item_to_trackers extends Hm_Output_Module
{
    protected function output()
    {
        $res = tiki_move_to_tracker_dropdown($this, 'Create item', 'Select Tracker', 'item_to_trackers');
        $this->concat('msg_controls_extra', $res);
    }
}

/**
 * Add move/copy dialog to the message list controls
 * @subpackage imap/output
 */
class Hm_Output_add_multiple_move_to_trackers extends Hm_Output_Module
{
    protected function output()
    {
        $res = tiki_move_to_tracker_dropdown($this);
        $this->concat('msg_controls_extra', $res);
    }
}

/**
 * @subpackage tiki/handler
 */
class Hm_Handler_tiki_get_trackers extends Hm_Handler_Module
{
    public function process()
    {
    }
}


class Hm_Output_tiki_get_trackers_output extends Hm_Output_Module
{
    public function output()
    {
        $trackers = '<div id="trackers_dropdown">' . tiki_move_to_tracker_dropdown($this) . '</div>';
        $this->out('trackers', $trackers);
    }
}

/**
 * @subpackage tiki/handler
 */
class Hm_Handler_tiki_tracker_info extends Hm_Handler_Module
{
    public function process()
    {
        list($success, $form) = $this->process_form(['tracker_field_id', 'tracker_item_id', 'folder']);
        if (! $success) {
            return;
        }
        $folder_name = 'Inbox';
        $trklib = TikiLib::lib('trk');
        $objectlib = TikiLib::lib('object');
        $field_data = $trklib->get_field_info($form['tracker_field_id']);

        $item = $objectlib->get_title('trackeritem', $form['tracker_item_id']);
        $tracker = $objectlib->get_title('tracker', $field_data['trackerId']);
        $field = $objectlib->get_title('trackerfield', $form['tracker_field_id']);

        $handler = $trklib->get_field_handler($field_data);
        if ($handler->getOption('useFolders')) {
            $folder_name = $handler->getFolders()[$form['folder']];
        }

        $out = $tracker . ' - ' . $field . ' - ' . $item . ' (' . $folder_name . ')';

        $this->out('tracker_data', $out);
    }
}


class Hm_Output_tiki_tracker_info_output extends Hm_Output_Module
{
    public function output()
    {
        $this->out('tracker_data', $this->get('tracker_data'));
    }
}

/**
 * Forward handler vars to the frontend
 * @subpackage tiki/output
 */
class Hm_Output_forward_variables extends Hm_Output_Module
{
    protected function output()
    {
        $this->out('msg_prev_link', $this->get('msg_prev_link'));
        $this->out('msg_prev_subject', $this->get('msg_prev_subject'));
        $this->out('msg_next_link', $this->get('msg_next_link'));
        $this->out('msg_next_subject', $this->get('msg_next_subject'));
        $this->out('show_archive', $this->get('show_archive'));
        $this->out('flag_state', $this->get('flag_state'));
    }
}

/**
 * Pass redirect_url param to the output
 * @subpackage tiki/output
 */
class Hm_Output_pass_redirect_url extends Hm_Output_Module
{
    protected function output()
    {
        $url = $this->get('tiki_redirect_url');
        $this->out('tiki_redirect_url', $url);
    }
}

/**
 * Pass tracker target params from GET request into output
 * @subpackage tiki/handler
 */
class Hm_Handler_pass_tracker_target_params extends Hm_Handler_Module
{
    public function process()
    {
        $item_id = intval($this->request->get['target_tracker_item_id'] ?? 0);
        $field_id = intval($this->request->get['target_tracker_field_id'] ?? 0);
        $folder = isset($this->request->get['target_tracker_folder'])
            ? $this->request->get['target_tracker_folder']
            : 'inbox';

        $this->out('target_tracker_item_id', $item_id);
        $this->out('target_tracker_field_id', $field_id);
        $this->out('target_tracker_folder', $folder);
    }
}

/**
 * Add tracker target params as hidden inputs to the search form
 * @subpackage tiki/output
 */
class Hm_Output_search_form_tracker_target_inputs extends Hm_Output_Module
{
    protected function output()
    {
        $target_tracker_item_id = $this->get('target_tracker_item_id');
        $target_tracker_field_id = $this->get('target_tracker_field_id');
        $target_tracker_folder = $this->get('target_tracker_folder');

        if (! ($target_tracker_item_id && $target_tracker_field_id)) {
            return;
        }

        return '<input type="hidden" name="target_tracker_item_id" value="' . $target_tracker_item_id . '" />'
            . '<input type="hidden" name="target_tracker_field_id" value="' . $target_tracker_field_id . '" />'
            . '<input type="hidden" name="target_tracker_folder" value="' . $this->html_safe($target_tracker_folder) . '" />';
    }
}

/**
 * Append tracker target params to saved search folder URLs
 * Runs after Hm_Output_search_folders to inject tracker context into saved search links
 * @subpackage tiki/output
 */
class Hm_Output_append_tracker_params_to_searches extends Hm_Output_Module
{
    protected function output()
    {
        $target_tracker_item_id = $this->get('target_tracker_item_id', false);
        $target_tracker_field_id = $this->get('target_tracker_field_id', false);
        $target_tracker_folder = $this->get('target_tracker_folder', 'inbox');

        if (! ($target_tracker_item_id && $target_tracker_field_id)) {
            return;
        }

        $folder_sources = $this->get('folder_sources', []);
        if (! is_array($folder_sources) || empty($folder_sources)) {
            return;
        }

        foreach ($folder_sources as $key => &$source) {
            if (is_array($source) && count($source) >= 2 && $source[0] === 'search_folders') {
                $html = $source[1];

                $param_string = '&target_tracker_item_id=' . $target_tracker_item_id
                    . '&target_tracker_field_id=' . $target_tracker_field_id
                    . '&target_tracker_folder=' . urlencode($target_tracker_folder);

                // Replace href patterns in search links
                // Match: href="?page=search..."
                $html = preg_replace_callback(
                    '/(href=")([^"]*page=search[^"]*)(")/i',
                    function ($matches) use ($param_string) {
                        return $matches[1] . $matches[2] . $param_string . $matches[3];
                    },
                    $html
                );

                $source[1] = $html;
            }
        }

        $this->out('folder_sources', $folder_sources);
    }
}

/**
 * @subpackage smtp/handler
 */
class Hm_Handler_tiki_load_smtp_is_imap_forward extends Hm_Handler_Module
{
    public function process()
    {
        if (! $this->module_is_supported('imap')) {
            return;
        }

        if (array_key_exists('forward', $this->request->get)) {
            $list_path = $this->request->get['list_path'];
            if (! preg_match("/tracker_folder_(\d+)_(\d+)/", $list_path, $m)) {
                return;
            }
            $email = tiki_parse_message($list_path, $this->request->get['uid']);
            if (! $email) {
                return;
            }
            $message = $email['message_raw'];
            $attached_files = [];
            foreach ($message->getAllAttachmentParts() as $att) {
                $new_attachment['basename'] = $att->getFilename();
                $new_attachment['name'] = $att->getFilename();
                $new_attachment['type'] = $att->getContentType();
                $new_attachment['size'] = strlen($att->getContent());
                $file_path = $this->config->get('attachment_dir') . DIRECTORY_SEPARATOR . $new_attachment['name'];
                $content = Hm_Crypt::ciphertext($att->getContent(), Hm_Request_Key::generate());
                file_put_contents($file_path, $content);
                $new_attachment['tmp_name'] = $file_path;
                $new_attachment['filename'] = $file_path;
                $attached_files[$this->request->get['uid']][] = $new_attachment;
            }
            $this->session->set('uploaded_files', $attached_files);
        }
    }
}


/**
 * Process input from the max per source setting for the Junk page in the settings page
 * @subpackage core/handler
 */
class Hm_Handler_process_trackers_setting extends Hm_Handler_Module
{
    /**
     * Allowed values are greater than zero and less than MAX_PER_SOURCE
     */
    public function process()
    {
        process_site_setting('trackers_per_source', $this, 'max_source_setting_callback', DEFAULT_PER_SOURCE);
        process_site_setting('trackers_since', $this, 'since_setting_callback');
    }
}

/**
 * Starts the Trash section on the settings page
 * @subpackage core/output
 */
class Hm_Output_start_trackers_settings extends Hm_Output_Module
{
    /**
     * Settings in this section control the trackers view
     */
    protected function output()
    {
        return '<tr><td data-target=".trackers_setting" colspan="2" class="settings_subtitle cursor-pointer">' .
            '<i class="bi bi-database fs-5 me-2"></i>' .
            $this->trans('Trackers') . '</td></tr>';
    }
}

/**
 * Option for the maximum number of messages per source for the Trackers page
 * @subpackage core/output
 */
class Hm_Output_trackers_source_max_setting extends Hm_Output_Module
{
    /**
     * Processed by Hm_Handler_process_trackers_source_max_setting
     */
    protected function output()
    {
        $sources = DEFAULT_PER_SOURCE;
        $settings = $this->get('user_settings', []);
        $reset = '';
        if (array_key_exists('trackers_per_source', $settings)) {
            $sources = $settings['trackers_per_source'];
        }
        if ($sources != 20) {
            $reset = '<span class="tooltip_restore" restore_aria_label="Restore default value"><i class="bi bi-arrow-counterclockwise refresh_list reset_default_value_input"></i></span>';
        }
        return '<tr class="trackers_setting"><td><label for="trackers_per_source">' .
            $this->trans('Max messages per source') . '</label></td>' .
            '<td><input type="text" class="form-control form-control-sm w-auto" size="2" id="trackers_per_source" name="trackers_per_source" value="' . $this->html_safe($sources) . '" data-default-value="' . DEFAULT_PER_SOURCE . '" />' . $reset . '</td></tr>';
    }
}

/**
 * Option for the "since" date range for the Trackers page
 * @subpackage core/output
 */
class Hm_Output_trackers_since_setting extends Hm_Output_Module
{
    /**
     * Processed by Hm_Handler_process_trackers_since_setting
     */
    protected function output()
    {
        $since = DEFAULT_SINCE;
        $settings = $this->get('user_settings', []);
        if (array_key_exists('trackers_since', $settings) && $settings['trackers_since']) {
            $since = $settings['trackers_since'];
        }
        return '<tr class="trackers_setting"><td><label for="trackers_since">' .
            $this->trans('Show trackers messages since') . '</label></td>' .
            '<td>' . message_since_dropdown($since, 'trackers_since', $this) . '</td></tr>';
    }
}

/**
 * Add trackers link to main menu
 * @subpackage tiki/handler
 */
class Hm_Output_combined_trackers_link extends Hm_Output_Module
{
    protected function output()
    {
        $res = '<li class="menu_combined_trackers"><a class="unread_link" href="' . $this->build_page_url('message_list', ['list_path' => 'trackers']) . '">';
        if (! $this->get('hide_folder_icons')) {
            $res .= '<i class="bi bi-database account_icon"></i> ';
        }
        $res .= $this->trans('Trackers') . '</a></li>';

        if ($this->format == 'HTML5') {
            return $res;
        }
        $this->concat('formatted_folder_list', $res);
    }
}

/**
 * Load IMAP servers for message list page
 * @subpackage tiki/handler
 */
class Hm_Handler_load_trackers_data_sources extends Hm_Handler_Module
{
    public $user_config;
    /**
     * Used by trackers view
     */
    public function process()
    {
        if (array_key_exists('list_path', $this->request->get) && $this->request->get['list_path'] == 'trackers') {
            $limit = $this->user_config->get('trackers_per_source_setting', DEFAULT_PER_SOURCE);
            $since = $this->user_config->get('trackers_since_setting', DEFAULT_SINCE);

            $this->out('data_sources', [['hook' => 'ajax_tiki_trackers', 'type' => 'custom', 'params' => []]]);
            $this->out('list_path', 'trackers');
            $this->out('mailbox_list_title', ['All Tracker items emails']);
            $this->out('message_list_since', $since);
            $this->out('per_source_limit', $limit);
        }
    }
}

/**
 * Fetch messages for the Groupmail page
 * @subpackage tiki/handler
 */
class Hm_Handler_trackers_fetch_messages extends Hm_Handler_Module
{
    public $user_config;
    public $request;
    public $session;
    public $config;
    /**
     * Returns all messages for trackers
     */
    public function process()
    {
        if (array_key_exists('list_path', $this->request->get) && $this->request->get['list_path'] == 'trackers') {
            $limit = $this->user_config->get('trackers_per_source_setting', DEFAULT_PER_SOURCE);
            $since = $this->user_config->get('trackers_since_setting', DEFAULT_SINCE);
            $keyword = $this->request->get['keyword'] ?? '';

            $msg_list = tiki_fetch_tracker_messages('inbox', $limit, $since, $keyword);

            $this->out('trackers_message_list_data', $msg_list);
        }
    }
}

/**
 * Format message headers for the trackers page
 * @subpackage imap/output
 */
class Hm_Output_filter_trackers_data extends Hm_Output_Module
{
    /**
     * Build ajax response for the trackers message list
     */
    protected function output()
    {
        if ($this->get('trackers_message_list_data')) {
            prepare_tracker_message_list($this->get('trackers_message_list_data'), $this, $this->get('list_path'));
        } else {
            $this->out('formatted_message_list', []);
        }
    }
}
