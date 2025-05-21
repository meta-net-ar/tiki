<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('tiki-setup.php');

$access->check_feature(['feature_trackers', 'feature_ajax', 'wikiplugin_tracker']);

include_once('lib/wiki-plugins/wikiplugin_tracker.php');

$headerlib->clear_js();                             // so store existing js for later and clear

$json_data = [];
$re = $userlib->get_group_info(isset($_REQUEST['chosenGroup']) ? $_REQUEST['chosenGroup'] : 'Registered');
if (! empty($re['usersTrackerId']) && ! empty($re['registrationUsersFieldIds'])) {
    $json_data['res'] = wikiplugin_tracker(
        '',
        [
            'trackerId' => $re['usersTrackerId'],
            'fields' => explode(':', $re['registrationUsersFieldIds']),
            'showdesc' => 'y',
            'showmandatory' => 'y',
            'embedded' => 'y',
            'action' => tra('Register'),
            'registration' => 'n',
            'formtag' => 'n',
            '_ajax_form_ins_id' => 'group',
        ]
    );

    $json_data['res'] .= $headerlib->output_js();
} else {
    $json_data['res'] = '<div class="tiki-form-group row"><label class="col-sm-4 col-form-label">' . tr('Group') . '</label>' .
        '<div class="col-sm-8"><div class="form-control"><span class="text-muted">' . $_REQUEST['chosenGroup'] . '</span></div></div></div>';
    $json_data['debug'] = $re;
}


/**
 * @param $matches
 * @return string
 */
function group_tracker_ajax_quote($matches)
{
    return '"' . $matches[1] . '":';
}

header('Content-Type: application/json');

$access->output_serialized($json_data);
