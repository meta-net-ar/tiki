<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'forums';
$inputConfiguration = [
    [
        'staticKeyFilters'      => [
            'comments_parentId'       => 'int',     //post
            'threadId'                => 'int',     //get
            'topics_offset'           => 'int',     //get
            'topics_sort_mode'        => 'word',    //get
            'thread_sort_mode'        => 'word',    //get
            'topics_find'             => 'word',    //get
            'topics_threshold'        => 'int',     //get
            'quote'                   => 'int',     //get
            'time_control'            => 'int',     //get
            'comments_offset'         => 'int',     //get
            'openpost'                => 'bool',    //get
            'comments_grandParentId'  => 'int',     //get
            'comments_reply_threadId' => 'int',     //get
            'lock'                    => 'bool',    //get
            'display'                 => 'word',    //get
            'archive'                 => 'url',     //post
            'report'                  => 'int',     //get
            'post_reported'           => 'word',    //get
            'comments_per_page'       => 'int',     //get
            'savenotepad'             => 'bool',    //get
            'watch_event'             => 'word',    //get
            'watch_object'            => 'word',    //get
            'view_atts'               => 'bool',    //get
            'fa_offset'               => 'int',     //get
            'fa_maxRecords'           => 'int',     //get
        ]
    ],
];
require_once('tiki-setup.php');

$access->check_feature('feature_forums');

$commentslib = TikiLib::lib('comments');
if (! isset($_REQUEST['comments_parentId']) && isset($_REQUEST['threadId'])) {
    $_REQUEST['comments_parentId'] = $_REQUEST['threadId'];
}
if (! isset($_REQUEST['comments_parentId'])) {
    Feedback::errorAndDie(tra("No thread indicated"), \Laminas\Http\Response::STATUS_CODE_400);
}

$thread_info = $commentslib->get_comment($_REQUEST['comments_parentId']);
if (empty($thread_info['object']) || $thread_info['objectType'] != 'forum') {
    Feedback::errorAndDie(tra('Incorrect thread'), \Laminas\Http\Response::STATUS_CODE_400);
}

$forumId = $thread_info['object'];
$forum_info = $commentslib->get_forum($forumId);
if (empty($forum_info)) {
    Feedback::errorAndDie(tra('Incorrect thread'), \Laminas\Http\Response::STATUS_CODE_400);
}

$pageCache = Tiki_PageCache::create()
    ->disableForRegistered()
    ->onlyForGet()
    ->requiresPreference('memcache_forum_output')
    ->addArray($_GET)
    ->addValue('role', 'forum-page-output')
    ->addKeys($_REQUEST, [ 'locale', 'forumId', 'comments_parentId' ])
    ->checkMeta(
        'forum-page-output-meta-time',
        [
            'forumId'           => $jitRequest->forumId->int(),
            'comments_parentId' => $jitRequest->comments_parentId->int(),
        ]
    )
    ->dieAndOutputOrStore();

if ($prefs['feature_categories'] == 'y') {
    $categlib = TikiLib::lib('categ');
}
if (! isset($_REQUEST['topics_offset'])) {
    $_REQUEST['topics_offset'] = 0;
}
if (! isset($_REQUEST['topics_sort_mode']) || empty($_REQUEST['topics_sort_mode'])) {
    $_REQUEST['topics_sort_mode'] = $forum_info['topicOrdering'];
} else {
    $smarty->assign('topics_sort_mode_param', '&amp;topics_sort_mode=' . $_REQUEST['topics_sort_mode']);
}
if (! empty($_REQUEST['thread_sort_mode'])) {
    $thread_sort_mode = $_REQUEST['thread_sort_mode'];
} elseif (! empty($forum_info['threadOrdering'])) {
    $thread_sort_mode = $forum_info['threadOrdering'];
} else {
    $thread_sort_mode = 'commentDate_asc';
}
$smarty->assign_by_ref('thread_sort_mode', $thread_sort_mode);

if (! isset($_REQUEST['topics_find'])) {
    $_REQUEST['topics_find'] = '';
}
if (! isset($_REQUEST['topics_threshold']) || empty($_REQUEST['topics_threshold'])) {
    $_REQUEST['topics_threshold'] = 0;
}
if (isset($_REQUEST["quote"]) && $_REQUEST["quote"]) {
    $quote = $_REQUEST["quote"];
} else {
    $quote = 0;
}
$smarty->assign('quote', $quote);

//Set time control to 0 if not set
if (! isset($_REQUEST['time_control'])) {
    $_REQUEST['time_control'] = 0;
}
$commentslib->set_time_control($_REQUEST['time_control']);
/* If the forum is flat (no sub-threads), check to see if the requested post is
the original post for the thread (ie. if it's the root of the thread). If not,
change the request to fetch its parent's thread and then find the location of the
originally requested post*/
if ($forum_info['is_flat'] == 'y') {
    if (empty($thread_info)) {
        //need to get thread info to find out if it's the root of the thread
        $thread_info = $commentslib->get_comment($_REQUEST["threadId"]);
    }
    // if it's not the root, ie. not 0, then start the fetch the thread via the root post
    if ($thread_info['parentId'] > 0) {
        $anchored_post = $_REQUEST['threadId'];
        $root_thread_id = $thread_info['parentId'];
        //gets the position/page offset of the requested post within the parent
        $resPos = $commentslib->get_comment_position($anchored_post, $root_thread_id, $thread_sort_mode, $forum_info['commentsPerPage']);
        //find the needed comments_offset to set to the right page
        $_REQUEST['comments_offset'] = $resPos['page_offset'] * $forum_info['commentsPerPage'];
        //note the #thread anchor added at the end of the URL to fetch the specific post
        $url = "tiki-view_forum_thread.php?comments_parentId=" . $root_thread_id . "&comments_offset=" . $_REQUEST['comments_offset'] . "#threadId=" . $anchored_post;
        header('location: ' . $url);
        die;
    }
}


$comments_parentId = $_REQUEST["comments_parentId"];
if (isset($_REQUEST["openpost"])) {
    $smarty->assign('openpost', 'y');
} else {
    $smarty->assign('openpost', 'n');
}
$smarty->assign('comments_parentId', $comments_parentId);
if (isset($_REQUEST["comments_grandParentId"])) {
    $smarty->assign('comments_grandParentId', $_REQUEST["comments_grandParentId"]);
}
if (isset($_REQUEST["comments_reply_threadId"])) {
    $smarty->assign('comments_reply_threadId', $_REQUEST["comments_reply_threadId"]);
} else {
    $_REQUEST["comments_reply_threadId"] = $comments_parentId;
    $smarty->assign('comments_reply_threadId', $_REQUEST["comments_reply_threadId"]);
}
$smarty->assign('forumId', $forumId);
if (isset($_REQUEST['lock'])) {
    $access->checkCsrf();

    if ($_REQUEST['lock'] == 'y') {
        $commentslib->lock_comment($comments_parentId);
    } elseif ($_REQUEST['lock'] == 'n') {
        $commentslib->unlock_comment($comments_parentId);
    }
}
$commentslib->comment_add_hit($comments_parentId);
$commentslib->mark_comment($user, $forumId, $comments_parentId);

$tikilib->get_perm_object($comments_parentId, 'thread', '', true, $forumId);

if ($user) {
    if ($forum_info["moderator"] == $user) {
        $tiki_p_admin_forum = 'y';
        $smarty->assign('tiki_p_admin_forum', 'y');
    } elseif (in_array($forum_info['moderator_group'], $userlib->get_user_groups($user))) {
        $tiki_p_admin_forum = 'y';
        $smarty->assign('tiki_p_admin_forum', 'y');
    }
}
if ($tiki_p_admin_forum == 'y') {
    $tiki_p_forum_post = 'y';
    $smarty->assign('tiki_p_forum_post', 'y');
    $tiki_p_forum_read = 'y';
    $smarty->assign('tiki_p_forum_read', 'y');
    $tiki_p_forum_vote = 'y';
    $smarty->assign('tiki_p_forum_vote', 'y');
    $tiki_p_forum_post_topic = 'y';
    $smarty->assign('tiki_p_forum_post_topic', 'y');
}

$access->check_permission(['tiki_p_forum_read'], '', 'thread', $comments_parentId);

if (isset($_REQUEST['display'])) {
    if ($_REQUEST['display'] == 'pdf') {
        $access->check_permission(['tiki_p_export_pdf'], '', 'thread', $comments_parentId);
    } else {
        $access->check_permission(['tiki_p_print'], '', 'thread', $comments_parentId);
    }
}

$smarty->assign('topics_next_offset', $_REQUEST['topics_offset'] + 1);
$smarty->assign('topics_prev_offset', $_REQUEST['topics_offset'] - 1);

$threads = $commentslib->get_forum_topics($forumId, max(0, $_REQUEST['topics_offset'] - 1), 3, $_REQUEST["topics_sort_mode"]);
if (count($threads) > 0) {
    if ($threads[0]['threadId'] == $comments_parentId) {
        if (isset($threads[1])) {
            $smarty->assign('next_topic', $threads[1]['threadId']);
        }
    } elseif ($threads[1]['threadId'] == $comments_parentId) {
        $smarty->assign('prev_topic', $threads[0]['threadId']);
        if (isset($threads[2])) {
            $smarty->assign('next_topic', $threads[2]['threadId']);
        }
    }
}
if ($tiki_p_admin_forum == 'y') {
    if ($prefs['feature_forum_topics_archiving'] == 'y' && isset($_REQUEST['archive']) && isset($comments_parentId)) {
        $access->checkCsrf();
        if ($_REQUEST['archive'] == 'y') {
            $commentslib->archive_thread($comments_parentId);
        } elseif ($_REQUEST['archive'] == 'n') {
            $commentslib->unarchive_thread($comments_parentId);
        }
    }
}
if ($tiki_p_forums_report == 'y' && isset($_REQUEST['report'])) {
    $access->checkCsrf();
    $commentslib->report_post($forumId, $comments_parentId, $_REQUEST['report'], $user, '');

    $pageCache->invalidate();

    $url = "tiki-view_forum_thread.php?comments_parentId=" . $comments_parentId . "&post_reported=y";
    header('location: ' . $url);
    die;
}
//shows a "thanks for reporting" message
if (isset($_REQUEST['post_reported'])) {
    $smarty->assign('post_reported', $_REQUEST['post_reported']);
} else {
    $smarty->assign('post_reported', '');
}
$smarty->assign_by_ref('forum_info', $forum_info);
$thread_info = $commentslib->get_comment($comments_parentId, null, $forum_info);

if ($user != $thread_info['userName']) {
    $score_id = $thread_info["threadId"];

    TikiLib::events()->trigger(
        'tiki.forumpost.view',
        [
            'type' => 'forum post',
            'object' => $score_id,
            'author' => $thread_info['userName'],
            'user' => $GLOBALS['user'],
        ]
    );
}

if (empty($thread_info)) { // this should be moved up as $thread_info could be null from 257 line
    $forumId = '';
    //thread might be missing due to a successful delete of a post
    if (! empty($_SESSION['tikifeedback'][0]['deleted_forumId'])) {
        $forumId = $_SESSION['tikifeedback'][0]['deleted_forumId'];
    }
    if (empty($forumId)) {
        Feedback::errorAndDie(tr('Thread %0 does not exist.', $comments_parentId), \Laminas\Http\Response::STATUS_CODE_409);
    } else {
        TikiLib::lib('access')->redirect('tiki-view_forum.php?forumId=' . $forumId);
    }
}

if (! empty($thread_info['parentId'])) {
    $thread_info['topic'] = $commentslib->get_comment($thread_info['parentId'], null, $forum_info);
}
if ($tiki_p_admin_forum != 'y' && $thread_info['locked'] == 'y') {
    $tiki_p_forum_post = 'n';
    $smarty->assign('tiki_p_forum_post', 'n');
}

$smarty->assign_by_ref('thread_info', $thread_info);
$comments_per_page = $forum_info['commentsPerPage'];
$thread_style = $forum_info['threadStyle'];
$comments_vars = [
    'forumId'
];

$comments_prefix_var = 'forum:';
$comments_objectId = $comments_prefix_var . $forumId;
if ($prefs['feature_forum_process_inbound_mail_in_cron'] !== 'y' && ! empty($forum_info["inbound_pop_server"])) {
    $commentslib->process_inbound_mail($forumId);
}

if (isset($_REQUEST['display']) && $_REQUEST['display'] == 'print_all') {
    $_REQUEST['comments_per_page'] = 0; // unlimited
}
$forum_mode = 'y';
include_once("comments.php");

$cat_type = 'forum';
$cat_objid = $forumId;
include_once('tiki-section_options.php');

if ($user && $prefs['feature_notepad'] == 'y' && isset($_REQUEST['savenotepad']) && $tiki_p_notepad == 'y') {
    $access->checkCsrf();
    $info = $commentslib->get_comment($_REQUEST['savenotepad'], null, $forum_info);
    $tikilib->replace_note($user, 0, $info['title'], $info['data']);
}
if ($prefs['feature_user_watches'] == 'y') {
    if ($user && isset($_REQUEST['watch_event'])) {
        $access->checkCsrf();
        if ($_REQUEST['watch_action'] == 'add') {
            $tikilib->add_user_watch($user, $_REQUEST['watch_event'], $_REQUEST['watch_object'], 'forum topic', $forum_info['name'] . ':' . $thread_info['title'], "tiki-view_forum_thread.php?comments_parentId=" . $comments_parentId);
        } else {
            $tikilib->remove_user_watch($user, $_REQUEST['watch_event'], $_REQUEST['watch_object'], 'forum topic');
        }
    }
    $smarty->assign('user_watching_topic', 'n');
    if ($user && $tikilib->user_watches($user, 'forum_post_thread', $comments_parentId, 'forum topic')) {
        $smarty->assign('user_watching_topic', 'y');
    }
    // Check, if the user is watching this forum's topic and thread by a category.
    if ($prefs['feature_categories'] == 'y') {
        $watching_categories_temp = $categlib->get_watching_categories($forumId, 'forum', $user);
        $smarty->assign('category_watched', 'n');
        if (count($watching_categories_temp) > 0) {
            $smarty->assign('category_watched', 'y');
            $watching_categories = [];
            foreach ($watching_categories_temp as $wct) {
                $watching_categories[] = [
                    "categId" => $wct,
                    "name" => $categlib->get_category_name($wct)
                ];
            }
            $smarty->assign('watching_categories', $watching_categories);
        }
    }
}
if ($tiki_p_admin_forum == 'y' || $prefs['feature_forum_quickjump'] == 'y') {
    $all_forums = $commentslib->list_forums(0, -1, 'name_asc', '');
    $temp_max = count($all_forums["data"]);
    for ($i = 0; $i < $temp_max; $i++) {
        if ($userlib->object_has_one_permission($all_forums["data"][$i]["forumId"], 'forum')) {
            if ($tiki_p_admin == 'y' || $userlib->object_has_permission($user, $all_forums["data"][$i]["forumId"], 'forum', 'tiki_p_admin_forum') || $userlib->object_has_permission($user, $all_forums["data"][$i]["forumId"], 'forum', 'tiki_p_forum_read')) {
                $all_forums["data"][$i]["can_read"] = 'y';
            } else {
                $all_forums["data"][$i]["can_read"] = 'n';
            }
        } else {
            $all_forums["data"][$i]["can_read"] = 'y';
        }
    }
    $smarty->assign('all_forums', $all_forums['data']);
}
// Generate the list of topics, used in comments.tpl for the moderator actions (e.g. move a comment to another topic)
if ($tiki_p_admin_forum == 'y') {
    $topics = $commentslib->get_forum_topics($forumId, 0, 200, 'commentDate_desc');
    $smarty->assign_by_ref('topics', $topics);
    $comms = array_column($topics, 'title', 'threadId');
    $smarty->assign('topics_encoded', json_encode($comms));
}
$smarty->assign('unread', 0);
if ($user && $prefs['feature_messages'] == 'y' && $tiki_p_messages == 'y') {
    $unread = $tikilib->user_unread_messages($user);
    $smarty->assign('unread', $unread);
}
if ($tiki_p_admin_forum == 'y') {
    $smarty->assign('queued', $commentslib->get_num_queued($comments_objectId));
    $smarty->assign('reported', $commentslib->get_num_reported($forumId));
}
if ($prefs['feature_freetags'] == 'y') {
    $cat_type = 'forum post';
    $cat_objid = $comments_parentId;
    $objectTags = $freetaglib->get_tags_on_object($cat_objid, $cat_type);
    $tags = [];
    if ($objectTags) {
        $tags = $objectTags['data'];
    }
    $smarty->assign('tags', $tags);
}
$defaultRows = $prefs['default_rows_textarea_forumthread'];
$smarty->assign('forum_mode', 'y');

if ($prefs['feature_actionlog'] == 'y') {
    $logslib->add_action('Viewed', $forumId, 'forum', 'comments_parentId=' . $comments_parentId);
}

if ($prefs['feature_forum_parse'] == 'y') {
    $wikilib = TikiLib::lib('wiki');
    $plugins = $wikilib->list_plugins(true, 'editpost2');
    $smarty->assign_by_ref('plugins', $plugins);
}
if (! empty($_REQUEST['view_atts']) && $_REQUEST['view_atts'] == 'y') {
    $fa_offset = isset($_REQUEST['fa_offset']) ? $_REQUEST['fa_offset'] : 0;
    $fa_maxRecords = isset($_REQUEST['fa_maxRecords']) ? $_REQUEST['fa_maxRecords'] : $prefs['maxRecords'];
    $atts = $commentslib->get_all_thread_attachments($comments_parentId, $fa_offset, $fa_maxRecords);
    $atts['offset'] = $fa_offset;
    $atts['maxRecords'] = $fa_maxRecords;
    $smarty->assign_by_ref('atts', $atts);
    $smarty->assign_by_ref('view_atts', $_REQUEST['view_atts']);
}

// Display the template
if (isset($_REQUEST['display'])) {
    // Remove icons and actions that should not be printed
    $prefs['forum_thread_user_settings'] = 'n';
    $forum_settings = array_fill_keys([
        'thread_show_comment_footers',
        'thread_show_pagination',
        'tiki_p_forum_post',
        'tiki_p_admin_forum',
        'tiki_p_forum_edit_own_posts',
        'tiki_p_notepad'
    ], 'n');
    $smarty->assign('display', $_REQUEST['display']);
    $smarty->assign($forum_settings);
    // Display the forum messages
    $smarty->assign('mid', 'tiki-print_forum_thread.tpl');
    // Allow PDF export by installing a Mod that define an appropriate function
    if ($_REQUEST['display'] == 'pdf') {
        $pdata = $smarty->fetch("tiki-print_forum_thread.tpl");
        $generator = new PdfGenerator();
        if (! empty($generator->error)) {
            Feedback::error($generator->error);
            $access->redirect($_SERVER['HTTP_REFERER']);
        } else {
            $pdf = $generator->getPdf('tiki-view_forum_thread.php', ['display' => 'print', 'comments_parentId' => $comments_parentId, 'forumId' => $forumId], $pdata);
            header('Cache-Control: private, must-revalidate');
            header('Pragma: private');
            header("Content-Description: File Transfer");
            header('Content-disposition: attachment; filename="' . $thread_info['title'] . '.pdf"');
            header("Content-Type: application/pdf");
            header("Content-Transfer-Encoding: binary");
            header('Content-Length: ' . strlen($pdf));
            TikiLib::lib('header')->setXRobotsTag($robots);
            echo $pdf;
        }
    } else {
        $smarty->display('tiki-print.tpl');
    }
} else {
    $smarty->assign('pdf_export', ($prefs['print_pdf_from_url'] != 'none') ? 'y' : 'n');
    $smarty->assign('display', '');
    $smarty->assign('mid', 'tiki-view_forum_thread.tpl');
    $smarty->display('tiki.tpl');
}
