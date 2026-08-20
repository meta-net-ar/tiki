<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Note this file is redundant in Tiki 11 and 12 (php 5.3 required)
 * extensions are now being checked using the extensions array in the pref definition
 * TODO remove in Tiki 13
 */


function prefs_php_list()
{
    return [
        'php_libxml' => [
            'name' => tra('PHP libxml extension'),
            'description' => tra(
                'This extension requires the libxml PHP extension.
                This means that passing in --enable-libxml is also required, although this is
                implicitly accomplished because libxml is enabled by default.'
            ),
            'type' => 'flag',
            'default' => class_exists('DOMDocument') ? 'y' : 'n',
        ],
        'php_datetime' => [
            'name' => tra('PHP DateTime'),
            'description' => tra(
                'DateTime class (and related functions) are enabled
                by default since PHP 5.2.0.'
            ),
            'type' => 'flag',
            'default' => class_exists('DateTime') ? 'y' : 'n',
        ],
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
