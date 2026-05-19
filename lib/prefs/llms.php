<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_llms_list()
{

    return [
        'llms_txt_enable' => [
            'name' => tra('LLMS.txt'),
            'description' => tra('Provide a plain text index of site content at /llms.txt'),
            'type' => 'flag',
            'help' => 'https://llmstxt.org/',
            'default' => 'n',
            'since' => '30',
            'admin' => 'seoprefs',
        ],
        'llms_md_enable' => [
            'name' => tra('LLMS markdown content'),
            'description' => tra('When enabled makes /llms.txt link to markdown .md URLs instead of HTML pages.'),
            'type' => 'flag',
            'default' => 'n',
            'since' => '30',
            'admin' => 'seoprefs',
        ],
    ];
}
