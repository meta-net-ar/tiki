<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'         => [
        'remove'                   => 'int',              //get
        'remove_x'                 => 'int',              //get
        'sort_mode'                => 'word',             //get
        'offset'                   => 'int',              //get
        'find'                     => 'string',           //post
        'blogId'                   => 'int',              //get
        ],
        'staticKeyFiltersForArrays' => [
            'checked'               => 'int',             //post
        ],
    ],
];
require_once('tiki-setup.php');
use Tiki\SortModeValidator;
$bloglib = TikiLib::lib('blog');
$access->check_feature('feature_blogs');
$access->check_permission('tiki_p_blog_admin');

if (isset($_REQUEST["remove"]) && $access->checkCsrf()) {
    $bloglib->remove_post($_REQUEST["remove"]);
}
if (isset($_REQUEST['checked']) && $access->checkCsrf()) {
    $checked = is_array($_REQUEST['checked']) ? $_REQUEST['checked'] : [$_REQUEST['checked']];
    // Delete post(s)
    if ((isset($_REQUEST['remove']) || isset($_REQUEST['remove_x'])) && $access->checkCsrf()) {
        foreach ($checked as $id) {
            $bloglib->remove_post($id);
        }
    }
}

$sort_mode = SortModeValidator::validateSortModeOrFeedback(
    $_REQUEST['sort_mode'] ?? 'created_desc',
    ['tiki_blog_posts']
);
$smarty->assign_by_ref('sort_mode', $sort_mode);

$offset = $_REQUEST["offset"] ?? 0;
$smarty->assign_by_ref('offset', $offset);
$find = $_REQUEST["find"] ?? '';
$smarty->assign('find', $find);
if (isset($_REQUEST['blogId'])) {
    $blogId = $_REQUEST['blogId'];
    $blog = $bloglib->get_blog($blogId);
    $smarty->assign('blogTitle', $blog['title']);
    $smarty->assign('blogId', $blogId);
} else {
    $blogId = -1;
}

$posts = $bloglib->list_posts($offset, $maxRecords, $sort_mode, $find, $blogId);
$smarty->assign_by_ref('count', $posts["count"]);
$smarty->assign_by_ref('posts', $posts["data"]);

// Display the template
$smarty->assign('mid', 'tiki-list_posts.tpl');
$smarty->display("tiki.tpl");
