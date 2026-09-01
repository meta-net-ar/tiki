<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_messages_unread_messages_info()
{
    return [
        'name' => tra('Unread Inter-User Messages'),
        'description' => tra('Displays to users their number of new inter-user messages and a link to their message box.'),
        'prefs' => ['feature_messages'],
        'params' => [
            'showempty' => [
                'name' => tra('Show If Empty'),
                'description' => tra('Show the module when there are no messages waiting.'),
                'required' => false,
                'options' => [
                    ['text' => tra('Default: Yes'), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ]
        ]
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_messages_unread_messages($mod_reference, $module_params)
{
    global $user;
    $globalperms = Perms::get();
    $smarty = TikiLib::lib('smarty');
    $tikilib = TikiLib::lib('tiki');
    if ($user && $globalperms->messages) {
        $modUnread = $tikilib->user_unread_messages($user);
        if ($modUnread > 0 || ! isset($module_params['showempty']) || $module_params['showempty'] == 'y') {
            $smarty->assign('modUnread', $modUnread);
            $smarty->assign('tpl_module_title', tra("Messages"));
        }
    }
}
