<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Tiki general modules
 * @package modules
 * @subpackage tiki
 */

if (! defined('DEBUG_MODE')) {
    die();
}

/**
 * Load Tiki contacts into the Cypht contact store
 * @subpackage tiki/handler
 */
class Hm_Handler_load_tiki_contacts extends Hm_Handler_Module
{
    public function process()
    {
        global $user;
        $contactlib = TikiLib::lib('contact');
        $contacts = $this->get('contact_store');
        $tiki_contacts = $contactlib->list_contacts($user);
        foreach ($tiki_contacts as $contact) {
            // skip adding if it already exists
            if ($contacts->get(null, false, $contact['email'])) {
                continue;
            }
            $contacts->add_contact([
                'source' => 'tiki',
                'email_address' => $contact['email'],
                'display_name' => $contact['firstName'] . ($contact['lastName'] ? ' ' . $contact['lastName'] : ''),
                'external' => true,
            ]);
        }
        $this->append('contact_sources', 'tiki');
        $this->out('contact_store', $contacts, false);
    }
}

/**
 * Check for Tiki redirect and instruct Cypht to redirect after compose finished successfully
 * @subpackage tiki/handler
 */
class Hm_Handler_check_for_tiki_redirect extends Hm_Handler_Module
{
    public $session;
    public function process()
    {
        if ($this->get('msg_sent') && $this->session->get('pageaftersend')) {
            $this->out('redirect_url', $this->session->get('pageaftersend'));
            $this->session->del('pageaftersend');
        }
    }
}

/**
 * Add optional Tiki File attachment to compose page
 * @subpackage tiki/handler
 */
class Hm_Handler_add_file_attachment extends Hm_Handler_Module
{
    public $request;
    public $session;
    public $config;
    public function process()
    {
        $draft_id = $this->request->get['draft_id'] ?? -1;
        $draft = get_draft($draft_id, $this->session);
        if ($draft && $draft['draft_fattId']) {
            $tikifile = Tiki\FileGallery\File::id($draft['draft_fattId']);
            $file = [
                'name' => $tikifile->name,
                'filename' => $tikifile->filename,
                'type' => $tikifile->filetype,
                'size' => $tikifile->filesize
            ];
            if (! attach_file($tikifile->getContents(), $file, $this->config->get('attachment_dir'), $draft_id, $this)) {
                Hm_Msgs::add(tr('An error occurred attaching the file gallery file.'), 'danger');
            }
        }
    }
}

/**
 * Output the Tiki Contacts menu item
 * @subpackage tiki/output
 */
class Hm_Output_tiki_contacts_page_link extends Hm_Output_Module
{
    public $format;
    protected function output()
    {
        $res = '<li class="menu_contacts"><a class="unread_link" href="#">';
        if (! $this->get('hide_folder_icons')) {
            $res .= '<i class="bi bi-people-fill menu-icon"></i>';
        }
        $res .= '<span class="nav-label">' . $this->trans('Contacts') . '</span></a></li>';
        if ($this->format == 'HTML5') {
            return $res;
        }
        $this->concat('formatted_folder_list', $res);
    }
}

/**
 * Save debug setting
 * @subpackage tiki/handler
 */
class Hm_Handler_process_debug_mode extends Hm_Handler_Module
{
    public function process()
    {
        function debug_mode_callback($val)
        {
            return $val;
        }
        process_site_setting('debug_mode', $this, 'debug_mode_callback', false, true);
    }
}

/**
 * Save external image sources setting
 * @subpackage tiki/handler
 */
class Hm_Handler_process_allow_external_images extends Hm_Handler_Module
{
    public function process()
    {
        function allow_external_images_callback($val)
        {
            return $val;
        }
        process_site_setting('allow_external_images', $this, 'allow_external_images_callback', false, true);
    }
}

/**
 * Set special variable to prevent saving settings on each config update
 * @subpackage tiki/handler
 */
class Hm_Handler_before_save_user_settings extends Hm_Handler_Module
{
    public $request;
    public $user_config;
    public function process()
    {
        if (array_key_exists('save_settings', $this->request->post)) {
            $this->user_config->set('skip_saving_on_set', true);
        }
    }
}

/**
 * Run sieve filters on imap unread setting
 * @subpackage tiki/handler
 */
class Hm_Handler_process_tiki_run_sieve_filters_on_imap_unread extends Hm_Handler_Module
{
    public function process()
    {
        function tiki_run_sieve_filters_on_imap_unread_callback($val)
        {
            return $val;
        }
        process_site_setting('tiki_run_sieve_filters_on_imap_unread', $this, 'tiki_run_sieve_filters_on_imap_unread_callback', false, true);
    }
}

/**
 * @subpackage tiki/output
 */
class Hm_Output_tiki_run_sieve_filters_on_imap_unread_setting extends Hm_Output_Module
{
    protected function output()
    {
        $auto = false;
        $settings = $this->get('user_settings', []);
        if (array_key_exists('tiki_run_sieve_filters_on_imap_unread', $settings)) {
            $auto = $settings['tiki_run_sieve_filters_on_imap_unread'];
        }
        $res = '<tr class="general_setting"><td><label class="form-check-label">' . $this->trans('Run cypht filters on unread imap messages') . '</label></td><td><input value="1" type="checkbox" name="tiki_run_sieve_filters_on_imap_unread" class="form-check-input"';
        $reset = '';
        if ($auto) {
            $res .= ' checked="checked"';
            $reset = '<span class="tooltip_restore" restore_aria_label="Restore default value"><i class="bi bi-arrow-clockwise reset_default_value_checkbox refresh_list"></i></span>';
        }
        $res .= '>' . $reset . '</td></tr>';
        return $res;
    }
}

/**
 * Remove special variable skipping settings save and save the settings
 * @subpackage tiki/handler
 */
class Hm_Handler_after_save_user_settings extends Hm_Handler_Module
{
    public $request;
    public $user_config;
    public function process()
    {
        if (array_key_exists('save_settings', $this->request->post)) {
            $this->user_config->del('skip_saving_on_set');
            $this->user_config->save();
        }
    }
}

/**
 * Expose debug setting
 * @subpackage tiki/output
 */
class Hm_Output_debug_mode_setting extends Hm_Output_Module
{
    protected function output()
    {
        $debug_mode = false;
        $settings = $this->get('user_settings', []);
        if (array_key_exists('debug_mode', $settings)) {
            $debug_mode = $settings['debug_mode'];
        }
        return '<tr class="general_setting"><td>' . tr('Debug mode messages to Tiki Log (caution: this may flood the logs if used extensively)') . '</td><td><input type="checkbox" name="debug_mode" value="1" ' . ($debug_mode ? 'checked' : '') . '></td></tr>';
    }
}

/**
 * Start the Advanced section on the settings page
 * @subpackage tiki/output
 */
class Hm_Output_start_advanced_settings extends Hm_Output_Module
{
    /**
     * Settings in this section control the advanced integration settings betwene Cypht and Tiki (hm3.ini ones)
     */
    protected function output()
    {
        return '<tr><td data-bs-target=".advanced_setting" colspan="2" class="settings_subtitle cursor-pointer border-bottom p-2 text-secondary">' .
            '<i class="bi bi-code-slash fs-5 me-2"></i>' .
            $this->trans('Advanced') . '</td></tr>';
    }
}

/**
 * Expose image sources setting
 * @subpackage tiki/output
 */
class Hm_Output_allow_external_images_setting extends Hm_Output_Module
{
    protected function output()
    {
        $allow_external_images = false;
        $settings = $this->get('user_settings', []);
        $reset = '<span class="tooltip_restore" restore_aria_label="Restore default value"><i class="bi bi-arrow-clockwise refresh_list reset_default_value_checkbox"></i></span>';
        if (array_key_exists('allow_external_images', $settings)) {
            $allow_external_images = $settings['allow_external_images'];
        }
        return '<tr class="general_setting"><td><label class="form-check-label">' . tr('Allow remote image sources') . '</label></td><td><input type="checkbox" class="form-check-input" name="allow_external_images" value="1" ' . ($allow_external_images ? 'checked >' . $reset : '>') . '</td></tr>';
    }
}

/**
 * Save enable oauth2 over imap setting
 * @subpackage tiki/handler
 */
class Hm_Handler_process_enable_oauth2_over_imap extends Hm_Handler_Module
{
    public function process()
    {
        function tiki_enable_oauth2_over_imap_callback($val)
        {
            return $val;
        }
        process_site_setting('tiki_enable_oauth2_over_imap', $this, 'tiki_enable_oauth2_over_imap_callback', false, true);
        process_site_setting('tiki_enable_gmail_contacts_module', $this, 'tiki_enable_oauth2_over_imap_callback', false, true);

        process_site_setting('gmail_client_id', $this, 'tiki_enable_oauth2_over_imap_callback');
        process_site_setting('gmail_client_secret', $this, 'tiki_enable_oauth2_over_imap_callback');
        process_site_setting('gmail_client_uri', $this, 'tiki_enable_oauth2_over_imap_callback');
        process_site_setting('gmail_auth_uri', $this, 'tiki_enable_oauth2_over_imap_callback');
        process_site_setting('gmail_token_uri', $this, 'tiki_enable_oauth2_over_imap_callback');
        process_site_setting('gmail_refresh_uri', $this, 'tiki_enable_oauth2_over_imap_callback');

        process_site_setting('outlook_client_id', $this, 'tiki_enable_oauth2_over_imap_callback');
        process_site_setting('outlook_client_secret', $this, 'tiki_enable_oauth2_over_imap_callback');
        process_site_setting('outlook_client_uri', $this, 'tiki_enable_oauth2_over_imap_callback');
        process_site_setting('outlook_auth_uri', $this, 'tiki_enable_oauth2_over_imap_callback');
        process_site_setting('outlook_token_uri', $this, 'tiki_enable_oauth2_over_imap_callback');
        process_site_setting('outlook_refresh_uri', $this, 'tiki_enable_oauth2_over_imap_callback');
    }
}


/**
 * Expose enable oauth2 over imap setting
 * @subpackage tiki/output
 */
class Hm_Output_enable_oauth2_over_imap_setting extends Hm_Output_Module
{
    protected function output()
    {
        $enable_oauth2_over_imap = false;

        $gmail_client_id = " ";
        $gmail_client_secret = " ";
        $gmail_client_uri = " ";
        $gmail_auth_uri = "https://accounts.google.com/o/oauth2/auth";
        $gmail_token_uri = "https://www.googleapis.com/oauth2/v3/token";
        $gmail_refresh_uri = "https://www.googleapis.com/oauth2/v3/token";

        $outlook_client_id = " ";
        $outlook_client_secret = " ";
        $outlook_client_uri = " ";
        $outlook_auth_uri = "https://login.live.com/oauth20_authorize.srf";
        $outlook_token_uri = "https://login.live.com/oauth20_token.srf";
        $outlook_refresh_uri = "https://login.live.com/oauth20_token.srf";
        $reset = '<span class="tooltip_restore" restore_aria_label="Restore default value"><i class="bi bi-arrow-clockwise refresh_list reset_default_value_checkbox"></i></span>';

        $settings = $this->get('user_settings', []);
        if (array_key_exists('tiki_enable_oauth2_over_imap', $settings)) {
            $enable_oauth2_over_imap = $settings['tiki_enable_oauth2_over_imap'];
        }
        //gmail settings
        if (array_key_exists('gmail_client_id', $settings)) {
            $gmail_client_id = $settings['gmail_client_id'];
        }
        if (array_key_exists('gmail_client_secret', $settings)) {
            $gmail_client_secret = $settings['gmail_client_secret'];
        }
        if (array_key_exists('gmail_client_uri', $settings)) {
            $gmail_client_uri = $settings['gmail_client_uri'];
        }

        //outlook settings
        if (array_key_exists('outlook_client_id', $settings)) {
            $outlook_client_id = $settings['outlook_client_id'];
        }
        if (array_key_exists('outlook_client_secret', $settings)) {
            $outlook_client_secret = $settings['outlook_client_secret'];
        }
        if (array_key_exists('outlook_client_uri', $settings)) {
            $outlook_client_uri = $settings['outlook_client_uri'];
        }

        return '
        <tr class="general_setting"><td><label class="form-check-label">' . tr('Enable Oauth2 over IMAP') . '</label></td><td><input type="checkbox" name="tiki_enable_oauth2_over_imap" value="1" class="tiki_enable_oauth2_over_imap form-check-input" ' . ($enable_oauth2_over_imap ? 'checked >' . $reset : '>') . '</td></tr>
        <tr class="oauth reveal-if-unchecked"><td>' . tr('Gmail Client ID') . '</td><td><textarea class="form-control" name="gmail_client_id">' . $gmail_client_id . '</textarea></td></tr>
        <tr class="oauth reveal-if-unchecked"><td>' . tr('Gmail Client secret') . '</td><td><textarea class="form-control" name="gmail_client_secret">' . $gmail_client_secret . '</textarea></td></tr>
        <tr class="oauth reveal-if-unchecked"><td>' . tr('Gmail Client Uri') . '</td><td><textarea class="form-control" name="gmail_client_uri">' . $gmail_client_uri . '</textarea></td></tr>
        <tr class="reveal-if-unchecked"><td>' . tr('Gmail Auth Uri') . '</td><td><textarea class="form-control" name="gmail_auth_uri">' . $gmail_auth_uri . '</textarea></td></tr>
        <tr class="reveal-if-unchecked"><td>' . tr('Gmail Token Uri') . '</td><td><textarea class="form-control" name="gmail_token_uri">' . $gmail_token_uri . '</textarea></td></tr>
        <tr class="reveal-if-unchecked"><td>' . tr('Gmail Refresh Uri') . '</td><td><textarea class="form-control" name="gmail_refresh_uri">' . $gmail_refresh_uri . '</textarea></td></tr>
        <tr class="oauth reveal-if-unchecked"></tr>
        <tr class="oauth reveal-if-unchecked"><td>' . tr('Outlook Client ID') . '</td><td><textarea class="form-control" name="outlook_client_id">' . $outlook_client_id . '</textarea></td></tr>
        <tr class="oauth reveal-if-unchecked"><td>' . tr('Outlook Client secret') . '</td><td><textarea class="form-control" name="outlook_client_secret">' . $outlook_client_secret . '</textarea></td></tr>
        <tr class="oauth reveal-if-unchecked"><td>' . tr('Outlook Client Uri') . '</td><td><textarea class="form-control" name="outlook_client_uri">' . $outlook_client_uri . '</textarea></td></tr>
        <tr class="reveal-if-unchecked"><td>' . tr('Outlook Auth Uri') . '</td><td><textarea class="form-control" name="outlook_auth_uri">' . $outlook_auth_uri . '</textarea></td></tr>
        <tr class="reveal-if-unchecked"><td>' . tr('Outlook Token Uri') . '</td><td><textarea class="form-control" name="outlook_token_uri">' . $outlook_token_uri . '</textarea></td></tr>
        <tr class="reveal-if-unchecked"><td>' . tr('Outlook Refresh Uri') . '</td><td><textarea class="form-control" name="outlook_refresh_uri">' . $outlook_refresh_uri . '</textarea></td></tr>
        ';
    }
}

/**
 *  Format the message headers about mpdf generation of the message view
 * Format the message headers about mpdf generation of the message view
 * @subpackage tiki/output
 */
class Hm_Output_filter_message_headers_mpdf extends Hm_Output_Module
{
    protected function output()
    {
        $headers = $this->get('msg_headers');
        if (is_string($headers) && TikiLib::lib('tiki')->get_preference('print_pdf_from_url') == "mpdf") {
            $pdf_link = ' <a class="hlink text-decoration-none btn btn-sm btn-outline-secondary" id="print_pdf" href="#"> ' . $this->trans('PDF') . ' </a>';
            $headers = append_to_msg_headers($headers, $pdf_link);
            $this->out('msg_headers', $headers, false);
        }
    }
}

/**
 * Save gmail_contacts  module setting
 * @subpackage tiki/handler
 */
class Hm_Handler_process_enable_gmail_contacts_module extends Hm_Handler_Module
{
    public function process()
    {
        function tiki_enable_gmail_contacts_module_callback($val)
        {
            return $val;
        }
        process_site_setting('tiki_enable_gmail_contacts_module', $this, 'tiki_enable_gmail_contacts_module_callback', false, true);
    }
}

/**
 * Overrides Cypht headers with Tiki-based ones
 * @subpackage core/handler
 */
class Hm_Handler_http_headers_tiki extends Hm_Handler_Module
{
    public function process()
    {
        global $prefs;
        $headers = $this->get('http_headers');
        if ($prefs['http_header_content_security_policy'] == 'y') {
            $headers['Content-Security-Policy'] = $prefs['http_header_content_security_policy_value'];
        } else {
            unset($headers['Content-Security-Policy']);
        }
        $this->out('http_headers', $headers);
    }
}

/**
 * Expose gmail_contacts module setting
 * @subpackage tiki/output
 */
class Hm_Output_enable_gmail_contacts_module_setting extends Hm_Output_Module
{
    protected function output()
    {
        $enable_gmail_contacts_module = false;
        $reset = '<span class="tooltip_restore" restore_aria_label="Restore default value"><i class="bi bi-arrow-clockwise refresh_list reset_default_value_checkbox"></i></span>';
        $settings = $this->get('user_settings', []);
        if (array_key_exists('tiki_enable_gmail_contacts_module', $settings)) {
            $enable_gmail_contacts_module = $settings['tiki_enable_gmail_contacts_module'];
        }
        return '<tr class="general_setting"><td><label class="form-check-label">' . tr('Enable Gmail Contacts Module') . '</label></td><td><input class="form-check-input" type="checkbox" name="tiki_enable_gmail_contacts_module" value="1" ' . ($enable_gmail_contacts_module ? 'checked >' . $reset : '>') . '</td></tr>';
    }
}

/**
 *
 */
class Hm_Handler_post_imap_save_sent extends Hm_Handler_Module
{
    public function process()
    {
        $msgUid = $this->get('sent_msg_uid');

        if (! $msgUid) {
            return;
        }

        $subject = $this->request->post['compose_subject'];

        $keywords = [];

        foreach (explode(' ', $subject) as $word) {
            $keywords[] = $word;
        }

        $keywords = array_map('trim', $keywords);
        $keywords = array_filter($keywords);
        $keywords = array_unique($keywords);

        $this->session->set('keywords', $keywords);
        $this->session->set('msg_uid', $msgUid);
        $this->session->set('imap_server_id', $this->get('sent_imap_id'));
    }
}

class Hm_Handler_get_msg_tracker_items extends Hm_Handler_Module
{
    public function process()
    {
        $keywords = $this->session->get('keywords');
        $msgUid = $this->session->get('msg_uid');
        $imapServerId = $this->session->get('imap_server_id');

        $this->session->set('keywords', '');
        $this->session->set('msg_uid', '');
        $this->session->set('imap_server_id', '');

        $fields = TikiLib::lib('trk')->get_fields_by_type('EF');
        $fields = array_map(function ($field) {
            $tracker = TikiLib::lib('trk')->get_tracker($field['trackerId']);
            return [
                'tracker_id' => $field['trackerId'],
                'field_id' => $field['fieldId'],
                'title' => "{$tracker['name']} - {$field['name']}",
            ];
        }, $fields);
        $this->out('tracker_fields', $fields);

        if (isset($this->request->post['lookup'])) {
            $searchArgs = [];
            if (isset($this->request->post['sort_mode'])) {
                $searchArgs['sort_mode'] = $this->request->post['sort_mode'];
            }
            if (isset($this->request->post['limit'])) {
                $searchArgs['maxRecords'] = $this->request->post['limit'];
            }
            if (isset($this->request->post['tracker_id'])) {
                $searchArgs['tracker_id'] = $this->request->post['tracker_id'];
            }
            if (isset($this->request->post['tracker_id'])) {
                $searchArgs['field_id'] = $this->request->post['field_id'];
            }
            $this->out('tracker_items', find_relevant_tracker_items($this->request->post['lookup'], searchArgs: $searchArgs));
            return;
        }

        if (! $keywords || ! $msgUid || ! $imapServerId) {
            $this->out('no_message', true);
            return;
        }

        $this->out('tracker_items', find_relevant_tracker_items(implode(' or ', $keywords)));
        $this->out('msg_uid', $msgUid);
        $this->out('list_path', "imap_$imapServerId" . '_' . bin2hex("Sent"));
    }
}
