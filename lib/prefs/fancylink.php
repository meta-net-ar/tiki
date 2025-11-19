<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_fancylink_list()
{
    return [
        'wikiplugin_fancylink' => [
            'name' => tra('Fancy Link'),
            'description' => tra('Display rich previews of external URLs in wiki content'),
            'type' => 'flag',
            'default' => 'n',
            'tags' => ['basic'],
            'help' => 'PluginFancyLink',
            'view' => 'tiki-admin.php?page=wiki',
        ],
    ];
}
