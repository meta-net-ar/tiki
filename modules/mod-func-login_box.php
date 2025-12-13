<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (str_contains($_SERVER["SCRIPT_NAME"], basename(__FILE__))) {
    header("location: index.php");
    exit;
}

//aris002 CHECK if we really can't avoid this?
require_once('lib/socnets/PrefsGen.php');
use TikiLib\Socnets\PrefsGen\PrefsGen;

/**
 * @return array
 */
function module_login_box_info()
{
    return [
        'name' => tra('Log In'),
        'description' => tra('Log-in box'),
        'prefs' => [],
        'documentation' => 'Module login_box',
        'params' => [
            'input_size' => [
                'name' => tra('Input size'),
                'description' => tra('Number of characters for username and password input fields.'),
                'filter' => 'int'
            ],
            'mode' => [
                'name' => tra('Mode'),
                'description' => tra('Display mode: module, header or popup. Leave empty for module mode'),
            ],
            'show_two_factor_auth' => [
                'name' => tra('2FA'),
                'description' => tra('Display two-factor authentication code input.'),
            ],
            'register' => [
                'name' => tra('Show Register'),
                'description' => tra('Show the register link') . ' (y/n)',
                'filter' => 'alpha',
            ],
            'register_page' => [
                'name' => tra('Register Page'),
                'description' => tra('Page to redirect when the register link is clicked. If not given, the default register page is used.'),
                'filter' => 'pagename',
                'profile_reference' => 'wiki_page',
            ],
            'forgot' => [
                'name' => tra('Show I Forgot'),
                'description' => tra('Show the "I forgot my password" link') . ' (y/n)',
                'filter' => 'alpha',
            ],
            'remember' => [
                'name' => tra('Show Remember me'),
                'description' => tra('Show the "Remember me" checkbox') . ' (y/n)',
                'filter' => 'alpha',
            ],
            'allowNone' => [
                'name' => tra('Allow None'),
                'description' => tra('Allow user selection field to be empty (without any users selected) when enabled.') . ' (y/n)',
                'filter' => 'alpha',
            ],
            'show_user_avatar' => [
                'name' => tra('Show user avatar'),
                'description' => tra('Show the user avatar when in popup mode') . ' (y/n)',
                'filter' => 'alpha',
            ],
            'show_user_name' => [
                'name' => tra('Show user name'),
                'description' => tra('Show the user name when in popup mode') . ' (y/n)',
                'filter' => 'alpha',
            ],
            'groups' => [
                'name' => tra('Groups for switch user listing'),
                'description' => tra('If this parameter is empty, all users are offered in the Switch user drop-down. If this parameter is set to a list of user groups, a user is only offered if it is member of at least one of these. A set of groups is specified with their identifiers (integers) separated by pipe characters ("|").'),
            ],
            'menu_id' => [
                'name' => tra('Menu ID'),
                'description' => tra('Menu to use as the dropdown in "popup" mode. Defaults to a built in menu with only "My Account" and "Logout"'),
                'filter' => 'int',
            ],
            'start_session' => [
                'name' => tra('Start Session'),
                'description' => tra('If the preference "silent_session" is enabled start the session when the login form is shown to avoid CSRF errors.') . ' (y/n)',
                'filter' => 'alpha',
            ],
        ]
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_login_box($mod_reference, &$module_params)
{
    global $prefs, $base_url_https, $base_url, $user_cookie_site;
    $smarty = TikiLib::lib('smarty');
    $tikilib = \TikiLib::lib('tiki');
    static $module_logo_instance = 0;

    $module_logo_instance++;

    if ($prefs['session_silent'] === 'y' && empty($_COOKIE[session_name()]) && ! empty($module_params['start_session']) && $module_params['start_session'] === 'y') {
        // start a basic session so the user can login without a CSRF ticket error, then tiki-setup_base.php will tidy it up on the next page load
        Laminas\Session\Container::getDefaultManager()->start();
    }

    if ($prefs['auth_webauthn_enabled'] === 'y') {
        TikiLib::lib('header')->add_jsfile('lib/jquery_tiki/tiki-webauthn.js');
    }

    $smarty->assign('module_logo_instance', $module_logo_instance);
    $smarty->assign('mode', $module_params['mode'] ?? 'module');
    $smarty->assign('login_text_explanation', $tikilib->get_preference('login_text_explanation'));

    $urlPrefix = in_array($prefs['https_login'], ['encouraged', 'required', 'force_nocheck']) ? $base_url_https : $base_url;
    $smarty->assign('registration', 'n');   // stops the openid form appearing in the module, only on tiki-login_scr.php
    $can_switch_user = 'y';
    $perms = Perms::get();
    if (! $perms->admin) {
        $previous_username = $_SESSION[$user_cookie_site . '_previous'] ?? null;
        if ($previous_username) {
            $permsContext = new Perms_Context($previous_username);
            $permsPrevious = Perms::get();
            if (! $permsPrevious->admin) {
                $can_switch_user = 'n';
            }
            // restore the current "switched to" user's permissions
            unset($permsContext);
        } else {
            $can_switch_user = 'n';
        }
    }
    $smarty->assign(
        'login_module',
        [
            'login_url' => $urlPrefix . $prefs['login_url'],
            'can_revert' => TikiLib::lib('login')->isSwitched(),
            'can_switch_user' => $can_switch_user,
        ]
    );

    if ($prefs['auth_method'] == 'openid_connect') {
        $openIdConnectLib = TikiLib::lib('openidconnect');
        if ($openIdConnectLib->isAvailable()) {
            $smarty->assign(
                'openidconnect_redirect_url',
                $openIdConnectLib->generateURL()
            );
        }
    }

    if ($prefs['feature_socialnetworks'] === 'y') {
        $smarty->assign('socnetsAll', PrefsGen::getHybridProvidersPHP());
    }
    if ($prefs['allowRegister'] === 'y' && (empty($module_params['register']) || $module_params['register'] === 'y')) {
        $module_params['show_register'] = 'y';
    } else {
        $module_params['show_register'] = 'n';
    }
    if ($prefs['forgotPass'] === 'y' && $prefs['change_password'] === 'y' && (empty($module_params['forgot']) || $module_params['forgot'] === 'y')) {
        $module_params['show_forgot'] = 'y';
    } else {
        $module_params['show_forgot'] = 'n';
    }
    if (! isset($module_params['groups'])) {
        $module_params['groups'] = '';
    }
    if (! isset($module_params['allowNone'])) {
        $module_params['allowNone'] = 'y';
    }
    if (! isset($module_params['show_user_avatar'])) {
        $module_params['show_user_avatar'] = 'y';
    }
}
