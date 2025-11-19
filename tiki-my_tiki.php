<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'mytiki';
require_once('tiki-setup.php');
$wikilib = TikiLib::lib('wiki');
include_once('lib/tasks/tasklib.php');
//get_strings tra('My Account Home');
$access->check_user($user);
$userwatch = $user;
if (isset($_REQUEST["view_user"])) {
    if ($_REQUEST["view_user"] <> $user) {
        if ($tiki_p_admin == 'y') {
            $userwatch = $_REQUEST["view_user"];
        } else {
            Feedback::errorAndDie(tra("You do not have permission to view other users data"), \Laminas\Http\Response::STATUS_CODE_403);
        }
    }
}
$smarty->assign('userwatch', $userwatch);
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'pageName_asc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$smarty->assign('sort_mode', $sort_mode);

//Step param for pagination
$step = 5;
$offset = $_REQUEST["offset"] ?? 0;

if ($prefs['feature_wiki'] == 'y') {
    $mytiki_pages = $tikilib->get_user_preference($user, 'mytiki_pages', 'y');
    if ($mytiki_pages == 'y') {
        $user_pages_count = $wikilib->getPagesCount($userwatch);
        $pages_offset = $offset;
        //always reset the offset to the last page when the offset in parameter is greater than the number of pages to have in the pagination
        if ($offset > $user_pages_count) {
            $pages_offset = $user_pages_count === 0 ? -1 : ((ceil($user_pages_count / $step) - 1) * $step);
        }
        $user_pages = $wikilib->get_user_all_pages($userwatch, $sort_mode, $pages_offset, $step);
        $smarty->assign_by_ref('user_pages', $user_pages);
        $smarty->assign_by_ref('user_pages_count', $user_pages_count);
        $smarty->assign_by_ref('pages_offset', $pages_offset);
        $smarty->assign('mytiki_pages', 'y');
    }
}
if ($prefs['feature_blogs'] == 'y') {
    $mytiki_blogs = $tikilib->get_user_preference($user, 'mytiki_blogs', 'y');
    if ($mytiki_blogs == 'y') {
        $bloglib = TikiLib::lib('blog');
        $user_blogs = $bloglib->list_user_blogs($userwatch, false);
        $smarty->assign_by_ref('user_blogs', $user_blogs);
        $smarty->assign('mytiki_blogs', 'y');

        $user_posts_count = count($bloglib->list_posts(0, -1, 'created_desc', '', -1, $userwatch)['data']);
        $posts_offset = $offset;
        //always reset the offset to the last page when the offset in parameter is greater than the number of pages to have in the pagination
        if ($offset > $user_posts_count) {
            $posts_offset = $user_posts_count === 0 ? -1 : ((ceil($user_posts_count / $step) - 1) * $step);
        }
        $user_blog_posts = $bloglib->list_posts($posts_offset, $step, 'created_desc', '', -1, $userwatch);
        $smarty->assign_by_ref('user_posts_count', $user_posts_count);
        $smarty->assign_by_ref('posts_offset', $posts_offset);
        $smarty->assign_by_ref('user_blog_posts', $user_blog_posts['data']);
    }
}
if ($prefs['feature_trackers'] == 'y') {
    $mytiki_user_items = $tikilib->get_user_preference($user, 'mytiki_items', 'y');
    if ($mytiki_user_items == 'y') {
        $trklib = TikiLib::lib('trk');

        $user_items_offset = $offset;
        $user_items_count = count($trklib->get_user_items($userwatch));
        //always reset the offset to the last page when the offset in parameter is greater than the number of pages to have in the pagination
        if ($offset > $user_items_count) {
            $user_items_offset = $user_items_count === 0 ? -1 : ((ceil($user_items_count / $step) - 1) * $step);
        }
        $user_items = $trklib->get_user_items($userwatch, true, $step, $user_items_offset);
        $smarty->assign_by_ref('user_items', $user_items);
        $smarty->assign('mytiki_user_items', 'y');
        $nb_item_comments = $trklib->nbComments($user);
        $smarty->assign_by_ref('user_items_offset', $user_items_offset);
        $smarty->assign_by_ref('user_items_count', $user_items_count);
        $smarty->assign_by_ref('nb_item_comments', $nb_item_comments);
    }
}
if ($prefs['feature_forums'] == 'y') {
    $mytiki_forum_replies = $tikilib->get_user_preference($user, 'mytiki_forum_replies', 'y');
    if ($mytiki_forum_replies == 'y') {
        $commentslib = TikiLib::lib('comments');
        $forum_replies_offset = $offset;
        $forum_replies_count = count($commentslib->get_user_forum_comments($userwatch, -1, 'replies'));
        //always reset the offset to the last page when the offset in parameter is greater than the number of pages to have in the pagination
        if ($offset > $forum_replies_count) {
            $forum_replies_offset = $forum_replies_count === 0 ? -1 : ((ceil($forum_replies_count / $step) - 1) * $step);
        }
        $user_forum_replies = $commentslib->get_user_forum_comments($userwatch, $step, 'replies', $forum_replies_offset);
        $smarty->assign_by_ref('user_forum_replies', $user_forum_replies);
        $smarty->assign_by_ref('forum_replies_count', $forum_replies_count);
        $smarty->assign_by_ref('forum_replies_offset', $forum_replies_offset);
        $smarty->assign('mytiki_forum_replies', 'y');
    }
    $mytiki_forum_topics = $tikilib->get_user_preference($user, 'mytiki_forum_topics', 'y');
    if ($mytiki_forum_topics == 'y') {
        $commentslib = TikiLib::lib('comments');
        $forum_topics_offset = $offset;
        $forum_topics_count = count($commentslib->get_user_forum_comments($userwatch, -1, 'topics'));
        //always reset the offset to the last page when the offset in parameter is greater than the number of pages to have in the pagination
        if ($offset > $forum_topics_count) {
            $forum_topics_offset = $forum_topics_count === 0 ? -1 : ((ceil($forum_topics_count / $step) - 1) * $step);
        }
        $user_forum_topics = $commentslib->get_user_forum_comments($userwatch, $step, 'topics', $forum_topics_offset);
        $smarty->assign_by_ref('user_forum_topics', $user_forum_topics);
        $smarty->assign_by_ref('forum_topics_count', $forum_topics_count);
        $smarty->assign_by_ref('forum_topics_offset', $forum_topics_offset);
        $smarty->assign('mytiki_forum_topics', 'y');
    }
}
if ($prefs['feature_tasks'] == 'y') {
    $mytiki_tasks = $tikilib->get_user_preference($user, 'mytiki_tasks', 'y');
    if ($mytiki_tasks == 'y') {
        $tasks_offset = $offset;
        $tasks_count = $tasklib->list_tasks($user, 0, -1, null, 'priority_asc', true, false, true)['count'];
        //always reset the offset to the last page when the offset in parameter is greater than the number of pages to have in the pagination
        if ($offset > $tasks_count) {
            $tasks_offset = $tasks_count === 0 ? -1 : ((ceil($tasks_count / $step) - 1) * $step);
        }
        $tasks = $tasklib->list_tasks($user, $tasks_offset, $step, null, 'priority_asc', true, false, true);
        $smarty->assign_by_ref('tasks', $tasks['data']);
        $smarty->assign_by_ref('tasks_offset', $tasks_offset);
        $smarty->assign_by_ref('tasks_count', $tasks_count);
        $smarty->assign('mytiki_tasks', 'y');
    }
}
if ($prefs['feature_messages'] == 'y' && $tiki_p_messages == 'y') {
    $mytiki_msgs = $tikilib->get_user_preference($user, 'mytiki_msgs', 'y');
    if ($mytiki_msgs == 'y') {
        $unread = $tikilib->user_unread_messages($userwatch);
        $smarty->assign_by_ref('unread', $unread);

        $msgs_offset = $offset;
        $msgs_count = TikiLib::lib('message')->list_user_messages($user, 0, -1, 'date_desc', '', 'isRead', 'n', '', 'messages')['count'];
        //always reset the offset to the last page when the offset in parameter is greater than the number of pages to have in the pagination
        if ($offset > $msgs_count) {
            $msgs_offset = $msgs_count === 0 ? -1 : ((ceil($msgs_count / $step) - 1) * $step);
        }

        $msgs = TikiLib::lib('message')->list_user_messages($user, $msgs_offset, $step, 'date_desc', '', 'isRead', 'n', '', 'messages');
        $smarty->assign_by_ref('msgs', $msgs['data']);
        $smarty->assign_by_ref('msgs_offset', $msgs_offset);
        $smarty->assign_by_ref('msgs_count', $msgs_count);
        $smarty->assign('mytiki_msgs', 'y');
    }
}
if ($prefs['feature_articles'] == 'y') {
    $mytiki_articles = $tikilib->get_user_preference($user, 'mytiki_articles', 'y');
    if ($mytiki_articles == 'y') {
        $artlib = TikiLib::lib('art');
        $user_articles_count = $artlib->getArticlesCount($userwatch);
        $articles_offset = $offset;
        //always reset the offset to the last page when the offset in parameter is greater than the number of pages to have in the pagination
        if ($offset > $user_articles_count) {
            $articles_offset = $user_articles_count === 0 ? -1 : ((ceil($user_articles_count / $step) - 1) * $step);
        }
        $user_articles = $artlib->get_user_articles($userwatch, $step, $articles_offset);
        $smarty->assign_by_ref('user_articles', $user_articles);
        $smarty->assign_by_ref('user_articles_count', $user_articles_count);
        $smarty->assign_by_ref('articles_offset', $articles_offset);
        $smarty->assign('mytiki_articles', 'y');
    }
}

$smarty->assign('step', $step);
include_once('tiki-section_options.php');
$smarty->assign('mid', 'tiki-my_tiki.tpl');
$smarty->display("tiki.tpl");
