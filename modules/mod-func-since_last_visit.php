<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_since_last_visit_info()
{
    return [
        'name' => tra('Since Last Visit (Simple)'),
        'description' => tra('Displays to logged-in users the number of new or updated objects since their last login date and time.')
    ];
}

/**
 * @param $mod_reference
 * @param null $params
 */
function module_since_last_visit($mod_reference, $params = null)
{
    global $user;
    $smarty = TikiLib::lib('smarty');

    // Call the enhanced module with simple view parameter
    $module_params = [
        'simple_view' => 'y',
        'showuser' => 'y',
        'showtracker' => 'y',
    ];

    // Include and call the enhanced module
    require_once('modules/mod-func-since_last_visit_new.php');
    module_since_last_visit_new($mod_reference, $module_params);

    // Get the data from the enhanced module
    $slvn_info = $smarty->getTemplateVars('slvn_info');

    // Calculate totals for simple view
    $nvi_info = [
        'lastVisit' => $slvn_info['lastLogin'],
        'pages'    => $slvn_info['items']['pages']['count'] ?? 0,
        'files'    => $slvn_info['items']['files']['count'] ?? 0,
        'comments' => $slvn_info['items']['comments']['count'] ?? 0,
        'users'    => $slvn_info['items']['users']['count'] ?? 0,
        'trackers' => $slvn_info['items']['trackers']['count'] ?? 0,
        'calendar' => $slvn_info['items']['calendar']['count'] ?? 0,
    ];

    $smarty->assign('nvi_info', $nvi_info);
    $smarty->assign('tpl_module_title', tra('Since your last visit'));
}
