<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Lib\Socnets\SocnetsDataLib;

/**
 * @return array
 */
function module_facebook_info()
{
    return [
        'name' => tra('Facebook'),
        'description' => tra('Shows the Wall of a user or Facebook Page'),
        'params' => [
            'user' => [
                'name' => 'user',
                'description' => tra('Tiki user to show Facebook wall of.'),
                'required' => true
            ],
            'showuser' => [
                'name' => 'showuser',
                'description' => tra('Show username in timeline. y|n'),
                'default' => 'n',
            ],
            'pageid' => [
                'name' => 'pageid',
                'description' => tra('Facebook Page ID (optional, to display a Facebook Page instead of user feed)'),
                'default' => '',
            ],
        ],
        'common_params' => ['nonums', 'rows'],
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_facebook($mod_reference, $module_params)
{
    global $prefs;

    $timeline = [];
    $page = isset($_GET['fb_page']) ? max(1, (int)$_GET['fb_page']) : 1;
    $perPage = ! empty($module_params['max']) ? (int)$module_params['max'] : 10;

    if (! empty($module_params['user'])) {
        $user = $module_params['user'];
        $pageId = $module_params['pageid'] ?? '';

        // Try new HybridAuth system first
        $socnetsDataLib = new SocnetsDataLib();
        try {
            $timeline = $socnetsDataLib->getFacebookWall($user, 'Facebook', $page, $perPage, $pageId);
        } catch (\TikiLib\Core\Services\Exception\SocnetsTokenNotFoundException $e) {
            $timeline = module_facebook_fetch_legacy_timeline($user);

            if (empty($timeline)) {
                $timeline[0]['message'] = tra('User not registered with Facebook') . ": $user";
                $timeline[0]['created_time'] = '';
                $timeline[0]['fromName'] = '';
            }
        } catch (\TikiLib\Core\Services\Exception\SocnetsProviderNotConfiguredException | \TikiLib\Core\Services\Exception\SocnetsApiException $e) {
            $timeline = module_facebook_fetch_legacy_timeline($user);

            if (empty($timeline)) {
                $timeline[0]['message'] = tra('Unable to retrieve Facebook feed. Please reconnect your Facebook account.');
                $timeline[0]['created_time'] = '';
                $timeline[0]['fromName'] = '';
            }
        }
    } else {
        $timeline[0]['message'] = tra('No username given');
        $timeline[0]['created_time'] = '';
        $timeline[0]['fromName'] = '';
    }

    $smarty = TikiLib::lib('smarty');
    $smarty->assign('timeline', $timeline);
    $smarty->assign('fb_page', $page);
    $smarty->assign('fb_perpage', $perPage);
    $smarty->assign('fb_has_posts', count($timeline) >= $perPage);
}

/**
 * Attempt to fetch the Facebook timeline using the legacy socialnetworkslib.
 *
 * @param string $user
 * @return array
 */
function module_facebook_fetch_legacy_timeline($user): array
{
    global $prefs;

    if (empty($prefs['socialnetworks_facebook_application_id'])) {
        return [];
    }

    global $socialnetworkslib;
    require_once('lib/socialnetworkslib.php');

    $legacyTimeline = $socialnetworkslib->facebookGetWall($user, true);

    return is_array($legacyTimeline) ? $legacyTimeline : [];
}
