<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_usercount_info()
{
    return [
        'name' => tra('User Count'),
        'documentation' => 'PluginUserCount',
        'description' => tra('Display number of users for a site or one or more groups'),
        'prefs' => [ 'wikiplugin_usercount' ],
        'body' => tra('Group name. If left blank, the total number of users for the site will be shown.'),
        'iconname' => 'group',
        'introduced' => 1,
        'params' => [
            'groups' => [
                'required' => false,
                'name' => tra('Groups'),
                'description' => tra('List of colon separated groups where a consolidated user count for multiple
                    groups is needed. Users in multiple groups are counted only once. If left blank then the behaviour
                    is defined by the body parameter settings.'),
                'since' => '14.1',
                'separator' => ':',
                'filter' => 'groupname',
                'default' => '',
            ],
        ],
    ];
}

function wikiplugin_usercount($data, $params)
{
    $userlib = TikiLib::lib('user');

    if (! empty($params['groups'])) {
        $numusers = $userlib->count_users_consolidated($params['groups']);
    } else {
        $numusers = $userlib->count_users($data);
    }

    return $numusers;
}
