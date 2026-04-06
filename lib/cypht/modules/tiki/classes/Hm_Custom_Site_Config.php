<?php

/**
 * Custom site configuration class. To use this, you must set the SITE_CONFIG_TYPE environment variable to 'custom'.
 */
class Hm_Custom_Site_Config extends Hm_Site_Config_File
{
    public $user_defaults = [];
    public $settings_per_page;

    public function __construct()
    {
        parent::__construct();

        $headerlib = TikiLib::lib('header');

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
                $headerlib->add_cssfile('vendor_bundled/vendor/cypht-org/cypht/site.css');
                $headerlib->add_css('html, body { background-color: var(--bs-body-bg) !important; }');
            }
            if (isset($output_modules[$page]['page_js'])) {
                unset($output_modules[$page]['page_js']);
                $headerlib->add_jsfile('vendor_bundled/vendor/cypht-org/cypht/site.js');
            }
        }

        // cleanup side menu
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
    }

    public function initSession($session_prefix = 'cypht')
    {
        $this->set('session_prefix', $session_prefix);

        if (empty($_SESSION[$session_prefix]['user_data']['timezone_setting'])) {
            $this->user_defaults['timezone_setting'] = TikiLib::lib('tiki')->get_display_timezone();
            if (isset($_SESSION[$session_prefix]['user_data'])) {
                $_SESSION[$session_prefix]['user_data']['timezone_setting'] = $this->user_defaults['timezone_setting'];
            }
        }

        if (isset($_SESSION[$session_prefix]['user_data']['allow_external_images_setting'])) {
            $this->set('allow_external_image_sources', $_SESSION[$session_prefix]['user_data']['allow_external_images_setting']);
        }

        $this->handleOAuth2Config($session_prefix);
    }

    private function handleOAuth2Config($session_prefix)
    {
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
            ],
        ];

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
                $this->config['modules'][] = 'gmail_contacts';
                $gmail_contact = [
                    'load_gmail_contacts' => [
                        '0' => 'gmail_contacts',
                        '1' => 1
                    ]
                ];
                $this->config['handler_modules']['contacts'][] = $gmail_contact;
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
