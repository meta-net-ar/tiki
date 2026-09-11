<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_w_list()
{
    $wikiAttachmentsHelp = 'Wiki-Attachments';

    return [
        'w_displayed_default' => [
            'name' => tra('Display by default'),
            'description' => tra('Show the wiki page attachments section expanded by default.'),
            'type' => 'flag',
            'default' => 'n',
            'help' => $wikiAttachmentsHelp,
        ],
        'w_use_dir' => [
            'name' => tra('Path (if stored in directory)'),
            'description' => tra('Directory on this server where wiki attachment files are stored when Storage is set to directory. PHP must be able to read and write to this path.'),
            'type' => 'text',
            'size' => '20',
            'perspective' => false,
            'default' => '',
            'help' => $wikiAttachmentsHelp,
        ],
        'w_use_db' => [
            'name' => tra('Storage'),
            'description' => tra('Where to store wiki page attachments when file galleries are not used for attachments.'),
            'type' => 'list',
            'perspective' => false,
            'options' => [
                'y' => tra('Store in database'),
                'n' => tra('Store in directory'),
            ],
            'default' => 'y',
            'help' => $wikiAttachmentsHelp,
        ],
    ];
}
