<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_lock_list()
{
    return  [
        'lock_content_templates' => [
            'name' => tra('Lock Content Templates'),
            'description' => tra('Enable users to lock content templates and so prevent others from editing them'),
            'type' => 'flag',
            'help' => 'Content-Templates#Activate_the_feature',
            'default' => 'n',
        ],
        'lock_wiki_structures' => [
            'name' => tra('Lock Wiki Structures'),
            'description' => tra('Enable users to lock wiki structures and so prevent others from editing them'),
            'type' => 'flag',
            'default' => 'n',
            'help' => 'Structures#Enable_the_Featuree',
        ],
    ];
}
