<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function prefs_schema_list()
{
    return [
        'schema_enable' => [
            'name' => tra('Schema.org structured data'),
            'description' => tra('Enable JSON-LD structured data markup for rich search results using Schema.org vocabulary.'),
            'type' => 'flag',
            'help' => 'https://schema.org/',
            'default' => 'n',
            'since' => '30',
            'tags' => ['advanced'],
        ],
    ];
}