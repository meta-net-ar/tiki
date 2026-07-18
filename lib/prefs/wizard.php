<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_wizard_list()
{
    return [
        'wizard_admin_hide_on_login' => [
            'name' => tra('Hide admin wizard on log-in when an admin user logs in'),
            'description' => tra('When enabled, administrators are not redirected to the setup wizard after logging in.'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['basic'],
            'help' => 'Admin-Wizard',
        ],
    ];
}
