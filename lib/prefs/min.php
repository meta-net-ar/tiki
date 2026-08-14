<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_min_list()
{
    $loginGeneralPreferencesHelp = 'Login-General-Preferences';

    return [
        'min_username_length' => [
            'name' => tra('Minimum length'),
            'description' => tra('The least possible number of characters for a valid username.'),
            'type' => 'text',
            'size' => 5,
            'filter' => 'digits',
            'units' => tra('characters'),
            'default' => 1,
            'help' => $loginGeneralPreferencesHelp,
        ],
        'min_pass_length' => [
            'name' => tra('Minimum length'),
            'description' => tra('The least possible number of characters for a valid password.'),
            'type' => 'text',
            'size' => 5,
            'filter' => 'digits',
            'units' => tra('characters'),
            'default' => 5,
            'help' => $loginGeneralPreferencesHelp,
        ],
    ];
}
