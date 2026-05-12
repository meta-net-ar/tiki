<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_cas_list()
{
    $casAuthenticationHelp = 'CAS-Authentication';

    return [
        'cas_create_user_tiki' => [
            'name' => tra('Create user if not registered in Tiki'),
            'type' => 'flag',
            'help' => $casAuthenticationHelp,
            'description' => tr('If a user was externally authenticated, but not found in the Tiki user database, Tiki will create an entry in its user database.'),
            'perspective' => false,
            'default' => 'n',
            ],
        'cas_autologin' => [
            'name' => tra('Try automatically to connect SSO'),
            'type' => 'flag',
            'help' => $casAuthenticationHelp,
            'description' => tra('When enabled, users are automatically logged in via CAS (SSO) without manual action.'),
            'perspective' => false,
            'default' => 'n',
            ],
        'cas_skip_admin' => [
            'name' => tra('Use Tiki authentication for Admin log-in'),
            'type' => 'flag',
            'help' => $casAuthenticationHelp,
            'description' => tra('The user “admin” will be authenticated by <b>only</b> using Tiki’s user database. This option has no effect on users other than “admin”.'),
            'perspective' => false,
            'default' => 'n',
            ],
        'cas_force_logout' => [
            'name' => tra('Force CAS log-out when the user logs out from Tiki.'),
            'type' => 'flag',
            'help' => $casAuthenticationHelp,
            'description' => tra('When enabled, users are logged out of both Tiki and CAS when they log out.'),
            'perspective' => false,
            'default' => 'n',
            ],
        'cas_show_alternate_login' => [
            'name' => tra('Show alternate log-in method in header'),
            'type' => 'flag',
            'help' => $casAuthenticationHelp,
            'description' => tra('When enabled, a non-CAS login option is shown in the header.'),
            'perspective' => false,
            'default' => 'y',
            ],
        'cas_version' => [
            'name' => tra('CAS server version'),
            'type' => 'list',
            'help' => $casAuthenticationHelp,
            'description' => tra('Select the CAS protocol version used when initializing the CAS client connection.'),
            'perspective' => false,
            'options' => [
                'none' => tra('none'),
                '1.0'  => tra('Version 1.0'),
                '2.0'  => tra('Version 2.0'),
                ],
            'default' => '1.0',
            ],
        'cas_hostname' => [
            'name' => tra('Hostname'),
            'description' => tra('Hostname of the CAS server.'),
            'type' => 'text',
            'help' => $casAuthenticationHelp,
            'size' => 50,
            'filter' => 'striptags',
            'perspective' => false,
            'default' => '',
            ],
        'cas_port' => [
            'name' => tra('Port'),
            'description' => tra('Port of the CAS server.'),
            'type' => 'text',
            'help' => $casAuthenticationHelp,
            'size' => 5,
            'filter' => 'digits',
            'perspective' => false,
            'default' => '443',
            ],
        'cas_path' => [
            'name' => tra('Path'),
            'description' => tra('Path for the CAS server.'),
            'type' => 'text',
            'help' => $casAuthenticationHelp,
            'size' => 50,
            'filter' => 'striptags',
            'perspective' => false,
            'default' => '',
            ],
        'cas_extra_param' => [
            'name' => tra('CAS Extra Parameter'),
            'description' => tra('Extra Parameter to pass to the CAS Server.'),
            'type' => 'text',
            'help' => $casAuthenticationHelp,
            'size' => 100,
            'filter' => 'striptags',
            'perspective' => false,
            'default' => '',
            ],
        'cas_authentication_timeout' => [
            'name' => tra('CAS Authentication Verification Timeout'),
            'description' => tra('Verify authentication with the CAS server every N seconds. Null value means never reverify.'),
            'type' => 'list',
            'help' => $casAuthenticationHelp,
            'filter' => 'digits',
            'perspective' => false,
            'options' => [
                '0' => tra('Never'),
                '60' => '1 ' . tra('minute'),
                '120' => '2 ' . tra('minutes'),
                '300' => '5 ' . tra('minutes'),
                '600' => '10 ' . tra('minutes'),
                '900' => '15 ' . tra('minutes'),
                '1800' => '30 ' . tra('minutes'),
                '3600' => '1 ' . tra('hour'),
                ],
            'default' => '0',
            ],
        ];
}
