<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_ldap_list()
{
    $ldapAuthenticationHelp = 'LDAP-authentication';

    return [
        'ldap_create_user_tiki' => [
            'name' => tra('Create user if not registered in Tiki'),
            'description' => tr('If a user was externally authenticated, but not found in the Tiki user database, Tiki will create an entry in its user database.'),
            'type' => 'list',
            'warning' => tra('If this option is disabled, this user wouldn’t be able to log in.'),
            'perspective' => false,
            'help' => $ldapAuthenticationHelp,
            'options' => [
                'y' => tra('Create the user'),
                'n' => tra('Deny access'),
            ],
            'default' => 'y',
        ],
        'ldap_create_user_ldap' => [
            'name' => tra('Create user if not in LDAP'),
            'description' => tra('If a user was authenticated by Tiki’s user database, but not found on the LDAP server, Tiki will create an LDAP entry for this user.'),
            'type' => 'flag',
            'default' => 'n',
            'help' => $ldapAuthenticationHelp,
            'warning' => tra('As of this writing, this is not yet implemented, and this option will probably not be offered in future.'),
            'tags' => ['experimental'],
        ],
        'ldap_create_user_tiki_validation' => [
            'name' => tra('Require admin validation for LDAP users'),
            'description' => tr('When externally authenticated user is created in Tiki database either allow immediate login or create in disabled state that requires an administrator to approve the account before user can login to Tiki.'),
            'type' => 'flag',
            'perspective' => false,
            'help' => $ldapAuthenticationHelp,
            'default' => 'n',
            'dependencies' => ['ldap_create_user_tiki'],
        ],
        'ldap_skip_admin' => [
            'name' => tra('Use Tiki authentication for Admin log-in'),
            'description' => tra('If this option is set, the user “admin” will be authenticated by only using Tiki’s user database and not via LDAP. This option has no effect on users other than “admin”.'),
            'type' => 'flag',
            'default' => 'y',
            'help' => $ldapAuthenticationHelp,
        ],
    ];
}
