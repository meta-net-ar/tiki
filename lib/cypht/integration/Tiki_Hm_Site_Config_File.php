<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tiki_Hm_Site_Config_File extends Hm_Site_Config_File
{
    /**
     * @var array<string, mixed>
     */
    public $user_defaults = [];
    public $config = [];
    public $settings_per_page;
    /**
     * Load data based on source
     * Overrides default configuration for Tiki integration
     * @param array $source source location for site configuration
     */
    public function __construct($source = [], $session_prefix = 'cypht', $settings_per_page = false)
    {
        global $user;
        parent::__construct($source);
        // override
        $headerlib = TikiLib::lib('header');
        $this->set('session_type', 'custom');
        $this->set('session_class', 'Tiki_Hm_Custom_Session');
        $this->set('cache_class', 'Tiki_Hm_Custom_Cache');
        $this->set('auth_class', 'Tiki_Hm_Custom_Auth');
        $this->set('session_prefix', $session_prefix);
        $this->set('auth_type', 'custom');
        $this->set('output_class', 'Tiki_Hm_Output_HTTP');
        $this->set('cookie_path', ini_get('session.cookie_path'));
        $this->set('sieve_client_factory', 'Tiki_Hm_Sieve_Client_Factory');
        if ($user && (empty($_SESSION[$session_prefix]['user_data']) || count($_SESSION[$session_prefix]['user_data']) == 2)) {
            $user_config = new Tiki_Hm_User_Config($this);
            $user_config->load($user);
            $_SESSION[$session_prefix]['user_data'] = $user_config->dump();
        }
        $this->settings_per_page = $settings_per_page;
        $output_modules = $this->get('output_modules');
        $handler_modules = $this->get('handler_modules');
        foreach ($output_modules as $page => $_) {
            unset($output_modules[$page]['header_start']);
            unset($output_modules[$page]['header_content']);
            unset($output_modules[$page]['header_end']);
            if (isset($output_modules[$page]['save_reminder'])) {
                unset($output_modules[$page]['save_reminder']);
            }
            if (isset($output_modules[$page]['header_css'])) {
                unset($output_modules[$page]['header_css']);
                $headerlib->add_cssfile('lib/cypht/site.css');
                $headerlib->add_css('html, body { background-color: var(--bs-body-bg) !important; }');
            }
            if (isset($output_modules[$page]['page_js'])) {
                unset($output_modules[$page]['page_js']);
                $headerlib->add_jsfile('lib/cypht/jquery.touch.js');
                $headerlib->add_jsfile('lib/cypht/site.js');
            }
        }
        // cleanup side menu
        unset($output_modules['ajax_hm_folders']['logout_menu_item']);
        unset($output_modules['ajax_hm_folders']['contacts_page_link']);
        unset($output_modules['ajax_hm_folders']['settings_save_link']);
        // show links according to permissions
        if (! Perms::get()->admin_personal_webmail && ! Perms::get()->admin_group_webmail) {
            unset($output_modules['ajax_hm_folders']['settings_servers_link']);
            unset($output_modules['ajax_hm_folders']['folders_page_link']);
            unset($output_modules['home']['welcome_dialog']);
            unset($handler_modules['ajax_imap_folder_expand']['add_folder_manage_link']);
        }
        foreach ($handler_modules as $page => $modules) {
            foreach ($modules as $module => $opts) {
                if ($module == 'http_headers') {
                    $handler_modules[$page]['http_headers_tiki'] = ['tiki', true];
                }
            }
        }
        $this->set('output_modules', $output_modules);
        $this->set('handler_modules', $handler_modules);
        if (empty($_SESSION[$session_prefix]['user_data']['timezone_setting'])) {
            $this->user_defaults['timezone_setting'] = TikiLib::lib('tiki')->get_display_timezone();
            if (isset($_SESSION[$session_prefix]['user_data'])) {
                $_SESSION[$session_prefix]['user_data']['timezone_setting'] = $this->user_defaults['timezone_setting'];
            }
        }
        if (isset($_SESSION[$session_prefix]['user_data']['allow_external_images_setting'])) {
            $this->set('allow_external_image_sources', $_SESSION[$session_prefix]['user_data']['allow_external_images_setting']);
        }
        // handle oauth2 config
        $oauth2 = [
            'gmail' => [
                'client_id' => '',
                'client_secret' => '',
                'client_uri' => '',
                'auth_uri' => '',
                'token_uri' => '',
                'refresh_uri' => '',
            ],
            'outlook' => [
                'client_id' => '',
                'client_secret' => '',
                'client_uri' => '',
                'auth_uri' => '',
                'token_uri' => '',
                'refresh_uri' => '',
            ],];
        if (isset($_SESSION[$session_prefix]['user_data']['tiki_enable_oauth2_over_imap_setting'])) {
            $this->set('tiki_enable_oauth2_over_imap', $_SESSION[$session_prefix]['user_data']['tiki_enable_oauth2_over_imap_setting']);
        }

        if (isset($_SESSION[$session_prefix]['user_data']['tiki_enable_gmail_contacts_module_setting'])) {
            $this->set('tiki_enable_gmail_contacts_module', $_SESSION[$session_prefix]['user_data']['tiki_enable_gmail_contacts_module_setting']);
        }

        if (isset($_SESSION[$session_prefix]['user_data']['gmail_client_id_setting'])) {
            $oauth2['gmail']['client_id'] = $_SESSION[$session_prefix]['user_data']['gmail_client_id_setting'];
        }

        if (isset($_SESSION[$session_prefix]['user_data']['gmail_client_secret_setting'])) {
            $oauth2['gmail']['client_secret'] = $_SESSION[$session_prefix]['user_data']['gmail_client_secret_setting'];
        }

        if (isset($_SESSION[$session_prefix]['user_data']['gmail_client_uri_setting'])) {
            $oauth2['gmail']['client_uri'] = $_SESSION[$session_prefix]['user_data']['gmail_client_uri_setting'];
        }

        if (isset($_SESSION[$session_prefix]['user_data']['gmail_auth_uri_setting'])) {
            $oauth2['gmail']['auth_uri'] = $_SESSION[$session_prefix]['user_data']['gmail_auth_uri_setting'];
        }

        if (isset($_SESSION[$session_prefix]['user_data']['gmail_token_uri_setting'])) {
            $oauth2['gmail']['token_uri'] = $_SESSION[$session_prefix]['user_data']['gmail_token_uri_setting'];
        }

        if (isset($_SESSION[$session_prefix]['user_data']['gmail_refresh_uri_setting'])) {
            $oauth2['gmail']['refresh_uri'] = $_SESSION[$session_prefix]['user_data']['gmail_refresh_uri_setting'];
        }

        if (isset($_SESSION[$session_prefix]['user_data']['outlook_client_id_setting'])) {
            $oauth2['outlook']['client_id'] = $_SESSION[$session_prefix]['user_data']['outlook_client_id_setting'];
        }

        if (isset($_SESSION[$session_prefix]['user_data']['outlook_client_secret_setting'])) {
            $oauth2['outlook']['client_secret'] = $_SESSION[$session_prefix]['user_data']['outlook_client_secret_setting'];
        }

        if (isset($_SESSION[$session_prefix]['user_data']['outlook_client_uri_setting'])) {
            $oauth2['outlook']['client_uri'] = $_SESSION[$session_prefix]['user_data']['outlook_client_uri_setting'];
        }

        if (isset($_SESSION[$session_prefix]['user_data']['outlook_auth_uri_setting'])) {
            $oauth2['outlook']['auth_uri'] = $_SESSION[$session_prefix]['user_data']['outlook_auth_uri_setting'];
        }

        if (isset($_SESSION[$session_prefix]['user_data']['outlook_token_uri_setting'])) {
            $oauth2['outlook']['token_uri'] = $_SESSION[$session_prefix]['user_data']['outlook_token_uri_setting'];
        }

        if (isset($_SESSION[$session_prefix]['user_data']['outlook_refresh_uri_setting'])) {
            $oauth2['outlook']['refresh_uri'] = $_SESSION[$session_prefix]['user_data']['outlook_refresh_uri_setting'];
        }

        if (isset($_SESSION[$session_prefix]['user_data']['tiki_enable_oauth2_over_imap_setting']) && $_SESSION[$session_prefix]['user_data']['tiki_enable_oauth2_over_imap_setting'] == 1) {
            $this->set('oauth2.ini', $oauth2);
            if (isset($_SESSION[$session_prefix]['user_data']['tiki_enable_gmail_contacts_module_setting']) && $_SESSION[$session_prefix]['user_data']['tiki_enable_gmail_contacts_module_setting'] == 1) {
                array_push($this->config['modules'], 'gmail_contacts');
                $gmail_contact = [
                    'load_gmail_contacts' => [
                        '0' => 'gmail_contacts',
                        '1' => 1
                    ]
                ];
                array_push($this->config['handler_modules']['contacts'], $gmail_contact);
            } else {
                unset($this->config['modules']['gmail_contacts']);
                unset($this->config['handler_modules']['contacts']['load_gmail_contacts']);
            }
        } else {
            if (isset($this->config['oauth2.ini'])) {
                $this->del('oauth2.ini');
            }
        }
    }
}
