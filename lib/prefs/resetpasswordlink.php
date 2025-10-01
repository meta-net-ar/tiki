<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_resetpasswordlink_list()
{
    return [
        'resetpasswordlink_expiry' => [
            'name' => tra('Reset password token expiry time'),
            'description' => tra('Time in minutes before a reset password link expires.'),
            'type' => 'text',
            'default' => 60,
            'filter' => 'digits',
        ],
    ];
}
