<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_cypht_info()
{
    return [
        'name' => tra('Cypht webmail'),
        'documentation' => 'PluginCypht',
        'description' => tra('Embed Cypht webmail and news reader.'),
        'prefs' => [ 'wikiplugin_cypht' ],
        'iconname' => 'envelope',
        'introduced' => '20.0',
        'format' => 'html',
        'tags' => [ 'basic' ],
        'params' => [
            'imap_name' => [
                'name' => tr('IMAP connection name'),
                'description' => tr('Name for the IMAP connection. E.g. "My Mailbox"'),
                'required' => false,
                'default' => '',
                'filter' => 'text',
                'since' => '20.0',
            ],
            'imap_server' => [
                'name' => tr('IMAP server'),
                'description' => tr("E.g. imap.example.com"),
                'required' => false,
                'default' => '',
                'filter' => 'text',
                'since' => '20.0',
            ],
            'imap_port' => [
                'name' => tr('IMAP port'),
                'description' => tr("Default is 993."),
                'required' => false,
                'default' => '993',
                'filter' => 'text',
                'since' => '20.0',
            ],
            'imap_tls' => [
                'name' => tra('IMAP use TLS'),
                'description' => tr('Use secure connection to IMAP server.'),
                'required' => false,
                'filter' => 'alpha',
                'default' => 'y',
                'since' => '20.0',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'imap_username' => [
                'name' => tra('IMAP username'),
                'description' => tr('Account IMAP username.'),
                'required' => false,
                'filter' => 'text',
                'default' => '',
                'since' => '20.0',
            ],
            'imap_password' => [
                'name' => tra('IMAP password'),
                'description' => tr('Account IMAP password.'),
                'required' => false,
                'filter' => 'password',
                'default' => '',
                'since' => '20.0',
            ],
            'smtp_name' => [
                'name' => tr('SMTP connection name'),
                'description' => tr('Name for the SMTP connection. E.g. "My Account"'),
                'required' => false,
                'default' => '',
                'filter' => 'text',
                'since' => '20.0',
            ],
            'smtp_server' => [
                'name' => tr('SMTP server'),
                'description' => tr("E.g. smtp.example.com"),
                'required' => false,
                'default' => '',
                'filter' => 'text',
                'since' => '20.0',
            ],
            'smtp_port' => [
                'name' => tr('SMTP port'),
                'description' => tr("Default is 587."),
                'required' => false,
                'default' => '587',
                'filter' => 'text',
                'since' => '20.0',
            ],
            'smtp_username' => [
                'name' => tra('SMTP username'),
                'description' => tr('Account SMTP username.'),
                'required' => false,
                'filter' => 'text',
                'default' => '',
                'since' => '21.1',
            ],
            'smtp_password' => [
                'name' => tra('SMTP password'),
                'description' => tr('Account SMTP password.'),
                'required' => false,
                'filter' => 'password',
                'default' => '',
                'since' => '21.1',
            ],
            'smtp_tls' => [
                'name' => tra('SMTP use TLS'),
                'description' => tr('Use secure TLS/SSL connection to SMTP server.'),
                'required' => false,
                'filter' => 'alpha',
                'default' => 'y',
                'since' => '20.0',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'smtp_no_auth' => [
                'name' => tra('SMTP no authentication'),
                'description' => tr('Disable SMTP authentication if your server does not support it.'),
                'required' => false,
                'filter' => 'alpha',
                'default' => 'n',
                'since' => '20.0',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('No'), 'value' => 'n'],
                    ['text' => tra('Yes'), 'value' => 'y'],
                ],
            ],
            'use_global_settings' => [
                'name' => tra('Use global settings'),
                'description' => tr('Use global Cypht settings available at Tiki Webmail page. Choosing "No" will make this instance of Cypht use its own settings. Useful if this is a Groupmail box or you don\'t want to mix mailbox server and/or site settings from other pages. There are two modes of non-global settings - default one stores settings per user where each individual Tiki user will have their own copy of the settings whereas the second one stores settings per wiki page. Use per-wiki-page option if you want the plugin Cypht settings shared between users using the plugin on the same wiki page.'),
                'required' => false,
                'filter' => 'alpha',
                'default' => 'n',
                'since' => '20.0',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('No - keep settings per-user'), 'value' => 'n'],
                    ['text' => tra('No - keep settings per-wiki-page'), 'value' => 'nw'],
                    ['text' => tra('Yes - keep settings global per-user'), 'value' => 'y'],
                ],
            ],
            'groupmail' => [
                'name' => tra('Groupmail use'),
                'description' => tr('Share this mailbox for Groupmail usage or keep it private.'),
                'required' => false,
                'filter' => 'alpha',
                'default' => 'n',
                'since' => '20.0',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('No'), 'value' => 'n'],
                    ['text' => tra('Yes'), 'value' => 'y'],
                ],
            ],
            'group' => [
                'name' => tra('Group'),
                'description' => tra('GroupMail: Group (e.g. "Help Team")'),
                'required' => false,
                'filter' => 'striptags',
                'default' => '',
                'since' => '20.0',
            ],
            'trackerId' => [
                'name' => tra('Tracker ID'),
                'description' => tra('GroupMail: Tracker ID (to store GroupMail activity)'),
                'required' => false,
                'filter' => 'int',
                'profile_reference' => 'tracker',
                'default' => '',
                'since' => '20.0',
            ],
            'fromFId' => [
                'name' => tra('From Field ID'),
                'description' => tra('GroupMail: From Field (Id of field in tracker to store email From header)'),
                'required' => false,
                'filter' => 'int',
                'profile_reference' => 'tracker_field',
                'default' => '',
                'since' => '20.0',
            ],
            'subjectFId' => [
                'name' => tra('Subject Field ID'),
                'description' => tra('GroupMail: Subject Field (Id of field in tracker to store email Subject header)'),
                'required' => false,
                'filter' => 'int',
                'profile_reference' => 'tracker_field',
                'default' => '',
                'since' => '20.0',
            ],
            'messageFId' => [
                'name' => tra('Message Field ID'),
                'description' => tra('GroupMail: Message Field (Id of field in tracker to store email message identifier)'),
                'required' => false,
                'filter' => 'int',
                'profile_reference' => 'tracker_field',
                'default' => '',
                'since' => '20.0',
            ],
            'contentFId' => [
                'name' => tra('Content Field ID'),
                'description' => tra('GroupMail: Content Field (Id of field in tracker to store email message body content)'),
                'required' => false,
                'filter' => 'int',
                'profile_reference' => 'tracker_field',
                'default' => '',
                'since' => '20.0',
            ],
            'accountFId' => [
                'name' => tra('Account Field ID'),
                'description' => tra('GroupMail: Account Field (Id of field in tracker to store Webmail account name)'),
                'required' => false,
                'filter' => 'int',
                'profile_reference' => 'tracker_field',
                'default' => '',
                'since' => '20.0',
            ],
            'datetimeFId' => [
                'name' => tra('DateTime Field Id'),
                'description' => tra('GroupMail: Date Time Field (Id of field in tracker to store email sent timestamp)'),
                'required' => false,
                'filter' => 'int',
                'profile_reference' => 'tracker_field',
                'default' => '',
                'since' => '20.0',
            ],
            'operatorFId' => [
                'name' => tra('Operator Field ID'),
                'description' => tra('GroupMail: Operator Field (Id of field in tracker to store operator name (username))'),
                'required' => false,
                'filter' => 'int',
                'profile_reference' => 'tracker_field',
                'default' => '',
                'since' => '20.0',
            ],
        ],
    ];
}

function wikiplugin_cypht($data, $params)
{
    global $tikipath, $tikiroot, $user, $page, $logslib, $prefs;
    $headerlib = TikiLib::lib('header');
    $tikilib = TikiLib::lib('tiki');

    if (defined('APP_PATH')) {
        return tr("Cypht already started.");
    }

    if (TikiLib::lib('wiki')->get_page_by_slug($_GET['page']) == $page && $prefs['feature_sefurl'] !== 'y') {
        TikiLib::lib('access')->redirect('tiki-index.php?page_id=' . $tikilib->get_page_id_from_name($page));
    } elseif ($prefs['feature_sefurl'] === 'y' && ! empty($_GET['page_id'])) {
        TikiLib::lib('access')->redirect(TikiLib::lib('wiki')->sefurl($page));
    }

    static $called = false;
    if ($called) {
        return tr("Only one cypht plugin per page can be used.");
    }
    $called = true;

    if (empty($params['imap_port'])) {
        $params['imap_port'] = 993;
    }

    if (empty($params['imap_tls'])) {
        $params['imap_tls'] = 'y';
    }

    if (empty($params['smtp_port'])) {
        $params['smtp_port'] = 587;
    }

    if (empty($params['smtp_tls'])) {
        $params['smtp_tls'] = 'y';
    }

    if (empty($params['smtp_no_auth'])) {
        $params['smtp_no_auth'] = 'n';
    }

    if (empty($params['use_global_settings'])) {
        $params['use_global_settings'] = 'n';
    }

    if (empty($params['groupmail'])) {
        $params['groupmail'] = 'n';
    }

    if ($params['groupmail'] == 'y') {
        $perm = 'tiki_p_use_group_webmail';
    } else {
        $perm = 'tiki_p_use_personal_webmail';
    }
    if (! Perms::get()->$perm) {
        return tra("You do not have the permission that is needed to use this feature:") . " " . $perm;
    }

    $settings_per_page = false;
    if ($params['use_global_settings'] !== 'y') {
        $preference_name = substr('cypht_user_config_' . $page, 0, 40);
        $session_prefix = substr('cypht_' . $page, 0, 20);
        if ($params['use_global_settings'] === 'nw') {
            $settings_per_page = $page;
        }
    } else {
        $preference_name = 'cypht_user_config';
        $session_prefix = 'cypht';
    }

    if (
        empty($_SESSION[$session_prefix]['preference_name']) || $_SESSION[$session_prefix]['preference_name'] != $preference_name
        || (! empty($_SESSION[$session_prefix]['username']) && $_SESSION[$session_prefix]['username'] != $user)
        || ! empty($_REQUEST['clear_cache'])
    ) {
        // resetting the session on purpose - could be coming from tiki-webmail
        $_SESSION[$session_prefix] = [];
    }

    $_SESSION[$session_prefix]['preference_name'] = $preference_name;
    $_SESSION[$session_prefix]['settings_per_page'] = $settings_per_page;

    $_SESSION[$session_prefix]['groupmail'] = $params['groupmail'] ?? null;
    $_SESSION[$session_prefix]['group'] = $params['group'] ?? null;
    $_SESSION[$session_prefix]['trackerId'] = $params['trackerId'] ?? null;
    $_SESSION[$session_prefix]['fromFId'] = $params['fromFId'] ?? null;
    $_SESSION[$session_prefix]['subjectFId'] = $params['subjectFId'] ?? null;
    $_SESSION[$session_prefix]['messageFId'] = $params['messageFId'] ?? null;
    $_SESSION[$session_prefix]['contentFId'] = $params['contentFId'] ?? null;
    $_SESSION[$session_prefix]['accountFId'] = $params['accountFId'] ?? null;
    $_SESSION[$session_prefix]['datetimeFId'] = $params['datetimeFId'] ?? null;
    $_SESSION[$session_prefix]['operatorFId'] = $params['operatorFId'] ?? null;

    if ($settings_per_page) {
        if ($data) {
            $_SESSION[$session_prefix]['user_data'] = json_decode($data, true);
            $_SESSION[$session_prefix]['plugin_data'] = $data;
        } else {
            $data = TikiLib::lib('tiki')->get_user_preference('%', $preference_name);
            $_SESSION[$session_prefix]['user_data'] = json_decode($data, true);
        }
    }

    $_SESSION[$session_prefix]['page_id'] = $tikilib->get_page_id_from_name($page);

    require_once $tikipath . '/lib/cypht/integration/classes.php';

    if (empty($_SESSION[$session_prefix]['request_key'])) {
        $_SESSION[$session_prefix]['request_key'] = Hm_Crypt::unique_id();
    }
    $_SESSION[$session_prefix]['username'] = $user;

    TikiLib::lib('header')->add_css("
        .inline-cypht { position: relative; }
    ");

    /* get configuration */
    $config = new Tiki_Hm_Site_Config_File([], $session_prefix, $settings_per_page);
    $environment->define_default_constants($config);

    // merge existing configuration with plugin params for smtp/imap servers
    if (! empty($params['imap_server']) && ! empty($params['imap_username']) && ! empty($params['imap_password'])) {
        $attributes = [
            'name' => empty($params['imap_name']) ? $params['imap_username'] : $params['imap_name'],
            'server' => $params['imap_server'],
            'port' => $params['imap_port'],
            'tls' => $params['imap_tls'] == 'y' ? '1' : '0',
            'user' => $params['imap_username'],
            'pass' => $params['imap_password']
        ];
        if (empty($_SESSION[$session_prefix]['user_data']['imap_servers'])) {
            $_SESSION[$session_prefix]['user_data']['imap_servers'] = [];
        }
        $found = false;
        foreach ($_SESSION[$session_prefix]['user_data']['imap_servers'] as $server) {
            if ($server['server'] == $attributes['server'] && $server['tls'] == $attributes['tls'] && $server['port'] == $attributes['port'] && $server['user'] == $attributes['user']) {
                $found = true;
                break;
            }
        }
        if (! $found) {
            do {
                $id = uniqid();
            } while (isset($_SESSION[$session_prefix]['user_data']['imap_servers'][$id]));
            $_SESSION[$session_prefix]['user_data']['imap_servers'][$id] = $attributes;
        }
    }

    if (! empty($params['smtp_server']) && ! empty($params['smtp_username']) && ! empty($params['smtp_password'])) {
        $attributes = [
            'name' => empty($params['smtp_name']) ? $params['smtp_username'] : $params['smtp_name'],
            'default' => true,
            'server' => $params['smtp_server'],
            'port' => $params['smtp_port'],
            'tls' => $params['smtp_tls'] == 'y',
            'user' => $params['smtp_username'],
            'pass' => $params['smtp_password']
        ];
        if ($params['smtp_no_auth'] == 'y') {
            $attributes['no_auth'] = true;
        }
        if (empty($_SESSION[$session_prefix]['user_data']['smtp_servers'])) {
            $_SESSION[$session_prefix]['user_data']['smtp_servers'] = [];
        }
        $found = false;
        foreach ($_SESSION[$session_prefix]['user_data']['smtp_servers'] as $server) {
            if ($server['server'] == $attributes['server'] && $server['tls'] == $attributes['tls'] && $server['port'] == $attributes['port'] && $server['user'] == $attributes['user']) {
                $found = true;
                break;
            }
        }
        if (! $found) {
            do {
                $id = uniqid();
            } while (isset($_SESSION[$session_prefix]['user_data']['smtp_servers'][$id]));
            $_SESSION[$session_prefix]['user_data']['smtp_servers'][$id] = $attributes;
        }
    }

    /* process the request */
    $dispatcher = new Hm_Dispatch($config);

    if (! empty($_SESSION[$session_prefix]['user_data']['debug_mode_setting'])) {
        $msgs = Hm_Debug::get();
        foreach ($msgs as $msg) {
            $logslib->add_log('cypht', $msg);
        }
    }

    $headerlib->add_js_module('import "@jquery-tiki/plugins/cypht";');

    return '<div class="inline-cypht">'
        . '<input type="hidden" id="hm_page_key" value="' . Hm_Request_Key::generate() . '" />'
        . '<input type="hidden" id="hm_session_prefix" value="' . htmlentities($session_prefix, ENT_COMPAT) . '" />'
        . $dispatcher->session->dedup_page_links($dispatcher->output)
        . "</div>";
}
