<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.


function prefs_php_list()
{
    return [
        'php_cli_path' => [
            'name' => tra('Path to the php binary'),
            'description' => tra(
                'Path to the php command line binary to be used by tiki when calling command line programs'
            ),
            'keywords' => 'command line php path',
            'type' => 'text',
            'default' => '',
            'tags' => ['advanced'],
            'warning' => tra('Security-sensitive setting. An incorrect path can lead to arbitrary command execution under the web server account.'),
            'hint' => tra('Set this using system configuration (tiki.ini) so only server administrators can configure it. Hidden by default as a risky preference.'),
            'help' => 'General-Settings',
        ],
    ];
}
