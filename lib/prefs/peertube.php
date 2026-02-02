<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_peertube_list()
{
    return [
        'peertube_service_url' => [
            'name' => tra('PeerTube service URL'),
            'description' => tra('Your PeerTube instance URL. More information: https://joinpeertube.org/'),
            'type' => 'text',
            'size' => 40,
            'default' => '',
            'tags' => ['basic'],
        ],
        'peertube_username' => [
            'name' => tra('Username'),
            'description' => tra('PeerTube username for video uploads'),
            'type' => 'text',
            'size' => 45,
            'default' => '',
            'tags' => ['basic'],
        ],
        'peertube_password' => [
            'name' => tra('Password'),
            'description' => tra('PeerTube password for video uploads'),
            'type' => 'password',
            'size' => 45,
            'default' => '',
            'tags' => ['basic'],
        ],
        'peertube_player_id' => [
            'name' => tra('PeerTube player ID'),
            'description' => tra('PeerTube player configuration ID (if applicable)'),
            'type' => 'text',
            'size' => 20,
            'default' => '',
            'tags' => ['basic'],
        ],
    ];
}
