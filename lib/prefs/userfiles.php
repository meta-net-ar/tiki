<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_userfiles_list()
{
    $userFilesHelp = 'User-Files';

    return [
        'userfiles_quota' => [
            'name' => tra('Quota'),
            'description' => tra('Maximum storage per user for personal files and notepad content, in megabytes. Also applied as the quota on each user file gallery.'),
            'type' => 'text',
            'size' => 5,
            'units' => tra('megabytes'),
            'default' => 30,
            'dependencies' => [
                'feature_userfiles',
            ],
            'help' => $userFilesHelp,
        ],
        'userfiles_private' => [
            'name' => tra('Private'),
            'description' => tra("Users cannot see each other's files or galleries"),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'feature_userfiles',
            ],
            'help' => $userFilesHelp,
        ],
        'userfiles_hidden' => [
            'name' => tra('Hidden'),
            'description' => tra("Users can see each other's files, but don't see the galleries in listings"),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'feature_userfiles',
            ],
            'help' => $userFilesHelp,
        ],
    ]; // TODO: update userfiles help page
}
