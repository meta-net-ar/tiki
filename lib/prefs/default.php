<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_default_list()
{

    $calendarlib = TikiLib::lib('calendar');
    $cals = $calendarlib->list_calendars();
    if (array_key_exists('data', $cals)) {
        $cals = array_column($cals['data'], 'name', 'calendarId');
    } else {
        $cals = [];
    }

    $forumAdminHelp = 'Forum-Admin';

    return [
        'default_mail_charset' => [
            'name' => tra('Default character set for sending mail'),
            'description' => tra('Specify the character encoding used by Tiki when sending mail notifications.'),
            'type' => 'list',
            'help' => 'Mail-notifications',
            'options' => [
                'utf-8' => tra('utf-8'),
                'iso-8859-1' => tra('iso-8859-1'),
            ],
            'default' => 'utf-8',
        ],
        'default_map' => [
            'name' => tra('default mapfile'),
            'description' => tra('Default MapServer map file for the MAP plugin (only if you use MapServer).'),
            'type' => 'text',
            'size' => '50',
            'help' => 'Maps',
            'default' => '',
        ],
        'default_wiki_diff_style' => [
            'name' => tra('Default diff style'),
            'description' => tra('How differences between wiki page versions are shown when viewing history or comparing revisions.'),
            'type' => 'list',
            'help' => 'Wiki-Features',
            'options' => [
                'old' => tra('Only with last version'),
                'htmldiff' => tra('HTML diff'),
                'sidediff' => tra('Side-by-side diff'),
                'sidediff-char' => tra('Side-by-side diff by characters'),
                'inlinediff' => tra('Inline diff'),
                'inlinediff-char' => tra('Inline diff by characters'),
                'sidediff-full' => tra('Full side-by-side diff'),
                'sidediff-full-char' => tra('Full side-by-side diff by characters'),
                'inlinediff-full' => tra('Full inline diff'),
                'inlinediff-full-char' => tra('Full inline diff by characters'),
                'unidiff' => tra('Unified diff'),
                'sideview' => tra('Side-by-side view'),
            ],
            'default' => 'sidediff',
        ],
        'default_rows_textarea_wiki' => [
            'name' => tra('Wiki'),
            'description' => tra('Size (height) of the wiki text area.'),
            'type' => 'text',
            'help' => 'Wiki-Page-Editor',
            'size' => '3',
            'units' => tra('rows'),
            'filter' => 'digits',
            'default' => '20',
        ],
        'default_rows_textarea_comment' => [
            'name' => tra('Comment box'),
            'type' => 'text',
            'help' => 'Comments',
            'description' => tr('Size (height) of the comment text area.'),
            'size' => '3',
            'units' => tra('rows'),
            'filter' => 'digits',
            'default' => '6',
        ],
        'default_rows_textarea_forum'       => [
            'name' => tra('Forum'),
            'description' => tra('Size (height) of the forum text area.'),
            'type' => 'text',
            'help' => $forumAdminHelp,
            'size' => '3',
            'units' => tra('rows'),
            'filter' => 'digits',
            'default' => '20',
        ],
        'default_rows_textarea_forumthread' => [
            'name' => tra('Forum reply'),
            'type' => 'text',
            'description' => tra('Size (height) of the forum reply text area.'),
            'help' => $forumAdminHelp,
            'size' => '3',
            'units' => tra('rows'),
            'filter' => 'digits',
            'default' => '10',
        ],
        'default_calendars'                 => [
            'name' => tra('Select default calendars to display'),
            'description' => tra('Calendars shown by default.'),
            'type' => 'multicheckbox',
            'help' => 'Calendar',
            'options' => $cals,
            'default' => [],
        ],
        'default_group_transitions'         => [
            'name'         => tra('Group transition sets default'),
            'description'  => tra("Sets the group transitioned to as the user's default group."),
            'type'         => 'flag',
            'help'         => 'Group-Transitions',
            'default'      => 'n',
            'dependencies' => ['feature_group_transition'],
        ],
    ];
}
