<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (! defined('DEBUG_MODE')) {
    die();
}

handler_source('tiki');
output_source('tiki');

/* groupmail page */
setup_base_page('groupmail', 'core');
add_handler('groupmail', 'load_data_sources', true, 'tiki', 'message_list_type', 'after');
add_output('groupmail', 'groupmail_heading', true, 'tiki', 'content_section_start', 'after');
add_output('groupmail', 'groupmail_start', true, 'tiki', 'groupmail_heading', 'after');
add_output('groupmail', 'groupmail_end', true, 'tiki', 'groupmail_start', 'after');

/* folder list update ajax request */
add_handler('ajax_hm_folders', 'check_groupmail_setting', true, 'tiki', 'load_user_data', 'after');
add_output('ajax_hm_folders', 'groupmail_page_link', true, 'tiki', 'logout_menu_item', 'before');
add_output('ajax_hm_folders', 'clear_cache_link', true, 'tiki', 'folder_list_content_end', 'before');

/* ajax groupmail callback data */
setup_base_ajax_page('ajax_tiki_groupmail', 'imap');
add_handler('ajax_tiki_groupmail', 'prepare_groupmail_settings', true, 'imap', 'load_user_data', 'after');
add_handler('ajax_tiki_groupmail', 'load_imap_servers_from_config', true, 'imap');
add_handler('ajax_tiki_groupmail', 'imap_oauth2_token_check', true, 'imap');
add_handler('ajax_tiki_groupmail', 'close_session_early', true, 'core');
add_handler('ajax_tiki_groupmail', 'groupmail_fetch_messages', true);
add_handler('ajax_tiki_groupmail', 'save_imap_cache', true);
add_output('ajax_tiki_groupmail', 'filter_groupmail_data', true);

/* ajax take groupmail */
setup_base_ajax_page('ajax_take_groupmail', 'core');
add_handler('ajax_take_groupmail', 'prepare_groupmail_settings', true, 'tiki', 'load_user_data', 'after');
add_handler('ajax_take_groupmail', 'load_imap_servers_from_config', true, 'imap');
add_handler('ajax_take_groupmail', 'take_groupmail', true, 'tiki');
add_output('ajax_take_groupmail', 'take_groupmail_response', true);

/* ajax put back groupmail */
setup_base_ajax_page('ajax_put_back_groupmail', 'core');
add_handler('ajax_put_back_groupmail', 'prepare_groupmail_settings', true, 'tiki', 'load_user_data', 'after');
add_handler('ajax_put_back_groupmail', 'load_imap_servers_from_config', true, 'imap');
add_handler('ajax_put_back_groupmail', 'put_back_groupmail', true, 'tiki');
add_output('ajax_put_back_groupmail', 'put_back_groupmail_response', true);

/* tiki contacts store */
add_handler('contacts', 'load_tiki_contacts', true, 'tiki', 'load_contacts', 'after');
add_handler('ajax_autocomplete_contact', 'load_tiki_contacts', true, 'tiki', 'load_contacts', 'after');
add_handler('ajax_imap_message_content', 'load_tiki_contacts', true, 'tiki', 'load_contacts', 'after');
add_handler('ajax_tiki_message_content', 'load_contacts', true, 'contacts', 'load_user_data', 'after');
add_handler('ajax_tiki_message_content', 'load_tiki_contacts', true, 'tiki', 'load_contacts', 'after');
add_handler('compose', 'load_tiki_contacts', true, 'tiki', 'load_contacts', 'after');
add_handler('ajax_delete_contact', 'load_tiki_contacts', true, 'tiki', 'load_contacts', 'after');
add_handler('ajax_add_contact', 'load_tiki_contacts', true, 'tiki', 'load_contacts', 'after');
add_output('ajax_hm_folders', 'tiki_contacts_page_link', true, 'tiki', 'logout_menu_item', 'before');

/* compose page handlers */
add_handler('compose', 'check_for_tiki_redirect', true, 'smtp', 'process_compose_form_submit', 'after');
add_handler('compose', 'add_file_attachment', true, 'smtp', 'load_smtp_servers_from_config', 'before');
add_handler('compose', 'tiki_load_smtp_is_imap_forward', true, 'smtp', 'load_smtp_is_imap_forward', 'after');

/* message page calendar invitation hooks */
add_handler('ajax_imap_message_content', 'check_calendar_invitations_imap', true, 'imap', 'imap_message_content', 'after');
add_output('ajax_imap_message_content', 'add_rsvp_actions', true, 'imap', 'filter_message_headers', 'after');
add_output('ajax_imap_message_content', 'filter_message_headers_mpdf', true, 'imap', 'add_rsvp_actions', 'after');

/* message page rsvp actions to an event */
setup_base_ajax_page('ajax_rsvp_action', 'core');
add_handler('ajax_rsvp_action', 'check_calendar_invitations_imap', true, 'imap', 'imap_message_content', 'after');
add_handler('ajax_rsvp_action', 'load_imap_servers_from_config', true, 'imap');
add_handler('ajax_rsvp_action', 'load_smtp_servers_from_config', true, 'smtp', 'load_imap_servers_from_config', 'after');
add_handler('ajax_rsvp_action', 'add_smtp_servers_to_page_data', true, 'smtp', 'load_smtp_servers_from_config', 'after');
add_handler('ajax_rsvp_action', 'compose_profile_data', true, 'profiles', 'add_smtp_servers_to_page_data', 'after');
add_handler('ajax_rsvp_action', 'imap_message_content', true, 'imap', 'compose_profile_data', 'after');
add_handler('ajax_rsvp_action', 'event_rsvp_action', true, 'tiki', 'imap_message_content', 'after');

/* message page add to calendar function */
setup_base_ajax_page('ajax_add_to_calendar', 'core');
add_handler('ajax_add_to_calendar', 'check_calendar_invitations_imap', true, 'imap', 'imap_message_content', 'after');
add_handler('ajax_add_to_calendar', 'load_imap_servers_from_config', true, 'imap');
add_handler('ajax_add_to_calendar', 'imap_message_content', true, 'imap', 'load_imap_servers_from_config', 'after');
add_handler('ajax_add_to_calendar', 'add_to_calendar', true, 'tiki', 'imap_message_content', 'after');

/* message page update in calendar function */
setup_base_ajax_page('ajax_update_in_calendar', 'core');
add_handler('ajax_update_in_calendar', 'check_calendar_invitations_imap', true, 'imap', 'imap_message_content', 'after');
add_handler('ajax_update_in_calendar', 'load_imap_servers_from_config', true, 'imap');
add_handler('ajax_update_in_calendar', 'imap_message_content', true, 'imap', 'load_imap_servers_from_config', 'after');
add_handler('ajax_update_in_calendar', 'update_in_calendar', true, 'tiki', 'imap_message_content', 'after');

/* message page update participant status function */
setup_base_ajax_page('ajax_update_participant_status', 'core');
add_handler('ajax_update_participant_status', 'check_calendar_invitations_imap', true, 'imap', 'imap_message_content', 'after');
add_handler('ajax_update_participant_status', 'load_imap_servers_from_config', true, 'imap');
add_handler('ajax_update_participant_status', 'imap_message_content', true, 'imap', 'load_imap_servers_from_config', 'after');
add_handler('ajax_update_participant_status', 'update_participant_status', true, 'tiki', 'imap_message_content', 'after');

/* message page remove event from calendar function */
setup_base_ajax_page('ajax_remove_from_calendar', 'core');
add_handler('ajax_remove_from_calendar', 'check_calendar_invitations_imap', true, 'imap', 'imap_message_content', 'after');
add_handler('ajax_remove_from_calendar', 'load_imap_servers_from_config', true, 'imap');
add_handler('ajax_remove_from_calendar', 'imap_message_content', true, 'imap', 'load_imap_servers_from_config', 'after');
add_handler('ajax_remove_from_calendar', 'remove_from_calendar', true, 'tiki', 'imap_message_content', 'after');

/* debug mode and other settings updates */
add_handler('settings', 'process_debug_mode', true, 'tiki', 'save_user_settings', 'before');
add_handler('settings', 'process_allow_external_images', true, 'tiki', 'save_user_settings', 'before');
add_handler('settings', 'before_save_user_settings', true, 'tiki', 'save_user_settings', 'before');
add_handler('settings', 'after_save_user_settings', true, 'tiki', 'save_user_settings', 'after');
add_handler('settings', 'process_enable_oauth2_over_imap', true, 'tiki', 'save_user_settings', 'before');
add_handler('settings', 'process_enable_gmail_contacts_module', true, 'tiki', 'save_user_settings', 'before');
add_output('settings', 'debug_mode_setting', true, 'tiki', 'start_unread_settings', 'before');
add_output('settings', 'start_advanced_settings', true, 'core', 'end_settings_form', 'before');
add_output('settings', 'allow_external_images_setting', true, 'tiki', 'start_advanced_settings', 'after');
add_output('settings', 'enable_oauth2_over_imap_setting', true, 'tiki', 'allow_external_images_setting', 'after');
add_output('settings', 'enable_gmail_contacts_module_setting', true, 'tiki', 'enable_oauth2_over_imap_setting', 'after');

/* tracker field email folder handling */
add_handler('message', 'tracker_message_list_type', true, 'core', 'message_list_type', 'after');
add_handler('message', 'tiki_download_message', true, 'core', 'message_list_type', 'after');
add_handler('message_list', 'check_path_redirect', true, 'core', 'load_user_data', 'after');
add_handler('compose', 'tiki_presave_sent', true, 'smtp', 'imap_save_sent', 'before');
add_handler('compose', 'tiki_mark_as_answered', true, 'smtp', 'process_compose_form_submit', 'after');
add_handler('compose', 'tiki_save_sent', true, 'smtp', 'tiki_mark_as_answered', 'after');
add_handler('compose', 'tiki_archive_replied', true, 'smtp', 'tiki_save_sent', 'after');
add_handler('compose', 'check_path_redirect_after_sent', true, 'smtp', 'tiki_archive_replied', 'after');
add_handler('compose', 'tiki_compose_from_draft', true, 'smtp', 'load_smtp_servers_from_config', 'after');
add_output('ajax_imap_message_content', 'add_move_to_trackers', true, 'imap', 'filter_message_headers', 'after');
add_output('ajax_imap_message_content', 'tiki_get_create_item_trackers_output', true, 'imap', 'filter_message_headers', 'after');
add_output('message_list', 'add_multiple_move_to_trackers', true, 'imap', 'imap_custom_controls', 'after');
add_output('message_list', 'add_multiple_item_to_trackers', true, 'imap', 'imap_custom_controls', 'after');
add_handler('ajax_smtp_save_draft', 'tiki_presave_draft', true, 'smtp', 'smtp_save_draft', 'before');
add_handler('ajax_smtp_save_draft', 'tiki_save_draft', true, 'smtp', 'smtp_save_draft', 'after');
setup_base_ajax_page('ajax_move_to_tracker', 'core');
add_handler('ajax_move_to_tracker', 'load_imap_servers_from_config', true, 'imap');
add_handler('ajax_move_to_tracker', 'imap_oauth2_token_check', true, 'imap');
add_handler('ajax_move_to_tracker', 'move_to_tracker', true, 'tiki', 'imap_oauth2_token_check', 'after');
add_handler('ajax_move_to_tracker', 'save_imap_cache', true, 'imap', 'move_to_tracker', 'after');
add_handler('ajax_move_to_tracker', 'close_session_early', true, 'core', 'save_imap_cache', 'after');
add_output('ajax_move_to_tracker', 'pass_redirect_url', true, 'tiki');
setup_base_ajax_page('ajax_tiki_message_content', 'core');
add_handler('ajax_tiki_message_content', 'tiki_message_content', true);
add_handler('ajax_tiki_message_content', 'close_session_early', true, 'core');
add_output('ajax_tiki_message_content', 'filter_message_headers', true, 'imap');
add_output('ajax_tiki_message_content', 'filter_message_headers_mpdf', true);
add_output('ajax_tiki_message_content', 'filter_message_body', true, 'imap');
add_output('ajax_tiki_message_content', 'filter_message_struct', true, 'imap');
add_output('ajax_tiki_message_content', 'forward_variables', true);
add_output('ajax_tiki_message_content', 'add_move_to_trackers', true);
setup_base_ajax_page('ajax_tiki_delete_message', 'core');
add_handler('ajax_tiki_delete_message', 'message_list_type', true, 'core');
add_handler('ajax_tiki_delete_message', 'tracker_message_list_type', true);
add_handler('ajax_tiki_delete_message', 'close_session_early', true, 'core');
add_handler('ajax_tiki_delete_message', 'tiki_delete_message', true);
setup_base_ajax_page('ajax_tiki_archive_message', 'core');
add_handler('ajax_tiki_archive_message', 'message_list_type', true, 'core');
add_handler('ajax_tiki_archive_message', 'tracker_message_list_type', true);
add_handler('ajax_tiki_archive_message', 'close_session_early', true, 'core');
add_handler('ajax_tiki_archive_message', 'tiki_archive_message', true);
setup_base_ajax_page('ajax_tiki_move_copy_action', 'core');
add_handler('ajax_tiki_move_copy_action', 'load_imap_servers_from_config', true, 'imap');
add_handler('ajax_tiki_move_copy_action', 'imap_oauth2_token_check', true, 'imap');
add_handler('ajax_tiki_move_copy_action', 'tiki_process_move', true);
add_handler('ajax_tiki_move_copy_action', 'save_imap_cache', true, 'imap');
add_handler('ajax_tiki_move_copy_action', 'close_session_early', true, 'core');
setup_base_ajax_page('ajax_tiki_flag_message', 'core');
add_handler('ajax_tiki_flag_message', 'close_session_early', true, 'core');
add_handler('ajax_tiki_flag_message', 'flag_tiki_message', true);
add_output('ajax_tiki_flag_message', 'forward_variables', true);
setup_base_ajax_page('ajax_tiki_message_action', 'core');
add_handler('ajax_tiki_message_action', 'tiki_message_action', true);

/* get trackers script */
setup_base_ajax_page('ajax_tiki_get_trackers', 'core');
add_handler('ajax_tiki_get_trackers', 'load_imap_servers_from_config', true, 'imap', 'load_user_data', 'after');
add_handler('ajax_tiki_get_trackers', 'settings_load_imap', true);
add_handler('ajax_tiki_get_trackers', 'tiki_get_trackers', true);
add_output('ajax_tiki_get_trackers', 'tiki_get_trackers_output', true);

/* get trackers script */
setup_base_ajax_page('ajax_tiki_tracker_info', 'core');
add_handler('ajax_tiki_tracker_info', 'load_imap_servers_from_config', true, 'imap', 'load_user_data', 'after');
add_handler('ajax_tiki_tracker_info', 'settings_load_imap', true);
add_handler('ajax_tiki_tracker_info', 'tiki_tracker_info', true);
add_output('ajax_tiki_tracker_info', 'tiki_tracker_info_output', true);

/* setup sources */
handler_source('developer');
output_source('developer');

/* info page */
setup_base_page('info', 'core');
add_handler('info', 'process_server_info', true, 'developer', 'load_user_data', 'after');
add_output('info', 'info_heading', true, 'developer', 'content_section_start', 'after');
add_output('info', 'server_information', true, 'developer', 'info_heading', 'after');
add_output('info', 'server_status_start', true, 'developer', 'server_information', 'after');
add_output('info', 'server_status_end', true, 'developer', 'server_status_start', 'after');
add_output('info', 'config_map', true, 'developer', 'server_status_end', 'after');

/* folder list */
add_output('ajax_hm_folders', 'info_page_link', true, 'developer', 'settings_menu_end', 'before');

/* sieve filters */
add_handler('sieve_filters', 'tiki_add_sieve_config_host', true, 'tiki', 'load_imap_servers_from_config', 'after');
add_output('sieve_filters', 'tiki_filters_cron', true, 'tiki', 'sievefilters_settings_start', 'after');

setup_base_ajax_page('ajax_tiki_sieve_get_mailboxes', 'core');
add_handler('ajax_tiki_sieve_get_mailboxes', 'load_imap_servers_from_config', true, 'imap', 'load_user_data', 'after');
add_handler('ajax_tiki_sieve_get_mailboxes', 'settings_load_imap', true);
add_handler('ajax_tiki_sieve_get_mailboxes', 'tiki_sieve_get_mailboxes_script', true);
add_output('ajax_tiki_sieve_get_mailboxes', 'tiki_sieve_get_mailboxes_output', true);

add_handler('ajax_imap_unread', 'tiki_process_imap_unread', true, 'tiki', 'imap_unread', 'after');

add_handler('settings', 'process_tiki_run_sieve_filters_on_imap_unread', true, 'tiki', 'save_user_settings', 'before');
add_output('settings', 'tiki_run_sieve_filters_on_imap_unread_setting', true, 'tiki', 'enable_gmail_contacts_module_setting', 'after');

return [
  'allowed_pages' => [
    'groupmail',
    'ajax_tiki_groupmail',
    'ajax_take_groupmail',
    'ajax_put_back_groupmail',
    'ajax_rsvp_action',
    'ajax_add_to_calendar',
    'ajax_update_in_calendar',
    'ajax_update_participant_status',
    'ajax_remove_from_calendar',
    'ajax_move_to_tracker',
    'ajax_tiki_message_content',
    'ajax_tiki_delete_message',
    'ajax_tiki_archive_message',
    'ajax_tiki_move_copy_action',
    'ajax_tiki_flag_message',
    'ajax_tiki_message_action',
    'ajax_tiki_get_trackers',
    'ajax_tiki_tracker_info',
    'ajax_tiki_sieve_get_mailboxes',
    'info',
  ],
  'allowed_get' => [
    'tiki_download_message' => FILTER_VALIDATE_BOOLEAN,
    'tiki_show_message'  => FILTER_VALIDATE_BOOLEAN,
  ],
  'allowed_output' => [
    'operator' => [FILTER_SANITIZE_FULL_SPECIAL_CHARS, false],
    'item_removed' => [FILTER_VALIDATE_BOOLEAN, false],
    'tiki_redirect_url' => [FILTER_SANITIZE_FULL_SPECIAL_CHARS, false],
    'msg_prev_link' => [FILTER_SANITIZE_FULL_SPECIAL_CHARS, false],
    'msg_prev_subject' => [FILTER_SANITIZE_FULL_SPECIAL_CHARS, false],
    'msg_next_link' => [FILTER_SANITIZE_FULL_SPECIAL_CHARS, false],
    'msg_next_subject' => [FILTER_SANITIZE_FULL_SPECIAL_CHARS, false],
    'delete_error' => [FILTER_VALIDATE_BOOLEAN, false],
    'archive_error' => [FILTER_VALIDATE_BOOLEAN, false],
    'show_archive' => [FILTER_VALIDATE_BOOLEAN, false],
    'flag_state' => [FILTER_SANITIZE_FULL_SPECIAL_CHARS, false],
    'trackers' => [FILTER_DEFAULT, false],
    'tracker_data' => [FILTER_DEFAULT, false],
    'mailboxes' => [FILTER_UNSAFE_RAW, false],
    'msg_headers' => [FILTER_UNSAFE_RAW, false],
  ],
  'allowed_post' => [
    'imap_server_id' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'imap_msg_uid' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'imap_msg_ids' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'imap_msg_part' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'folder' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'msgid' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'imap_allow_images' => FILTER_VALIDATE_BOOLEAN,
    'list_path' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'rsvp_action' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'calendar_id' => FILTER_VALIDATE_INT,
    'debug_mode' => FILTER_VALIDATE_INT,
    'allow_external_images' => FILTER_VALIDATE_INT,
    'tiki_enable_oauth2_over_imap' => FILTER_VALIDATE_INT,
    'tiki_enable_gmail_contacts_module' => FILTER_VALIDATE_INT,
    'gmail_client_id' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'gmail_client_secret' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'gmail_client_uri' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'gmail_auth_uri' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'gmail_token_uri' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'gmail_refresh_uri' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'outlook_client_id' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'outlook_client_secret' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'outlook_client_uri' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'outlook_auth_uri' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'outlook_token_uri' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'outlook_refresh_uri' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'tracker_field_id' => FILTER_VALIDATE_INT,
    'tracker_item_id' => FILTER_VALIDATE_INT,
    'imap_move_ids' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'imap_move_to' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'imap_move_action' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'action_type' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'imap_account' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'tiki_archive_replied' => FILTER_VALIDATE_INT,
    'tiki_run_sieve_filters_on_imap_unread' => FILTER_VALIDATE_INT,
  ]
];
