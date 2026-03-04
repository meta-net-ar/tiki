<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function wikiplugin_livesupport_info()
{
    return [
        'name' => tra('Live Support'),
        'documentation' => 'PluginLiveSupport',
        'description' => tra('This plugin adds an online Support Request button to a wiki page'),
        'prefs' => [ 'wikiplugin_livesupport' ],
        'iconname' => 'message',
        'format' => 'html',
        'introduced' => 25,
        'params' => [
            'check_operators_online' => [
                'required' => true,
                'name' => tra('Check Operators Online'),
                'description' => tr('Show live chat only if operator is available'),
                'since' => '26.0',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'operator_groups' => [
                'required' => false,
                'name' => tra('Group'),
                'description' => tra('Group that acts as live support operators'),
                'since' => '26.0',
                'filter' => 'groupname',
                'default' => 'admin'
            ],
            'leave_message' => [
                'required' => true,
                'name' => tra('Leave message'),
                'description' => tr('leave message if no operator is available'),
                'since' => '26.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
        ],
    ];
}

function wikiplugin_livesupport($data, $params)
{
    global $prefs;
    global $lslib;
    global $lsadminlib;
    global $user;
    include_once('lib/live_support/lsadminlib.php');
    include_once('lib/live_support/lslib.php');
    $smarty = TikiLib::lib('smarty');


    if ($prefs['feature_live_support'] !== 'y') {
        return tra('You cannot use this plugin until the feature live support is activated');
    }

    if ($lsadminlib->is_operator($user)) {
        $smarty->assign('user_is_operator', 'y');
    } else {
        $smarty->assign('user_is_operator', 'n');
    }

    $smarty->assign('operators_online', $lslib->operators_online());
    $smarty->assign('leave_message', $params['leave_message']);
    $ret = $smarty->fetch('wiki-plugins/wikiplugin_livesupport.tpl');
    return $ret;
}
