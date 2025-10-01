<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'freetags';
$inputConfiguration = [
    [
        'staticKeyFilters'                => [
            'del'                         => 'int',      //get
            'itemit'                      => 'int',      //post
            'typeit'                      => 'word',     //post
            'tag'                         => 'word',     //post
            'sort_mode'                   => 'int',      //post
            'find'                        => 'string',   //post
            'type'                        => 'word',     //post
            'old_type'                    => 'word',     //post
            'user_only'                   => 'bool',     //post
            'broaden'                     => 'bool',     //post
            'maxPopular'                  => 'int',      //get
            'tsort_mode'                  => 'word',     //get
            'objectId'                    => 'int',      //get
        ],
    ],
];
require_once('tiki-setup.php');
$freetaglib = TikiLib::lib('freetag');
$access->check_feature('feature_freetags');
$access->check_permission('tiki_p_view_freetags');

if (isset($_REQUEST['del'])) {
    if ($tiki_p_admin == 'y' || $tiki_p_unassign_freetags == 'y') {
        $freetaglib->delete_object_tag($_REQUEST['itemit'], $_REQUEST['typeit'], $_REQUEST['tag']);
    } else {
        Feedback::errorAndDie(tra('Permission denied'), \Laminas\Http\Response::STATUS_CODE_403);
    }
}
$smarty->assign('metatag_robots', 'NOINDEX, FOLLOW');

if ($freetaglib->count_tags() == 0) {
    Feedback::errorAndDie(tra("Nothing tagged yet") . '.', \Laminas\Http\Response::STATUS_CODE_400);
}
if (! isset($_REQUEST['tag']) && $prefs['freetags_preload_random_search'] == 'y') {
    $tag = $freetaglib->get_tag_suggestion('', 1);
    if (! empty($tag[0])) {
        $_REQUEST['tag'] = $tag[0];
        if (str_contains($tag[0], ' ')) {
            $_REQUEST['tag'] = '"' . $_REQUEST['tag'] . '"';
        }
    }
}
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = $prefs['freetags_sort_mode'];
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$query_sort_mode = str_replace('created', 'o.`created`', $sort_mode);
if (isset($_REQUEST["find"])) {
    $find = $_REQUEST["find"];
} else {
    $find = '';
}
$smarty->assign_by_ref('sort_mode', $sort_mode);
$smarty->assign_by_ref('find', $find);
if (! isset($_REQUEST["offset"])) {
    $offset = 0;
} else {
    $offset = $_REQUEST["offset"];
}
$smarty->assign_by_ref('offset', $offset);
if (! isset($_REQUEST["type"])) {
    $type = $_REQUEST['old_type'] ?? '';
} else {
    $type = $_REQUEST["type"];
}
$smarty->assign('type', $type);
if (isset($_REQUEST["user_only"]) && $_REQUEST["user_only"] == 'on') {
    $view_user = $user;
    $smarty->assign('user_only', 'on');
} else {
    $view_user = '';
    $smarty->assign('user_only', 'off');
}
if (isset($_REQUEST['broaden']) && $_REQUEST['broaden'] == 'y') {
    $broaden = 'y';
} else {
    $broaden = 'n';
}
$smarty->assign('broaden', $broaden);
$tagArray = $freetaglib->parse_tag((isset($_REQUEST['tag'])) ? $_REQUEST['tag'] : '');
$tagString = '';
foreach ($tagArray as $t_ar) {
    if (str_contains($t_ar, ' ')) {
        $tagString .= '"' . $t_ar . '" ';
    } else {
        $tagString .= $t_ar . ' ';
    }
}
$smarty->assign('tagString', trim($tagString));
$smarty->assign('tag', (isset($tagArray[0])) ? $tagArray[0] : '');
if (empty($_REQUEST['maxPopular'])) {
    $maxPopular = $prefs['freetags_browse_amount_tags_in_cloud'];
} else {
    $maxPopular = $_REQUEST['maxPopular'];
    $smarty->assign_by_ref('maxPopular', $maxPopular);
}
if (empty($_REQUEST['tsort_mode'])) {
    $tsort_mode = 'tag_asc';
} else {
    $tsort_mode = $_REQUEST['tsort_mode'];
    $smarty->assign_by_ref('tsort_mode', $tsort_mode);
}
if (! empty($_REQUEST['objectId'])) {
    $objectId = $_REQUEST['objectId'];
} else {
    $objectId = null;
}
$smarty->assign_by_ref('objectId', $objectId);
if ($prefs['feature_blogs'] == 'y' && $type == 'blog post') {
    $blogs = TikiLib::lib('blog')->list_blogs();
    $smarty->assign('blogs', $blogs['data']);
}

$most_popular_tags = $freetaglib->get_most_popular_tags('', 0, $maxPopular, $type, $objectId, $tsort_mode);
if (! empty($prefs['freetags_cloud_colors'])) {
    $colors = explode(',', $prefs['freetags_cloud_colors']);
    $prev = '';
    foreach ($most_popular_tags as $id => $tag) {
        if (count($colors) == 1) {
            $i = 0;
        } elseif (count($colors) == 2) {
            $i = $prev ? 0 : 1;
        }
        $most_popular_tags[$id]['color'] = $colors[$i];
        $prev = $i;
    }
}
$smarty->assign('most_popular_tags', $most_popular_tags);
if ($broaden == 'y') {
    $broaden = 'n';
    if (! empty($tagArray)) {
        $tagArray = [
            $tagArray[count($tagArray) - 1]
        ];
    } else {
        $tagArray = [];
    }
}
$objects = $freetaglib->get_objects_with_tag_combo($tagArray, $type, $view_user, $offset, $maxRecords, $query_sort_mode, $find, $broaden, $objectId);

$smarty->assign_by_ref('objects', $objects["data"]);
$smarty->assign('objects_count', count($objects['data']));
$count = $objects['count'];
$smarty->assign('count', $objects['count']);

include_once('tiki-section_options.php');

$smarty->assign(
    'objects_with_freetags',
    [
        'wiki page',
        'blog post',
        'article',
        'directory',
        'faq',
        'file gallery',
        'image gallery',
        'image',
        'poll',
        'quiz',
        'survey',
        'tracker',
        'tracker %d'
     ]
);
$smarty->assign('mid', 'tiki-browse_freetags.tpl');
$smarty->display("tiki.tpl");
