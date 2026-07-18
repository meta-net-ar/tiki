<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_section_list()
{
    return [
        'section_comments_parse' => [
            'name' => tra('Parse wiki syntax in comments'),
            'description' => tr('Parse wiki syntax in comments in all sections apart from Forums. %0 Use "Accept wiki syntax" for forums in admin forums page', '<br>'),
            'type' => 'flag',
            'help' => 'Wiki-syntax',
            'default' => 'y',       // parse wiki markup on comments in all sections
            'help' => 'Wiki-syntax',
        ],
    ];
}
