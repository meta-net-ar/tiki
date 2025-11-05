<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'trackers';
$inputConfiguration = [
    [
        'staticKeyFilters'         => [
        'trackerId'                => 'int',                //get
        'sort_mode'                => 'alnumdash',          //post
        'offset'                   => 'int',                //get
        'find'                     => 'string',             //post
        ],
    ],
];
require_once('tiki-setup.php');
$trklib = TikiLib::lib('trk');
$categlib = TikiLib::lib('categ');
$access->check_feature('feature_trackers');
$auto_query_args = ['sort_mode', 'offset', 'find'];

// Only used to call an edit dialog directly from other pages
$auto_query_args = ['trackerId'];
if (! isset($_REQUEST["trackerId"])) {
    $_REQUEST["trackerId"] = 0;
}
if (! empty($_REQUEST['trackerId'])) {
    $smarty->assign('trackerInfo', $trklib->get_tracker($_REQUEST['trackerId']));
}
$smarty->assign('trackerId', $_REQUEST["trackerId"]);

if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = $prefs['tracker_list_order'] ?? 'created_desc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$smarty->assign_by_ref('sort_mode', $sort_mode);
if (! isset($_REQUEST["offset"])) {
    $offset = 0;
} else {
    $offset = $_REQUEST["offset"];
}
$smarty->assign_by_ref('offset', $offset);
if (isset($_REQUEST["find"])) {
    $find = $_REQUEST["find"];
} else {
    $find = '';
}

if ($prefs['tracker_display_categories'] == 'y') {
    // Get all categories
    $categories = $categlib->getCategories(['type' => 'all']);
    $smarty->assign('tracker_categories', $categories);
}

$trackers = $trklib->list_trackers($offset, $maxRecords, $sort_mode, $find, true);
$trackers_data = $trackers["data"];
$isTrackerFiltered = 'n';
$arrayOfTrackerIdsPassedInUrl = [];
if (isset($_REQUEST['trackerIds'])) {
    $isTrackerFiltered = 'y';
    $allowedId = $_REQUEST['trackerIds'];
    $filtered_trackers = array_filter($trackers["data"], function ($tracker) use ($allowedId) {
        return in_array($tracker['trackerId'], $allowedId);
    });

    $arrayOfTrackerIdsPassedInUrl = $allowedId;
    $allData = $trackers["data"];
    $trackers_data = $filtered_trackers;
    //This is set to space because : Make the clear button visible to show users that it is the filter.
    $find = ' ';
}

foreach ($trackers_data as &$tracker) {
    if ($userlib->object_has_one_permission($tracker["trackerId"], 'tracker')) {
        $tracker["individual"] = 'y';
    } else {
        $tracker["individual"] = 'n';
    }

    $tracker['watched'] = $user && $tikilib->user_watches($user, 'tracker_modified', $tracker["trackerId"], 'tracker');

    // Get tracker options and set wiki_only status
    $tracker_info = $trklib->get_tracker_options($tracker['trackerId']);
    $tracker['wiki_only'] = $tracker_info['adminOnlyViewEditItem'] ?? 'n';

    // Could be used with object_perms_summary.tpl instead of the above but may be less performant
    //  $objectperms = Perms::get('tracker', trackerId);
    //  $smarty->assign('permsType', $objectperms->from());

    // Hide tracker from non-admins if adminOnlyViewEditItem is enabled
    if ($tiki_p_admin_trackers !== 'y' && $tracker['wiki_only'] === 'y') {
        $tracker = null;
        continue;
    }

    // Get categories for valid trackers
    $tracker['categorie_ids'] = $categlib->get_object_categories('tracker', $tracker['trackerId']);
    if (! empty($tracker['categorie_ids'])) {
        $tracker['category_details'] = [];
        foreach ($tracker['categorie_ids'] as $categoryId) {
            $category_info = $categlib->get_category($categoryId);
            if ($category_info) {
                $tracker['category_details'][] = [
                    "id" => $category_info['categId'],
                    "name" => $category_info["name"]
                ];
            }
        }
    }
}

// Filter out null trackers (those hidden from non-admins)
$trackers_data = array_filter($trackers_data);

$smarty->assign('find', $find);
$smarty->assign('objectId', 'trackerId');
$smarty->assign('find_objectId', $arrayOfTrackerIdsPassedInUrl);
$smarty->assign('find_show_objects_multi', $isTrackerFiltered);
$smarty->assign_by_ref('count', $trackers['count']);
$smarty->assign_by_ref('trackers', $trackers_data);
$smarty->assign_by_ref('findAllObjects', $allData);
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

include_once('tiki-section_options.php');
$smarty->assign('title', tr('Trackers'));

// Display the template
$smarty->display("tiki-list_trackers.tpl");
