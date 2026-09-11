<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_allowmsg_list()
{
    $interUserMessagesHelp = 'Inter-user-Messages';

    return [
        'allowmsg_by_default' => [
            'name' => tra('Users accept internal messages by default'),
            'description' => tra('Default value for whether users accept internal messages from other users when they have not set their own preference.'),
            'type' => 'flag',
            'help' => $interUserMessagesHelp,
            'dependencies' => [
                'feature_messages',
            ],
            'default' => 'y',
        ],
        'allowmsg_is_optional' => [
            'name' => tra('Users can opt out of internal messages'),
            'description' => tra('Allow each user to choose whether to accept internal messages in their preferences. When disabled, all users receive messages and cannot opt out.'),
            'type' => 'flag',
            'help' => $interUserMessagesHelp,
            'dependencies' => [
                'feature_messages',
            ],
            'default' => 'y',
        ],
    ];
}
