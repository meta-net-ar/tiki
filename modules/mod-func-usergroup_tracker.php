<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_usergroup_tracker_info()
{
    return [
        'name' => tra('User-Group Tracker'),
        'description' => tra('User and Group tracker links.'),
        'prefs' => ['feature_trackers'],
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_usergroup_tracker($mod_reference, $module_params)
{
    global $user, $smarty, $userlib;

    if (! $user) {
        return;
    }

    // Filter out system groups that cannot have tracker representations
    $user_groups = array_filter(
        $userlib->get_user_groups($user),
        function ($group) {
            return ! in_array($group, ['Anonymous']);
        }
    );

    // Re-index array after filtering for clean template iteration
    $smarty->assign('user_groups', array_values($user_groups));
}
