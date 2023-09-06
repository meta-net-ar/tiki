<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_userlastlogged_info()
{
    return [
        'name'          => tra('Last Login information'),
        'documentation' => tra('PluginUserLastLogged'),
        'description'   => tra('Show the last login information for a given or current user'),
        'iconname'      => 'user',
        'introduced'    => 13,
        'params'        => [
            'user'        => [
                'required'    => false,
                'name'        => tra('Username'),
                'description' => tra(
                    'Username to display last login information for. Current user information shown
                    if left blank.'
                ),
                'since'       => '13.0',
                'filter'      => 'username',
            ],
            'date_format' => [
                'required'    => false,
                'name'        => tra('DateFormat'),
                'description' => tra('Date format setting. Short_datetime used by default'),
                'since'       => '16.0',
                'filter'      => 'dateformat',
                'options'     => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Long date'), 'value' => 'long_date'],
                    ['text' => tra('Short date'), 'value' => 'short_date'],
                    ['text' => tra('Long time'), 'value' => 'long_time'],
                    ['text' => tra('Short time'), 'value' => 'short_time'],
                    ['text' => tra('Long datetime'), 'value' => 'long_datetime'],
                    ['text' => tra('Short datetime'), 'value' => 'short_datetime'],
                    ['text' => tra('ISO8601 Datetime'), 'value' => 'iso8601_datetime'],
                    ['text' => tra('Compact iso8601 Datetime'), 'value' => 'compact_iso8601_datetime'],
                ]
            ],
        ],
    ];
}

function wikiplugin_userlastlogged($data, $params)
{
    global $user;
    $userlib = TikiLib::lib('user');
    $tikilib = TikiLib::lib('tiki');

    $allowed_methods = ['long_date', 'short_date', 'long_time', 'short_time', 'long_datetime', 'short_datetime', 'iso8601_datetime',
                        'compact_iso8601_datetime'];

    if (! empty($params['user'])) {
        $info = $userlib->get_user_info($params['user']);
    } else {
        $info = $userlib->get_user_info($user);
    }

    if (! empty($params['date_format']) && in_array($params['date_format'], $allowed_methods)) {
        $functionName = 'get_' . $params['date_format'];
        if (method_exists($tikilib, $functionName)) {
            return $tikilib->$functionName($info['lastLogin']);
        } elseif ($params['date_format'] == 'timestamp') {
            return $info['lastLogin'];
        } else {
            return $tikilib->get_short_datetime($info['lastLogin']);
        }
    } else {
        return $tikilib->get_short_datetime($info['lastLogin']);
    }
}
