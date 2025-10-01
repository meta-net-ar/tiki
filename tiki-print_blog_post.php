<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'blogs';
$inputConfiguration = [
    [
        'staticKeyFilters'         => [
        'postId'                   => 'int',             //get
        ],
    ],
];
require_once('tiki-setup.php');
$bloglib = TikiLib::lib('blog');
$access->check_feature('feature_blogs');

if (! isset($_REQUEST["postId"])) {
    Feedback::errorAndDie(tra("No post indicated"), \Laminas\Http\Response::STATUS_CODE_400);
}

$postId = $_REQUEST["postId"];
$post_info = $bloglib->get_post($postId);

$blogId = $post_info["blogId"];
$tikilib->get_perm_object($postId, 'blog post');
$access->check_permission('tiki_p_read_blog', '', 'blog post', $postId);


$blog_data = $bloglib->get_blog($blogId);

if (! $blog_data) {
    Feedback::errorAndDie(tra("Blog not found"), \Laminas\Http\Response::STATUS_CODE_404);
}

$parsed_data = TikiLib::lib('parser')->parse_data($post_info["data"], ['is_html' => true]);
$parsed_data = preg_replace('/\.\.\.page\.\.\./', '<hr />', $parsed_data);

$smarty->assign('blog_data', $blog_data);
$smarty->assign('blogId', $blogId);
$post_info['parsed_data'] = $parsed_data;
$smarty->assign('post_info', $post_info);
$smarty->assign('postId', $postId);

// note: tiki-print_blog_post.tpl doesn't use the $mid system for page layout,
//       this is assigned here to trick canonical.tpl into generating a link tag
$smarty->assign('mid', 'tiki-view_blog_post.tpl');

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('print_page', 'y');
$smarty->display("tiki-print_blog_post.tpl");
