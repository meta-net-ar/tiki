<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_group_info()
{
    return [
        'name' => tra('Group'),
        'documentation' => 'PluginGroup',
        'description' => tra('Display content based on the user\'s groups or friends'),
        'body' => tr('Wiki text to display if conditions are met. The body may contain %0. Text after the marker
            will be displayed to users not matching the conditions.', '<code>{ELSE}</code>'),
        'prefs' => ['wikiplugin_group'],
        'iconname' => 'group',
        'filter' => 'wikicontent',
        'introduced' => 1,
        'tags' => [ 'basic' ],
        'params' => [
            'groups' => [
                'required' => false,
                'name' => tra('Allowed Groups'),
                'description' => tra('Select one or more groups allowed to view the block.'),
                'since' => '1',
                'filter' => 'groupname',
                'separator' => '|',
                'profile_reference' => 'group',
            ],
            'users' => [
                'required' => false,
                'name' => tra('Allowed Users'),
                'description' => tra('Select one or more users allowed to view the block.'),
                'since' => '27',
                'filter' => 'username',
                'separator' => '|',
                'profile_reference' => 'user'
            ],
            'notgroups' => [
                'required' => false,
                'name' => tra('Denied Groups'),
                'description' => tra('Select one or more groups not allowed to view the block.'),
                'since' => '1',
                'filter' => 'groupname',
                'separator' => '|',
                'profile_reference' => 'group',
            ],
            'friends' => [
                'required' => false,
                'name' => tra('Allowed User Friends'),
                'description' => tr('Select one or more users. Friends of these selected users will be allowed to view the block.'),
                'since' => '4.0',
                'filter' => 'username',
                'separator' => '|',
                'profile_reference' => 'user'
            ],
            'pending' => [
                'required' => false,
                'name' => tra('Allowed Groups Pending Membership'),
                'description' => tra('Select one or more groups. Users will be allowed to view the block if 
                    their membership payment to join the groups is outstanding.'),
                'since' => '13.0',
                'filter' => 'groupname',
                'separator' => '|',
                'profile_reference' => 'group',
            ],
            'notpending' => [
                'required' => false,
                'name' => tra('Allowed Groups Full Membership'),
                'description' => tra('Select one or more groups. Users will be allowed to view the block if their 
                    membership in all of the selected groups is not pending.'),
                'since' => '13.0',
                'filter' => 'groupname',
                'separator' => '|',
                'profile_reference' => 'group',
            ],
        ],
    ];
}

function wikiplugin_group($data, $params)
{
    // TODO : Re-implement friend filter
    global $user, $groupPluginReturnAll;
    $tikilib = TikiLib::lib('tiki');
    $dataelse = '';
    if (strrpos($data, '{ELSE}')) {
        $dataelse = substr($data, strrpos($data, '{ELSE}') + 6);
        $data = substr($data, 0, strrpos($data, '{ELSE}'));
    }

    if (isset($groupPluginReturnAll) && $groupPluginReturnAll == true) {
        return $data . $dataelse;
    }

    $groups = $params['groups'];
    $notgroups = $params['notgroups'];
    $allowedUsers = $params['users'];
    $userPending = [];
    if (! is_null($params['pending']) || ! is_null($params['notpending'])) {
        $attributelib = TikiLib::lib('attribute');
        $attributes = $attributelib->get_attributes('user', $user);
        $userlib = TikiLib::lib('user');
        if (! is_null($params['pending'])) {
            $pending = $params['pending'];
            foreach ($pending as $pgrp) {
                $grpinfo = $userlib->get_group_info($pgrp);
                $attname = 'tiki.memberextend.' . ($grpinfo['id'] ?? '');
                if (isset($attributes[$attname])) {
                    $userPending[] = $pgrp;
                }
            }
        }
        if (! is_null($params['notpending'])) {
            $notpending = $params['notpending'];
            foreach ($notpending as $npgrp) {
                $grpinfo = $userlib->get_group_info($npgrp);
                $attname = 'tiki.memberextend.' . ($grpinfo['id'] ?? '');
                if (! isset($attributes[$attname])) {
                    $userNotPending[] = $npgrp;
                }
            }
        }
    }

    if (is_null($groups) && is_null($notgroups) && empty($pending) && empty($notpending) && is_null($allowedUsers)) {
        return '';
    }

    $userGroups = $tikilib->get_user_groups($user);
    $smarty = TikiLib::lib('smarty');
    if (count($userGroups) > 1) { //take away the anonymous as everybody who is registered is anonymous
        foreach ($userGroups as $key => $grp) {
            if ($grp == 'Anonymous') {
                $userGroups[$key] = '';
                break;
            }
        }
    }
    if (! is_null($groups) || ! empty($pending)) {
        $ok = false;
        if (! is_null($groups)) {
            if (! is_array($groups)) {
                $groups = explode('|', $groups);
            }
            foreach ($userGroups as $grp) {
                if (in_array($grp, $groups)) {
                    $ok = true;
                    $smarty->assign('groupValid', 'y');
                    break;
                }
                $smarty->assign('groupValid', 'n');
            }
        }
        if (count($userPending) > 0) {
            $ok = true;
        }
        if (! $ok) {
            return $dataelse;
        }
    }

    if (! is_null($notgroups) || ! empty($notpending)) {
        $ok = true;
        if (! is_null($notgroups)) {
            foreach ($userGroups as $grp) {
                if (in_array($grp, $notgroups)) {
                    $ok = false;
                    $smarty->assign('notgroupValid', 'y');
                    break;
                }
                $smarty->assign('notgroupValid', 'n');
            }
        }
        if (isset($userNotPending) && (count($userNotPending) < count($notpending))) {
            $ok = false;
        }
        if (! $ok) {
            return $dataelse;
        }
    }
    if (! is_null($allowedUsers)) {
        $ok = false;
        if (! empty($user)) {
            if (in_array($user, $allowedUsers)) {
                $ok = true;
                $smarty->assign('userValid', 'y');
            }
        } else {
            $ok = false;
        }
        $smarty->assign('userValid', 'n');
        if (! $ok) {
            return $dataelse;
        }
    }
    return $data;
}
